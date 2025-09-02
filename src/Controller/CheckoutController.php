<?php
// src/Controller/CheckoutController.php
namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'checkout_start', methods: ['GET'])]
    public function start(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('info', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_show');
        }

        $items = [];
        $total = 0.0;

        foreach ($cart as $row) {
            $product = $em->getRepository(Product::class)->find($row['product_id'] ?? 0);
            if (!$product) {
                continue;
            }

            $size     = (string) ($row['size'] ?? '');
            $quantity = (int) ($row['quantity'] ?? 0);
            $available = $product->getStockForSize($size);

            if ($quantity <= 0 || $quantity > $available) {
                $this->addFlash('danger', sprintf(
                    "Stock insuffisant pour %s (taille %s).",
                    $product->getName(),
                    $size
                ));
                return $this->redirectToRoute('cart_show');
            }

            $price    = (float) $product->getPrice();
            $subtotal = $price * $quantity;

            $items[] = [
                'product'  => $product,
                'size'     => $size,
                'quantity' => $quantity,
                'price'    => $price,
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }

        if (!$items) {
            $this->addFlash('info', 'Votre panier ne contient plus d’articles disponibles.');
            return $this->redirectToRoute('cart_show');
        }

        return $this->render('checkout/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/checkout/create-session', name: 'checkout_create_session', methods: ['POST'])]
    public function createSession(
        SessionInterface $session,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urls
    ): Response {
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('info', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_show');
        }

        // ----- BYPASS STRIPE EN TEST / CI -----
        $isTestEnv = $this->getParameter('kernel.environment') === 'test';
        $fakeFlag  = filter_var(
            $_ENV['APP_FAKE_PAYMENT'] ?? getenv('APP_FAKE_PAYMENT') ?? '0',
            FILTER_VALIDATE_BOOL
        );

        if ($isTestEnv || $fakeFlag) {
            // on simule un succès de paiement
            return $this->redirectToRoute('checkout_success');
        }

        // ----- Revérification du panier + construction des line items -----
        $lineItems = [];
        foreach ($cart as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $size      = (string) ($row['size'] ?? '');
            $qty       = (int) ($row['quantity'] ?? 0);

            $product = $em->getRepository(Product::class)->find($productId);
            if (!$product || $qty <= 0) {
                continue;
            }

            $available = $product->getStockForSize($size);
            if ($qty > $available) {
                $this->addFlash('danger', sprintf(
                    "Stock insuffisant pour %s (taille %s).",
                    $product->getName(),
                    $size
                ));
                return $this->redirectToRoute('cart_show');
            }

            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => (int) round(((float) $product->getPrice()) * 100),
                    'product_data' => [
                        'name' => $product->getName() . ' - ' . $size,
                    ],
                ],
                'quantity' => $qty,
            ];
        }

        if (!$lineItems) {
            $this->addFlash('info', 'Votre panier ne contient plus d’articles disponibles.');
            return $this->redirectToRoute('cart_show');
        }

        // ----- Clé Stripe -----
        $secret = $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY') ?: null;
        if (!$secret) {
            $this->addFlash('danger', 'Clé Stripe manquante. Ajoute STRIPE_SECRET_KEY dans .env.local');
            return $this->redirectToRoute('app_order');
        }

        try {
            \Stripe\Stripe::setApiKey($secret);

            $sessionStripe = \Stripe\Checkout\Session::create([
                'mode'        => 'payment',
                'line_items'  => $lineItems,
                'metadata'    => ['cart_json' => json_encode($cart, JSON_UNESCAPED_UNICODE)],
                'success_url' => $urls->generate('checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url'  => $urls->generate('app_order', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            return $this->redirect($sessionStripe->url, 303);

        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Erreur Stripe : ' . $e->getMessage());
            return $this->redirectToRoute('app_order');
        }
    }

    #[Route('/checkout/success', name: 'checkout_success', methods: ['GET'])]
    public function success(SessionInterface $session): Response
    {
        // Le décrément réel se fait via le webhook Stripe côté serveur.
        // Ici on nettoie juste le panier côté client.
        $session->remove('cart');

        return $this->render('checkout/success.html.twig');
    }
}
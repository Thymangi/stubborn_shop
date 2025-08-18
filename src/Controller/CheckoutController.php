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
        $total = 0;

        foreach ($cart as $row) {
            $product = $em->getRepository(Product::class)->find($row['product_id']);
            if (!$product) {
                continue;
            }

            $size      = (string) $row['size'];
            $quantity  = (int) $row['quantity'];
            $available = $product->getStockForSize($size);

            // Re-vérif stock
            if ($quantity > $available) {
                $this->addFlash(
                    'danger',
                    sprintf("Stock insuffisant pour %s (taille %s).", $product->getName(), $size)
                );
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

        if (empty($items)) {
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

        // Construire les line_items Stripe
        $lineItems = [];
        foreach ($cart as $row) {
            $product = $em->getRepository(Product::class)->find($row['product_id']);
            if (!$product) { continue; }

            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => (int) round($product->getPrice() * 100), // € → centimes
                    'product_data' => [
                        'name' => $product->getName().' - '.$row['size'],
                    ],
                ],
                'quantity' => (int) $row['quantity'],
            ];
        }

        if (!$lineItems) {
            $this->addFlash('info', 'Votre panier ne contient plus d’articles disponibles.');
            return $this->redirectToRoute('cart_show');
        }

        // Récup clé Stripe
        $secret = $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY') ?: null;
        if (!$secret) {
            $this->addFlash('danger', 'Clé Stripe manquante. Ajoute STRIPE_SECRET_KEY dans .env.local');
            return $this->redirectToRoute('app_order');
        }

        // Ajout du panier en metadata (pour le webhook)
        $metadata = [
            'cart_json' => json_encode($cart, JSON_UNESCAPED_UNICODE),
        ];

        try {
            \Stripe\Stripe::setApiKey($secret);

            $sessionStripe = \Stripe\Checkout\Session::create([
                'mode'        => 'payment',
                'line_items'  => $lineItems,
                'metadata'    => $metadata,
                'success_url' => $urls->generate('checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url'  => $urls->generate('app_order', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            // Redirection 303 vers la page Stripe
            return $this->redirect($sessionStripe->url, 303);

        } catch (\Throwable $e) {
            // En cas d'erreur Stripe → message et retour
            $this->addFlash('danger', 'Erreur Stripe : '.$e->getMessage());
            return $this->redirectToRoute('app_order');
        }
    }

    #[Route('/checkout/success', name: 'checkout_success', methods: ['GET'])]
    public function success(SessionInterface $session): Response
    {
        // Le décrément réel se fait dans le webhook.
        // Côté client, on nettoie juste le panier.
        $session->remove('cart');

        return $this->render('checkout/success.html.twig');
    }
}
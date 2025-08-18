<?php
// src/Service/StripeCheckoutService.php
namespace App\Service;

use App\Port\StripeCheckoutPort;
use App\Entity\Product;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class StripeCheckoutService
{
    public function __construct(
        private StripeCheckoutPort $port,   // dépend plus du SDK ici
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urls
    ) {}

    /** Crée une session Checkout et retourne [id, url] */
    public function createCheckoutForCart(array $cart): array
    {
        $lineItems = [];
        foreach ($cart as $row) {
            $product = $this->em->getRepository(Product::class)->find($row['product_id'] ?? 0);
            if (!$product) { continue; }

            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => (int) round($product->getPrice() * 100),
                    'product_data' => [
                        'name' => $product->getName().' - '.($row['size'] ?? ''),
                    ],
                ],
                'quantity' => (int) ($row['quantity'] ?? 0),
            ];
        }
        if (!$lineItems) {
            throw new \RuntimeException('Panier vide ou invalide.');
        }

        $successUrl = $this->urls->generate('checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL)
                    . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = $this->urls->generate('app_order', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $session = $this->port->createSession([
            'mode'        => 'payment',
            'line_items'  => $lineItems,
            'metadata'    => ['cart_json' => json_encode($cart, JSON_UNESCAPED_UNICODE)],
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ]);

        return ['id' => $session->id, 'url' => $session->url ?? null];
    }

    /** Vérifie la session, crée la commande si besoin, décrémente les stocks. */
    public function fulfillOrderFromSession(string $sessionId): Order
    {
        // idempotence
        $orderRepo = $this->em->getRepository(Order::class);
        if ($existing = $orderRepo->findOneBy(['stripeSessionId' => $sessionId])) {
            return $existing;
        }

        // session Stripe
        $session = $this->port->retrieveSession($sessionId);
        if (($session->payment_status ?? '') !== 'paid') {
            throw new \RuntimeException('Paiement non confirmé (Stripe).');
        }

        // panier
        $cartJson = $session->metadata->cart_json ?? null;
        $cart = $cartJson ? json_decode($cartJson, true) : [];
        if (!is_array($cart) || empty($cart)) {
            throw new \RuntimeException('Panier introuvable dans la session Stripe.');
        }

        // création commande
        $order = new Order();
        $order->setStripeSessionId($sessionId);
        $order->setStatus('paid');
        $order->setCreatedAt(new \DateTimeImmutable());

        $totalCents = 0;

        foreach ($cart as $row) {
            $pid  = (int) ($row['product_id'] ?? 0);
            $size = (string) ($row['size'] ?? '');
            $qty  = (int) ($row['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0 || $size === '') { continue; }

            $product = $this->em->getRepository(Product::class)->find($pid);
            if (!$product) { continue; }

            // décrément stock
            $available = $product->getStockForSize($size);
            $stocks = $product->getStocks();
            $stocks[$size] = max(0, $available - $qty);
            $product->setStocks($stocks); // force un nouvel array pour changement détecté

            // force Doctrine à voir la modif JSON
            $meta = $this->em->getClassMetadata(\App\Entity\Product::class);
            $this->em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $product);

            $unit = (float) $product->getPrice();

            $item = (new OrderItem())
                ->setOrderRef($order)
                ->setProduct($product)
                ->setSize($size)
                ->setQuantity($qty)
                ->setPrice($unit);

            $totalCents += (int) round($unit * 100) * $qty;

            $this->em->persist($product);
            $this->em->persist($item);
        }

        $order->setTotalCents($totalCents);
        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }
}
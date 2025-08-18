<?php
// src/Controller/StripeWebhookController.php
namespace App\Controller;

use App\Service\StripeCheckoutService;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request, StripeCheckoutService $svc): Response
    {
        $payload   = $request->getContent();
        $signature = $request->headers->get('stripe-signature') ?? '';
        $secret    = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (\Throwable $e) {
            return new Response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $sid = $event->data->object->id ?? null;
            if ($sid) {
                // Idempotent: le service ne créera pas deux fois la même commande
                $svc->fulfillOrderFromSession($sid);
            }
        }

        return new Response('OK', 200);
    }
}
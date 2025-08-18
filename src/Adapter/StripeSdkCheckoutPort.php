<?php
// src/Adapter/StripeSdkCheckoutPort.php
namespace App\Adapter;

use App\Port\StripeCheckoutPort;
use Stripe\StripeClient;

final class StripeSdkCheckoutPort implements StripeCheckoutPort
{
    public function __construct(private StripeClient $stripe) {}

    public function createSession(array $payload): object
    {
        return $this->stripe->checkout->sessions->create($payload);
    }

    public function retrieveSession(string $sessionId): object
    {
        return $this->stripe->checkout->sessions->retrieve($sessionId, []);
    }
}
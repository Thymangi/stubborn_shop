<?php
namespace App\Port;

interface StripeCheckoutPort
{
    /** @return object { id: string, url?: string } */
    public function createSession(array $payload): object;

    /** @return object { id: string, payment_status?: string, metadata?: object } */
    public function retrieveSession(string $sessionId): object;
}
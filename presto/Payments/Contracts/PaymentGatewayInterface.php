<?php

declare(strict_types=1);

namespace PrestoWorld\Payments\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Get the name of the gateway (stripe, paypal, etc.)
     */
    public function getName(): string;

    /**
     * Process a payment request
     */
    public function purchase(array $options): array;

    /**
     * Handle payment callback/webhook
     */
    public function completePurchase(array $options): array;
}

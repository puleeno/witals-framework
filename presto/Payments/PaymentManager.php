<?php

declare(strict_types=1);

namespace PrestoWorld\Payments;

use Omnipay\Omnipay;
use PrestoWorld\Payments\Contracts\PaymentGatewayInterface;

class PaymentManager
{
    protected array $gateways = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Create/Retrieve an Omnipay gateway instance
     */
    public function gateway(string $name = 'paypal')
    {
        if (isset($this->gateways[$name])) {
            return $this->gateways[$name];
        }

        $gateway = Omnipay::create($this->resolveGatewayClass($name));
        
        // Initialize with config
        $gateway->initialize($this->config[$name] ?? []);

        return $this->gateways[$name] = $gateway;
    }

    protected function resolveGatewayClass(string $name): string
    {
        return match($name) {
            'paypal' => 'PayPal_Rest',
            'stripe' => 'Stripe',
            default => throw new \InvalidArgumentException("Unsupported gateway: {$name}")
        };
    }
}

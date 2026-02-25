<?php

declare(strict_types=1);

namespace PrestoWorld\Ecommerce;

use Cycle\Database\DatabaseProviderInterface;
use Cake\Chronos\Chronos;

class OrderManager
{
    protected DatabaseProviderInterface $dbal;

    public function __construct(DatabaseProviderInterface $dbal)
    {
        $this->dbal = $dbal;
    }

    public function createOrder(array $data): int
    {
        $orderData = [
            'order_number' => $data['order_number'] ?? 'ORD-' . strtoupper(uniqid()),
            'customer_id' => $data['customer_id'] ?? null,
            'customer_email' => $data['customer_email'],
            'status' => 'pending',
            'payment_status' => 'pending',
            'total' => $data['total'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'items' => json_encode($data['items'] ?? []),
            'created_at' => Chronos::now()
        ];

        return (int)$this->dbal->database()->insert('orders')->values($orderData)->run();
    }

    public function createInvoice(int $orderId, array $data): int
    {
        $invoiceData = [
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'order_id' => $orderId,
            'status' => 'draft',
            'total' => $data['total'],
            'created_at' => Chronos::now()
        ];

        return (int)$this->dbal->database()->insert('invoices')->values($invoiceData)->run();
    }
}

<?php

declare(strict_types=1);

namespace PrestoWorld\Ecommerce\Traits;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

trait HasPurchaseAction
{
    /**
     * Common action to handle and add item to cart or direct purchase
     */
    public function purchase(Request $request, string $id): Response
    {
        $item = $this->resolveItem($id);
        
        if (!$item) {
            return $this->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        // Logic to add to cart or process direct buy
        // For now, return success
        return $this->json([
            'success' => true,
            'message' => 'Added to checkout',
            'data' => [
                'id'    => $item->getBuyableId(),
                'title' => $item->getBuyableTitle(),
                'price' => $item->getBuyablePrice(),
                'type'  => $item->getBuyableType()
            ]
        ]);
    }

    abstract protected function resolveItem(string $id);
}

<?php

declare(strict_types=1);

namespace PrestoWorld\Ecommerce\Contracts;

interface BuyableInterface
{
    public function getBuyableId(): string|int;
    public function getBuyableTitle(): string;
    public function getBuyablePrice(): int|float;
    public function getBuyableType(): string;
}

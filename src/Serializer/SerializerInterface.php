<?php

declare(strict_types=1);

namespace Witals\Framework\Serializer;

interface SerializerInterface
{
    public function serialize(mixed $data, string $format = 'json'): string;

    public function deserialize(string $data, string $format = 'json'): mixed;

    public function normalize(mixed $data): array;

    public function denormalize(array $data, string $type): object;
}

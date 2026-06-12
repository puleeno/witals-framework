<?php

declare(strict_types=1);

namespace Witals\Framework\Serializer;

class JsonSerializer implements SerializerInterface
{
    public function __construct(
        protected int $encodeFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        protected int $decodeFlags = JSON_THROW_ON_ERROR,
        protected ?ArraySerializer $arraySerializer = null
    ) {
        $this->arraySerializer ??= new ArraySerializer();
    }

    public function serialize(mixed $data, string $format = 'json'): string
    {
        return json_encode($data, $this->encodeFlags);
    }

    public function deserialize(string $data, string $format = 'json'): mixed
    {
        return json_decode($data, true, 512, $this->decodeFlags);
    }

    public function normalize(mixed $data): array
    {
        return $this->arraySerializer->normalize($data);
    }

    public function denormalize(array $data, string $type): object
    {
        return $this->arraySerializer->denormalize($data, $type);
    }

    public function setEncodeFlags(int $flags): static
    {
        $this->encodeFlags = $flags;
        return $this;
    }

    public function setDecodeFlags(int $flags): static
    {
        $this->decodeFlags = $flags;
        return $this;
    }

    public function prettyPrint(): static
    {
        $this->encodeFlags |= JSON_PRETTY_PRINT;
        return $this;
    }
}

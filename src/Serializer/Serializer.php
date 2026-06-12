<?php

declare(strict_types=1);

namespace Witals\Framework\Serializer;

class Serializer implements SerializerInterface
{
    protected array $formats = [];

    public function __construct(
        protected JsonSerializer $jsonSerializer,
        protected ArraySerializer $arraySerializer
    ) {
        $this->formats = [
            'json' => $jsonSerializer,
            'array' => $arraySerializer,
        ];
    }

    public function registerFormat(string $name, SerializerInterface $serializer): void
    {
        $this->formats[$name] = $serializer;
    }

    public function serialize(mixed $data, string $format = 'json'): string
    {
        return $this->resolve($format)->serialize($data, $format);
    }

    public function deserialize(string $data, string $format = 'json'): mixed
    {
        return $this->resolve($format)->deserialize($data, $format);
    }

    public function normalize(mixed $data): array
    {
        return $this->jsonSerializer->normalize($data);
    }

    public function denormalize(array $data, string $type): object
    {
        return $this->jsonSerializer->denormalize($data, $type);
    }

    protected function resolve(string $format): SerializerInterface
    {
        return $this->formats[$format] ?? throw new \InvalidArgumentException("Unsupported format: {$format}");
    }
}

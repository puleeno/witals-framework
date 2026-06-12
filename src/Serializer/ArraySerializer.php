<?php

declare(strict_types=1);

namespace Witals\Framework\Serializer;

class ArraySerializer implements SerializerInterface
{
    public function serialize(mixed $data, string $format = 'array'): string
    {
        throw new \RuntimeException('ArraySerializer cannot produce string output. Use JsonSerializer instead.');
    }

    public function deserialize(string $data, string $format = 'array'): mixed
    {
        throw new \RuntimeException('ArraySerializer cannot parse string input. Use JsonSerializer instead.');
    }

    public function normalize(mixed $data): array
    {
        if (is_array($data)) {
            return $this->normalizeArray($data);
        }

        if (is_object($data)) {
            return $this->normalizeObject($data);
        }

        return [$data];
    }

    public function denormalize(array $data, string $type): object
    {
        $ref = new \ReflectionClass($type);
        $instance = $ref->newInstanceWithoutConstructor();

        foreach ($data as $property => $value) {
            if ($ref->hasProperty($property)) {
                $prop = $ref->getProperty($property);
                $prop->setValue($instance, $value);
            }
        }

        return $instance;
    }

    protected function normalizeArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = $this->normalizeValue($value);
        }
        return $result;
    }

    protected function normalizeObject(object $object): array
    {
        $ref = new \ReflectionClass($object);
        $result = [];

        foreach ($ref->getProperties() as $prop) {
            $prop->setAccessible(true);
            $value = $prop->getValue($object);
            $result[$prop->getName()] = $this->normalizeValue($value);
        }

        return $result;
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if (is_object($value)) {
            return $this->normalizeObject($value);
        }

        if (is_array($value)) {
            return $this->normalizeArray($value);
        }

        return $value;
    }
}

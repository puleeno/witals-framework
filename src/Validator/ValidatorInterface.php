<?php

declare(strict_types=1);

namespace Witals\Framework\Validator;

interface ValidatorInterface
{
    public function validate(array $data, array $rules): array;

    public function passed(): bool;

    public function fails(): bool;

    public function errors(): array;

    public function setCustomMessages(array $messages): static;
}

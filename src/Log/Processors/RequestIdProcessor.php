<?php

declare(strict_types=1);

namespace Witals\Framework\Log\Processors;

use Witals\Framework\Log\ProcessorInterface;

/**
 * Request ID Processor
 * Adds a unique request ID to all logs within a request lifecycle.
 * In long-running environments, this ID must be updated per request.
 */
class RequestIdProcessor implements ProcessorInterface
{
    protected ?string $requestId = null;

    public function __construct(?string $requestId = null)
    {
        $this->requestId = $requestId ?: bin2hex(random_bytes(8));
    }

    public function __invoke(array $record): array
    {
        $record['request_id'] = $this->requestId;
        return $record;
    }

    public function setRequestId(string $id): void
    {
        $this->requestId = $id;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }
}

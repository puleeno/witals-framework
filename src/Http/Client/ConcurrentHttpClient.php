<?php

declare(strict_types=1);

namespace Witals\Framework\Http\Client;

use Witals\Framework\Contracts\ConcurrentManager;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ConcurrentHttpClient
{
    private HttpClientInterface $client;

    private ?FiberAwareHttpClient $fiberClient = null;

    public function __construct(
        ?HttpClientInterface $client = null,
        array $defaultOptions = [],
        private ?ConcurrentManager $concurrent = null,
    ) {
        $this->client = $client ?? HttpClient::create($defaultOptions);
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $url, $options);
    }

    /**
     * Dispatch multiple requests concurrently and return responses in order.
     * Uses Symfony's curl_multi under the hood — non-blocking at transport level.
     *
     * @param array<string, array{method?: string, url: string, options?: array}> $requests
     * @return array<string, string>
     */
    public function fetch(array $requests): array
    {
        $responses = [];
        foreach ($requests as $key => $req) {
            $responses[$key] = $this->client->request(
                $req['method'] ?? 'GET',
                $req['url'],
                $req['options'] ?? [],
            );
        }

        $results = [];
        foreach ($responses as $key => $response) {
            $results[$key] = $response->getContent();
        }

        return $results;
    }

    /**
     * Stream responses as they complete via Symfony's event-driven transport.
     * Inside a long-running worker, yields results progressively instead of
     * waiting for all responses to finish.
     *
     * @param array<string, ResponseInterface> $responses
     * @return \Generator<string, string>
     */
    public function stream(array $responses): \Generator
    {
        foreach ($this->client->stream($responses) as $response => $chunk) {
            if ($chunk->isLast()) {
                $key = array_search($response, $responses, true);
                if ($key !== false) {
                    yield $key => $response->getContent();
                }
            }
        }
    }

    /**
     * Concurrent HTTP fetch using Fibers.
     *
     * When FiberManager is enabled (long-running runtime): fires all
     * requests at once (curl_multi transport concurrency), then uses
     * the event loop to collect responses — fibers that finish first
     * yield their results first.
     *
     * Traditional runtime: runs requests sequentially via getContent().
     *
     * @param array<string, array{method?: string, url: string, options?: array}> $requests
     * @return array<string, string>
     */
    public function fiberFetch(array $requests): array
    {
        if ($this->concurrent?->isEnabled()) {
            return $this->fiberClient()->fiberFetch($requests);
        }

        return $this->fetch($requests);
    }

    public function getClient(): HttpClientInterface
    {
        return $this->client;
    }

    private function fiberClient(): FiberAwareHttpClient
    {
        if ($this->fiberClient === null) {
            $this->fiberClient = new FiberAwareHttpClient(
                $this->client,
                $this->concurrent,
            );
        }

        return $this->fiberClient;
    }
}

<?php

declare(strict_types=1);

namespace Witals\Framework\Http\Client;

use Witals\Framework\Contracts\ConcurrentManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FiberAwareHttpClient
{
    public function __construct(
        private HttpClientInterface $client,
        private ConcurrentManager $concurrent,
    ) {}

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $url, $options);
    }

    /**
     * Fire-and-collect: starts all requests immediately (curl_multi handles
     * transport concurrency), then collects responses via stream().
     *
     * Traditional server — sequential getContent() calls.
     * RoadRunner — Fibers + EventLoop interleave chunk processing.
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

        if (!$this->concurrent->isEnabled()) {
            return $this->collectResponses($responses);
        }

        return $this->concurrent->run(function () use ($responses) {
            $results = [];
            $stream = $this->client->stream($responses, 0.1);

            foreach ($stream as $response => $chunk) {
                if ($chunk->isLast()) {
                    $key = array_search($response, $responses, true);
                    if ($key !== false) {
                        $results[$key] = $response->getContent();
                    }
                }
            }

            return $results;
        });
    }

    /**
     * Concurrent fetch using Fibers + EventLoop.
     *
     * Each request runs inside its own Fiber; the EventLoop suspends a Fiber
     * when its response is pending and resumes it when data is ready.
     * This prevents sequential blocking for multiple external API calls
     * inside a single RoadRunner request.
     *
     * @param array<string, array{method?: string, url: string, options?: array}> $requests
     * @return array<string, string>
     */
    public function fiberFetch(array $requests): array
    {
        if (!$this->concurrent->isEnabled()) {
            return $this->fetch($requests);
        }

        $responses = [];
        foreach ($requests as $key => $req) {
            $responses[$key] = $this->client->request(
                $req['method'] ?? 'GET',
                $req['url'],
                $req['options'] ?? [],
            );
        }

        $tasks = [];
        foreach ($responses as $key => $response) {
            $tasks[$key] = fn() => $response->getContent();
        }

        return $this->concurrent->all($tasks);
    }

    public function getClient(): HttpClientInterface
    {
        return $this->client;
    }

    private function collectResponses(array $responses): array
    {
        $results = [];
        foreach ($responses as $key => $response) {
            $results[$key] = $response->getContent();
        }
        return $results;
    }
}

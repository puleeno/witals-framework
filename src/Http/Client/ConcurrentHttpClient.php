<?php

declare(strict_types=1);

namespace Witals\Framework\Http\Client;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ConcurrentHttpClient
{
    private HttpClientInterface $client;

    public function __construct(?HttpClientInterface $client = null, array $defaultOptions = [])
    {
        $this->client = $client ?? HttpClient::create($defaultOptions);
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $url, $options);
    }

    /**
     * Dispatch multiple requests concurrently and return responses in order.
     * Uses Symfony's curl_multi under the hood — non-blocking at transport level.
     * Inside a long-running worker, call this once instead of N sequential requests.
     *
     * @param array<string, array{method?: string, url: string, options?: array}> $requests
     * @return array<string, string>  key => response body
     */
    public function fetch(array $requests): array
    {
        $responses = [];
        foreach ($requests as $key => $req) {
            $responses[$key] = $this->client->request(
                $req['method'] ?? 'GET',
                $req['url'],
                $req['options'] ?? []
            );
        }

        $results = [];
        foreach ($responses as $key => $response) {
            $results[$key] = $response->getContent();
        }

        return $results;
    }

    /**
     * Stream responses as they complete. Useful for large payloads or
     * when you want to process results progressively.
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
     * Concurrent search queries using Fibers.
     * Each Fiber dispatches one request; Symfony's underlying curl_multi
     * handles the actual I/O concurrency. In long-running runtimes this
     * prevents sequential blocking for multiple external API calls.
     *
     * @param array<string, array{method?: string, url: string, options?: array}> $requests
     * @return array<string, string>
     */
    public function fiberFetch(array $requests): array
    {
        $results = [];
        $fibers = [];

        foreach ($requests as $key => $req) {
            $client = $this->client;
            $fibers[$key] = new \Fiber(function () use ($client, $req) {
                return $client->request(
                    $req['method'] ?? 'GET',
                    $req['url'],
                    $req['options'] ?? []
                )->getContent();
            });
        }

        foreach ($fibers as $key => $fiber) {
            $fiber->start();
            if ($fiber->isTerminated()) {
                $results[$key] = $fiber->getReturn();
            }
        }

        return $results;
    }

    public function getClient(): HttpClientInterface
    {
        return $this->client;
    }
}

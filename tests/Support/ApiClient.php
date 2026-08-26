<?php

namespace Tests\Support;

use GuzzleHttp\Client;

/**
 * Thin wrapper around Guzzle for calling the real api/*.php endpoints
 * during integration tests. Every EJR Garage endpoint accepts a JSON
 * body and always responds with JSON, so this keeps every test's
 * arrange/act/assert short.
 */
class ApiClient
{
    private Client $client;

    public function __construct(?string $baseUri = null)
    {
        $this->client = new Client([
            'base_uri'    => $baseUri ?? (getenv('API_BASE_URI') ?: 'http://localhost:8000/api/'),
            'http_errors' => false, // we assert on status codes ourselves
            'timeout'     => 10,
        ]);
    }

    /** @param array<string,string> $headers */
    public function post(string $endpoint, array $payload = [], array $headers = []): array
    {
        return $this->send('POST', $endpoint, $payload, $headers);
    }

    /** @param array<string,string> $headers */
    public function get(string $endpoint, array $headers = []): array
    {
        return $this->send('GET', $endpoint, null, $headers);
    }

    /** @param array<string,string>|null $payload */
    private function send(string $method, string $endpoint, ?array $payload, array $headers): array
    {
        $options = [
            'headers' => array_merge(['Content-Type' => 'application/json'], $headers),
        ];

        if ($payload !== null) {
            $options['json'] = $payload;
        }

        $response = $this->client->request($method, ltrim($endpoint, '/'), $options);
        $rawBody  = (string) $response->getBody();

        return [
            'status' => $response->getStatusCode(),
            'body'   => json_decode($rawBody, true),
            'raw'    => $rawBody,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Analytics\Http;

class Request
{
    private string $method;
    private string $uri;
    private array $queryParams;
    private ?array $body;
    private array $headers;

    public function __construct(
        string $method,
        string $uri,
        array $queryParams = [],
        ?array $body = null,
        array $headers = []
    ) {
        $this->method = strtoupper($method);
        $this->uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $this->queryParams = $queryParams;
        $this->body = $body;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $queryParams = $_GET;

        // Parse JSON body if present
        $rawBody = file_get_contents('php://input');
        $body = null;
        if (!empty($rawBody)) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $body = $decoded;
            }
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$headerName] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headerName = strtolower(str_replace('_', '-', $key));
                $headers[$headerName] = $value;
            }
        }

        return new self($method, $uri, $queryParams, $body, $headers);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getAllQuery(): array
    {
        return $this->queryParams;
    }

    public function getBody(): ?array
    {
        return $this->body;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function getClientIp(): string
    {
        if (!empty($this->headers['x-forwarded-for'])) {
            $ips = explode(',', $this->headers['x-forwarded-for']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function getUserAgent(): string
    {
        return $this->headers['user-agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}

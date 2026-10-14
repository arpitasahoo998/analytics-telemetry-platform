<?php

declare(strict_types=1);

namespace Analytics\Http;

class Response
{
    private int $statusCode;
    private array $headers;
    private ?string $content;

    public function __construct(int $statusCode = 200, array $headers = [], ?string $content = null)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->content = $content;
    }

    public static function json(mixed $data, int $statusCode = 200, array $headers = []): self
    {
        $defaultHeaders = [
            'Content-Type' => 'application/json; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-Session-Id, X-Anonymous-Id',
        ];

        $mergedHeaders = array_merge($defaultHeaders, $headers);
        $encoded = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new self($statusCode, $mergedHeaders, $encoded !== false ? $encoded : '{}');
    }

    public static function corsPreflight(): self
    {
        return new self(204, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-Session-Id, X-Anonymous-Id',
            'Access-Control-Max-Age' => '86400',
        ], '');
    }

    public static function error(string $message, int $statusCode = 400, array $details = []): self
    {
        $payload = [
            'status' => 'error',
            'message' => $message,
        ];
        if (!empty($details)) {
            $payload['details'] = $details;
        }

        return self::json($payload, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $header => $value) {
            header("{$header}: {$value}");
        }
        if ($this->content !== null) {
            echo $this->content;
        }
    }
}

<?php

declare(strict_types=1);

namespace Analytics\Sdk\Transport;

use Analytics\Services\IngestionService;

/**
 * Direct Ingestion Transport:
 * Directly invokes IngestionService in-process.
 * Useful when running in single-threaded environments (like PHP CLI built-in server)
 * or when backend services share the same database process to avoid HTTP overhead.
 */
class DirectIngestionTransport implements TransportInterface
{
    private IngestionService $ingestionService;

    public function __construct(?IngestionService $ingestionService = null)
    {
        $this->ingestionService = $ingestionService ?? new IngestionService();
    }

    public function send(array $payload): bool
    {
        $res = $this->ingestionService->processPayload($payload);
        return $res['success'];
    }
}

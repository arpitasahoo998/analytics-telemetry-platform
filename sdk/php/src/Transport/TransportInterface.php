<?php

declare(strict_types=1);

namespace Analytics\Sdk\Transport;

interface TransportInterface
{
    /**
     * Send event payload to ingestion endpoint.
     *
     * @param array $payload
     * @return bool True if successfully sent, false otherwise
     */
    public function send(array $payload): bool;
}

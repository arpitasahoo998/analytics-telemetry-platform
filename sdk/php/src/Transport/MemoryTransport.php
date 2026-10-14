<?php

declare(strict_types=1);

namespace Analytics\Sdk\Transport;

class MemoryTransport implements TransportInterface
{
    /** @var array<int, array> */
    private array $sentPayloads = [];

    public function send(array $payload): bool
    {
        $this->sentPayloads[] = $payload;
        return true;
    }

    /**
     * @return array<int, array>
     */
    public function getSentPayloads(): array
    {
        return $this->sentPayloads;
    }

    public function clear(): void
    {
        $this->sentPayloads = [];
    }

    public function count(): int
    {
        return count($this->sentPayloads);
    }
}

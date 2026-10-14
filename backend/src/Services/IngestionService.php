<?php

declare(strict_types=1);

namespace Analytics\Services;

use Analytics\Repositories\EventRepository;
use Analytics\Http\Request;

class IngestionService
{
    private EventRepository $repository;

    public function __construct(?EventRepository $repository = null)
    {
        $this->repository = $repository ?? new EventRepository();
    }

    /**
     * Process incoming event payload (single event or batch).
     *
     * @param array $payload
     * @param Request|null $request
     * @return array [ 'success' => bool, 'processed' => int, 'errors' => array ]
     */
    public function processPayload(array $payload, ?Request $request = null): array
    {
        // Support both single event { ... } and batch { "events": [ ... ] } or [ { ... }, { ... } ]
        $eventsList = [];
        if (isset($payload['events']) && is_array($payload['events'])) {
            $eventsList = $payload['events'];
        } elseif (array_is_list($payload) && !empty($payload) && is_array($payload[0])) {
            $eventsList = $payload;
        } else {
            $eventsList = [$payload];
        }

        $validatedEvents = [];
        $errors = [];

        foreach ($eventsList as $index => $item) {
            if (!is_array($item)) {
                $errors[] = ['index' => $index, 'error' => 'Event must be a valid JSON object'];
                continue;
            }

            $validation = $this->validateAndNormalize($item, $request);
            if ($validation['valid']) {
                $validatedEvents[] = $validation['event'];
            } else {
                $errors[] = ['index' => $index, 'error' => $validation['error']];
            }
        }

        $insertedCount = 0;
        if (!empty($validatedEvents)) {
            $insertedCount = $this->repository->insertBatch($validatedEvents);
        }

        return [
            'success' => count($errors) === 0 || $insertedCount > 0,
            'received' => count($eventsList),
            'accepted' => count($validatedEvents),
            'inserted' => $insertedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Validate and normalize a single event record.
     */
    public function validateAndNormalize(array $raw, ?Request $request = null): array
    {
        // 1. Validate event_name
        $eventName = trim((string)($raw['event_name'] ?? ''));
        if ($eventName === '' || strlen($eventName) > 128) {
            return ['valid' => false, 'error' => 'event_name is required (1-128 characters)'];
        }

        // 2. Validate anonymous_id
        $anonymousId = trim((string)($raw['anonymous_id'] ?? ''));
        if ($anonymousId === '' || strlen($anonymousId) > 128) {
            return ['valid' => false, 'error' => 'anonymous_id is required (1-128 characters)'];
        }

        // 3. Ensure event_id exists (generate UUID v4 if absent)
        $eventId = trim((string)($raw['event_id'] ?? ''));
        if ($eventId === '') {
            $eventId = $this->generateUuidV4();
        }

        // 4. Normalize timestamp
        $timestamp = $raw['timestamp'] ?? gmdate('Y-m-d\TH:i:s.v\Z');

        // 5. Source: browser or server
        $source = in_array($raw['source'] ?? '', ['browser', 'server'], true)
            ? $raw['source']
            : 'browser';

        // 6. Properties & Context
        $properties = is_array($raw['properties'] ?? null) ? $raw['properties'] : [];
        $context = is_array($raw['context'] ?? null) ? $raw['context'] : [];

        // Enrich context with server IP / User-Agent if request is present
        if ($request !== null) {
            if (empty($context['ip_address'])) {
                $context['ip_address'] = $request->getClientIp();
            }
            if (empty($context['user_agent'])) {
                $context['user_agent'] = $request->getUserAgent();
            }
        }

        // Payload size protection: limit properties and context size
        if (strlen(json_encode($properties)) > 65536) {
            return ['valid' => false, 'error' => 'properties payload exceeds 64KB size limit'];
        }

        return [
            'valid' => true,
            'event' => [
                'event_id' => $eventId,
                'event_name' => $eventName,
                'timestamp' => (string)$timestamp,
                'source' => $source,
                'anonymous_id' => $anonymousId,
                'session_id' => !empty($raw['session_id']) ? (string)$raw['session_id'] : null,
                'user_id' => !empty($raw['user_id']) ? (string)$raw['user_id'] : null,
                'properties' => $properties,
                'context' => $context,
            ],
        ];
    }

    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

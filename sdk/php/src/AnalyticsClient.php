<?php

declare(strict_types=1);

namespace Analytics\Sdk;

use Analytics\Sdk\Transport\HttpTransport;
use Analytics\Sdk\Transport\TransportInterface;

class AnalyticsClient
{
    private string $endpoint;
    private TransportInterface $transport;
    private array $defaultContext;
    private bool $debug;

    public function __construct(array $config = [])
    {
        $this->endpoint = $config['endpoint'] ?? 'http://127.0.0.1:8000/api/v1/events';
        $this->debug = (bool)($config['debug'] ?? false);
        $timeout = (float)($config['timeout'] ?? 1.5);
        $connectTimeout = (float)($config['connect_timeout'] ?? 0.8);

        $this->transport = $config['transport'] ?? new HttpTransport(
            $this->endpoint,
            $timeout,
            $connectTimeout,
            $this->debug
        );

        $this->defaultContext = array_merge([
            'sdk_name' => 'analytics-php-sdk',
            'sdk_version' => '1.0.0',
            'runtime' => 'PHP ' . PHP_VERSION,
            'server_hostname' => gethostname() ?: 'unknown',
        ], $config['default_context'] ?? []);
    }

    /**
     * Track a custom backend business event.
     *
     * @param string $eventName e.g., 'order_created', 'payment_processed'
     * @param array $properties Event business properties
     * @param string|null $userId Authenticated user ID (optional)
     * @param string|null $anonymousId Visitor anonymous ID (for identity stitching)
     * @param array $context Additional event context
     * @param string|null $sessionId Client session ID
     * @return bool True if successfully dispatched, false otherwise
     */
    public function track(
        string $eventName,
        array $properties = [],
        ?string $userId = null,
        ?string $anonymousId = null,
        array $context = [],
        ?string $sessionId = null
    ): bool {
        $event = $this->buildEventPayload(
            eventName: $eventName,
            properties: $properties,
            userId: $userId,
            anonymousId: $anonymousId,
            sessionId: $sessionId,
            context: $context
        );

        return $this->dispatch($event);
    }

    /**
     * Identify a user and record identity traits.
     */
    public function identify(
        string $userId,
        array $traits = [],
        ?string $anonymousId = null,
        ?string $sessionId = null
    ): bool {
        return $this->track(
            eventName: 'identify',
            properties: ['traits' => $traits],
            userId: $userId,
            anonymousId: $anonymousId,
            sessionId: $sessionId
        );
    }

    /**
     * Track a server-rendered page view.
     */
    public function page(
        string $pageName,
        array $properties = [],
        ?string $userId = null,
        ?string $anonymousId = null,
        ?string $sessionId = null
    ): bool {
        $properties['name'] = $pageName;
        return $this->track(
            eventName: 'page_view',
            properties: $properties,
            userId: $userId,
            anonymousId: $anonymousId,
            sessionId: $sessionId
        );
    }

    private function buildEventPayload(
        string $eventName,
        array $properties,
        ?string $userId,
        ?string $anonymousId,
        ?string $sessionId,
        array $context
    ): array {
        // Fallback anonymousId to server-generated ID if missing
        $resolvedAnonymousId = $anonymousId ?: ($userId ? 'user_' . $userId : 'srv_' . bin2hex(random_bytes(8)));

        // Extract client IP and user agent from superglobals if available
        $inferredContext = [];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $inferredContext['ip_address'] = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $inferredContext['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $inferredContext['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        $mergedContext = array_merge($this->defaultContext, $inferredContext, $context);

        return [
            'event_id' => $this->generateUuidV4(),
            'event_name' => $eventName,
            'timestamp' => gmdate('Y-m-d\TH:i:s.v\Z'),
            'source' => 'server',
            'anonymous_id' => $resolvedAnonymousId,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'properties' => $properties,
            'context' => $mergedContext,
        ];
    }

    private function dispatch(array $payload): bool
    {
        try {
            return $this->transport->send($payload);
        } catch (\Throwable $e) {
            if ($this->debug) {
                error_log('[Analytics Sdk] Transport exception: ' . $e->getMessage());
            }
            return false;
        }
    }

    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

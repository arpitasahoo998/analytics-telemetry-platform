<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Str;

class ServerAnalytics
{
    /**
     * Record a verified business event from the server.
     */
    public static function track(
        string $eventName,
        array $properties = [],
        ?string $userId = null,
        ?string $anonymousId = null,
        ?string $sessionId = null,
        array $context = []
    ): bool {
        try {
            $eventId = (string)Str::uuid();
            $timestamp = gmdate('Y-m-d\TH:i:s.v\Z');
            $epochMs = (int)(microtime(true) * 1000);

            $resolvedAnon = $anonymousId ?: ($userId ? 'user_' . $userId : 'srv_' . bin2hex(random_bytes(6)));

            static $cachedHost = null;
            if ($cachedHost === null) {
                $cachedHost = php_uname('n') ?: 'localhost';
            }

            $defaultContext = [
                'sdk_name' => 'analytics-laravel-sdk',
                'sdk_version' => '1.0.0',
                'framework' => 'Laravel ' . app()->version(),
                'runtime' => 'PHP ' . PHP_VERSION,
                'server_hostname' => $cachedHost,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'ServerSideClient/1.0',
            ];

            $mergedContext = array_merge($defaultContext, $context);

            Event::insertOrIgnore([
                'event_id' => $eventId,
                'event_name' => $eventName,
                'timestamp' => $timestamp,
                'timestamp_epoch' => $epochMs,
                'source' => 'server',
                'anonymous_id' => $resolvedAnon,
                'session_id' => $sessionId,
                'user_id' => $userId,
                'properties' => json_encode($properties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'context' => json_encode($mergedContext, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            \Log::error('[ServerAnalytics] Failed to track event: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Record an identity stitching event from the server.
     */
    public static function identify(
        string $userId,
        array $traits = [],
        ?string $anonymousId = null,
        ?string $sessionId = null
    ): bool {
        return self::track(
            eventName: 'identify',
            properties: ['traits' => $traits],
            userId: $userId,
            anonymousId: $anonymousId,
            sessionId: $sessionId
        );
    }
}

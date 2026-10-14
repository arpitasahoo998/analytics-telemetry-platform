<?php

declare(strict_types=1);

namespace Analytics\Repositories;

use mysqli;
use Analytics\Database\Connection;

class EventRepository
{
    private mysqli $db;

    public function __construct(?mysqli $db = null)
    {
        $this->db = $db ?? Connection::get();
    }

    /**
     * Batch insert validated events with idempotency and transaction safety.
     *
     * @param array<int, array> $events
     * @return int Count of events successfully inserted (excluding duplicates)
     */
    public function insertBatch(array $events): int
    {
        if (empty($events)) {
            return 0;
        }

        $stmt = $this->db->prepare('
            INSERT IGNORE INTO events (
                event_id,
                event_name,
                timestamp,
                timestamp_epoch,
                source,
                anonymous_id,
                session_id,
                user_id,
                properties,
                context
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $insertedCount = 0;
        $this->db->begin_transaction();

        try {
            foreach ($events as $event) {
                $eventId = (string)$event['event_id'];
                $eventName = (string)$event['event_name'];
                $timestampStr = (string)($event['timestamp'] ?? gmdate('Y-m-d\TH:i:s.v\Z'));
                $epochMs = $this->parseTimestampToEpochMs($timestampStr);
                $source = (string)($event['source'] ?? 'browser');
                $anonymousId = (string)$event['anonymous_id'];
                $sessionId = !empty($event['session_id']) ? (string)$event['session_id'] : null;
                $userId = !empty($event['user_id']) ? (string)$event['user_id'] : null;

                $propertiesJson = is_array($event['properties'] ?? null)
                    ? json_encode($event['properties'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : (string)($event['properties'] ?? '{}');

                $contextJson = is_array($event['context'] ?? null)
                    ? json_encode($event['context'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : (string)($event['context'] ?? '{}');

                $stmt->bind_param(
                    'sssissssss',
                    $eventId,
                    $eventName,
                    $timestampStr,
                    $epochMs,
                    $source,
                    $anonymousId,
                    $sessionId,
                    $userId,
                    $propertiesJson,
                    $contextJson
                );

                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $insertedCount++;
                }
            }

            $this->db->commit();
            $stmt->close();
        } catch (\Throwable $e) {
            $this->db->rollback();
            $stmt->close();
            throw $e;
        }

        return $insertedCount;
    }

    /**
     * Parse timestamp string (ISO-8601 or unix ms) to epoch milliseconds.
     */
    public function parseTimestampToEpochMs(string|int|float $timestamp): int
    {
        if (is_numeric($timestamp)) {
            $num = (int)$timestamp;
            if ($num > 100000000000) {
                return $num;
            }
            return $num * 1000;
        }

        $time = strtotime((string)$timestamp);
        if ($time === false) {
            return (int)(microtime(true) * 1000);
        }

        if (preg_match('/\.(\d{1,3})/', (string)$timestamp, $matches)) {
            $ms = (int)str_pad($matches[1], 3, '0', STR_PAD_RIGHT);
            return ($time * 1000) + $ms;
        }

        return $time * 1000;
    }

    /**
     * Retrieve recent events for real-time inspection.
     */
    public function getRecentEvents(int $limit = 50, ?string $source = null, ?string $eventName = null): array
    {
        $limit = max(1, min($limit, 200));
        $whereClauses = [];
        $params = [];
        $types = '';

        if ($source !== null && $source !== '') {
            $whereClauses[] = 'source = ?';
            $params[] = $source;
            $types .= 's';
        }

        if ($eventName !== null && $eventName !== '') {
            $whereClauses[] = 'event_name = ?';
            $params[] = $eventName;
            $types .= 's';
        }

        $sql = 'SELECT id, event_id, event_name, timestamp, timestamp_epoch, source, anonymous_id, session_id, user_id, properties, context, created_at FROM events';
        if (!empty($whereClauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }
        $sql .= ' ORDER BY timestamp_epoch DESC, id DESC LIMIT ?';
        $params[] = $limit;
        $types .= 'i';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_map(function ($row) {
            $row['properties'] = json_decode($row['properties'] ?? '{}', true) ?: [];
            $row['context'] = json_decode($row['context'] ?? '{}', true) ?: [];
            return $row;
        }, $rows);
    }
}

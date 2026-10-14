<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventIngestionController extends Controller
{
    public function ingest(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            return response()->json(['status' => 'error', 'message' => 'Empty or invalid JSON body'], 400);
        }

        // Support single event or batch format
        $eventsList = [];
        if (isset($payload['events']) && is_array($payload['events'])) {
            $eventsList = $payload['events'];
        } elseif (array_is_list($payload) && !empty($payload) && is_array($payload[0])) {
            $eventsList = $payload;
        } else {
            $eventsList = [$payload];
        }

        $recordsToInsert = [];
        $errors = [];
        $accepted = 0;

        foreach ($eventsList as $idx => $evt) {
            if (!is_array($evt)) {
                $errors[] = ['index' => $idx, 'error' => 'Event must be a JSON object'];
                continue;
            }

            $eventName = trim((string)($evt['event_name'] ?? ''));
            $anonId = trim((string)($evt['anonymous_id'] ?? ''));

            if ($eventName === '' || $anonId === '') {
                $errors[] = ['index' => $idx, 'error' => 'event_name and anonymous_id are required'];
                continue;
            }

            $eventId = trim((string)($evt['event_id'] ?? '')) ?: (string)Str::uuid();
            $timestamp = (string)($evt['timestamp'] ?? gmdate('Y-m-d\TH:i:s.v\Z'));

            $time = strtotime($timestamp);
            $epochMs = $time !== false ? ($time * 1000) : (int)(microtime(true) * 1000);
            if (preg_match('/\.(\d{1,3})/', $timestamp, $m)) {
                $epochMs = ($time !== false ? $time * 1000 : 0) + (int)str_pad($m[1], 3, '0', STR_PAD_RIGHT);
            }

            $source = in_array($evt['source'] ?? '', ['browser', 'server'], true) ? $evt['source'] : 'browser';
            $properties = is_array($evt['properties'] ?? null) ? $evt['properties'] : [];
            $context = is_array($evt['context'] ?? null) ? $evt['context'] : [];

            if (empty($context['ip_address'])) {
                $context['ip_address'] = $request->ip() ?: '127.0.0.1';
            }
            if (empty($context['user_agent'])) {
                $context['user_agent'] = $request->userAgent() ?: 'Unknown';
            }

            $recordsToInsert[] = [
                'event_id' => $eventId,
                'event_name' => $eventName,
                'timestamp' => $timestamp,
                'timestamp_epoch' => $epochMs,
                'source' => $source,
                'anonymous_id' => $anonId,
                'session_id' => !empty($evt['session_id']) ? (string)$evt['session_id'] : null,
                'user_id' => !empty($evt['user_id']) ? (string)$evt['user_id'] : null,
                'properties' => json_encode($properties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'context' => json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $accepted++;
        }

        if ($accepted === 0 && !empty($errors)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed: event_name and anonymous_id are required',
                'errors' => $errors,
            ], 422);
        }

        $inserted = 0;
        if (!empty($recordsToInsert)) {
            // Batch insert with duplicate ignore for idempotency
            $inserted = Event::insertOrIgnore($recordsToInsert);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'received' => count($eventsList),
                'accepted' => $accepted,
                'inserted' => $inserted,
                'errors' => $errors,
            ]
        ], 201)->header('Connection', 'close');
    }
}

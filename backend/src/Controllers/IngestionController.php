<?php

declare(strict_types=1);

namespace Analytics\Controllers;

use Analytics\Http\Request;
use Analytics\Http\Response;
use Analytics\Services\IngestionService;

class IngestionController
{
    private IngestionService $service;

    public function __construct(?IngestionService $service = null)
    {
        $this->service = $service ?? new IngestionService();
    }

    public function handle(Request $request): Response
    {
        $body = $request->getBody();

        if (empty($body)) {
            return Response::error('Empty or invalid JSON payload', 400);
        }

        try {
            $result = $this->service->processPayload($body, $request);

            if (!$result['success']) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'Validation failed for submitted events',
                    'errors' => $result['errors'],
                ], 422);
            }

            return Response::json([
                'status' => 'success',
                'data' => [
                    'received' => $result['received'],
                    'accepted' => $result['accepted'],
                    'inserted' => $result['inserted'],
                    'errors' => $result['errors'],
                ],
            ], 201);
        } catch (\Throwable $e) {
            return Response::error('Internal server error during ingestion: ' . $e->getMessage(), 500);
        }
    }
}

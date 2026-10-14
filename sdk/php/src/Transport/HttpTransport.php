<?php

declare(strict_types=1);

namespace Analytics\Sdk\Transport;

class HttpTransport implements TransportInterface
{
    private string $endpoint;
    private float $timeout;
    private float $connectTimeout;
    private bool $debug;

    public function __construct(
        string $endpoint,
        float $timeout = 2.0,
        float $connectTimeout = 1.0,
        bool $debug = false
    ) {
        $this->endpoint = $endpoint;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->debug = $debug;
    }

    public function send(array $payload): bool
    {
        $json = json_encode($payload);
        if ($json === false) {
            if ($this->debug) {
                error_log('[Analytics Sdk] Failed to JSON-encode payload: ' . json_last_error_msg());
            }
            return false;
        }

        if (function_exists('curl_init')) {
            return $this->sendViaCurl($json);
        }

        return $this->sendViaStream($json);
    }

    private function sendViaCurl(string $json): bool
    {
        $ch = curl_init($this->endpoint);
        if ($ch === false) {
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: analytics-php-sdk/1.0.0',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => (int)($this->timeout * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int)($this->connectTimeout * 1000),
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            if ($this->debug) {
                error_log("[Analytics Sdk] Ingestion request failed. HTTP: {$httpCode}, Error: {$curlError}");
            }
            return false;
        }

        return true;
    }

    private function sendViaStream(string $json): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nUser-Agent: analytics-php-sdk/1.0.0\r\n",
                'content' => $json,
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ],
        ]);

        $fp = @fopen($this->endpoint, 'r', false, $context);
        if (!$fp) {
            if ($this->debug) {
                error_log('[Analytics Sdk] Stream connection failed to endpoint: ' . $this->endpoint);
            }
            return false;
        }

        $meta = stream_get_meta_data($fp);
        fclose($fp);

        return !empty($meta['wrapper_data']);
    }
}

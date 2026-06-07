<?php

declare(strict_types=1);

final class Mailer
{
    public static function mode(): string
    {
        $mode = strtolower(trim((string) env_value('MAIL_MODE', 'log')));
        return in_array($mode, ['log', 'resend', 'disabled'], true) ? $mode : 'log';
    }

    public static function isConfigured(): bool
    {
        if (self::mode() === 'log') {
            return true;
        }

        return self::mode() === 'resend'
            && trim((string) env_value('RESEND_API_KEY', '')) !== ''
            && trim((string) env_value('MAIL_FROM', '')) !== '';
    }

    public static function send(string $to, string $subject, string $html, string $text = ''): bool
    {
        $to = strtolower(trim($to));
        $subject = mb_substr(trim($subject), 0, 180);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || $subject === '') {
            return false;
        }

        if (self::mode() === 'log') {
            return self::writeLog($to, $subject, $html, $text);
        }

        if (self::mode() !== 'resend' || !self::isConfigured()) {
            return false;
        }

        $payload = [
            'from' => (string) env_value('MAIL_FROM', ''),
            'to' => [$to],
            'subject' => $subject,
            'html' => $html,
            'text' => $text !== '' ? $text : strip_tags($html),
        ];

        try {
            return self::postJson('https://api.resend.com/emails', $payload);
        } catch (Throwable) {
            return false;
        }
    }

    public static function loggedMessages(int $limit = 20): array
    {
        $path = self::logPath();
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $messages = [];
        foreach (array_reverse(array_slice($lines, -max(1, min($limit, 100)))) as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $messages[] = $decoded;
            }
        }
        return $messages;
    }

    private static function writeLog(string $to, string $subject, string $html, string $text): bool
    {
        $dir = dirname(__DIR__) . '/storage';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        $record = [
            'created_at' => gmdate('c'),
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
            'text' => $text !== '' ? $text : strip_tags($html),
        ];
        $encoded = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($encoded) && file_put_contents(self::logPath(), $encoded . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
    }

    private static function postJson(string $url, array $payload): bool
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return false;
        }

        $apiKey = trim((string) env_value('RESEND_API_KEY', ''));
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl === false) {
                return false;
            }
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 12,
            ]);
            curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            return $status >= 200 && $status < 300;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer {$apiKey}\r\nContent-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $json,
                'timeout' => 12,
                'ignore_errors' => true,
            ],
        ]);
        @file_get_contents($url, false, $context);
        foreach (($http_response_header ?? []) as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
                $status = (int) $matches[1];
                return $status >= 200 && $status < 300;
            }
        }
        return false;
    }

    private static function logPath(): string
    {
        return dirname(__DIR__) . '/storage/mail.log';
    }
}

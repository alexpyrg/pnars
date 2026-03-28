<?php

declare(strict_types=1);

namespace App\Core\Support;

final class SmtpMailer
{
    /** @var array<string, mixed> */
    private array $config;
    private ?string $lastError = null;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function send(string $toEmail, string $subject, string $textBody, ?string $toName = null): bool
    {
        $this->lastError = null;

        if (!$this->isEnabled()) {
            $this->lastError = 'Η αποστολή email είναι απενεργοποιημένη.';
            return false;
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Μη έγκυρη διεύθυνση παραλήπτη.';
            return false;
        }

        $host = (string) ($this->config['host'] ?? '127.0.0.1');
        $port = (int) ($this->config['port'] ?? 25);
        $timeout = max(1, (int) ($this->config['timeout'] ?? 10));
        $encryption = strtolower((string) ($this->config['encryption'] ?? 'none'));
        $username = trim((string) ($this->config['username'] ?? ''));
        $password = (string) ($this->config['password'] ?? '');
        $fromAddress = trim((string) ($this->config['from_address'] ?? ''));
        $fromName = trim((string) ($this->config['from_name'] ?? ''));
        $helo = trim((string) ($this->config['helo'] ?? 'localhost'));

        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Μη έγκυρη διεύθυνση αποστολέα.';
            return false;
        }

        $transportHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @stream_socket_client(
            $transportHost . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!is_resource($socket)) {
            $this->lastError = 'Αδυναμία σύνδεσης στον SMTP server: ' . $errstr;
            return false;
        }

        stream_set_timeout($socket, $timeout);

        try {
            $this->assertResponseCode($this->readResponse($socket), [220]);

            $this->writeCommand($socket, 'EHLO ' . $helo);
            $ehloResponse = $this->readResponse($socket);

            if (!$this->responseHasCode($ehloResponse, [250])) {
                $this->writeCommand($socket, 'HELO ' . $helo);
                $this->assertResponseCode($this->readResponse($socket), [250]);
            }

            if ($encryption === 'tls') {
                $this->writeCommand($socket, 'STARTTLS');
                $this->assertResponseCode($this->readResponse($socket), [220]);

                $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoEnabled !== true) {
                    throw new \RuntimeException('Αποτυχία ενεργοποίησης TLS.');
                }

                $this->writeCommand($socket, 'EHLO ' . $helo);
                $this->assertResponseCode($this->readResponse($socket), [250]);
            }

            if ($username !== '') {
                $this->writeCommand($socket, 'AUTH LOGIN');
                $this->assertResponseCode($this->readResponse($socket), [334]);

                $this->writeCommand($socket, base64_encode($username));
                $this->assertResponseCode($this->readResponse($socket), [334]);

                $this->writeCommand($socket, base64_encode($password));
                $this->assertResponseCode($this->readResponse($socket), [235]);
            }

            $this->writeCommand($socket, 'MAIL FROM:<' . $fromAddress . '>');
            $this->assertResponseCode($this->readResponse($socket), [250]);

            $this->writeCommand($socket, 'RCPT TO:<' . $toEmail . '>');
            $this->assertResponseCode($this->readResponse($socket), [250, 251]);

            $this->writeCommand($socket, 'DATA');
            $this->assertResponseCode($this->readResponse($socket), [354]);

            $fromHeader = $this->formatAddressHeader($fromAddress, $fromName);
            $toHeader = $this->formatAddressHeader($toEmail, $toName ?? '');
            $subjectHeader = $this->encodeHeaderValue($subject);

            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $fromHeader,
                'To: ' . $toHeader,
                'Subject: ' . $subjectHeader,
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];

            $body = str_replace(["\r\n", "\r"], "\n", $textBody);
            $body = str_replace("\n", "\r\n", $body);

            $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $data = preg_replace('/(^|\r\n)\./', '$1..', $data) ?? $data;

            fwrite($socket, $data . "\r\n.\r\n");
            $this->assertResponseCode($this->readResponse($socket), [250]);

            $this->writeCommand($socket, 'QUIT');
            $this->readResponse($socket);

            fclose($socket);
            return true;
        } catch (\Throwable $e) {
            fclose($socket);
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /** @param resource $socket */
    private function readResponse($socket): string
    {
        $response = '';

        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }

            $response .= $line;

            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            throw new \RuntimeException('Κενή απόκριση από SMTP server.');
        }

        return $response;
    }

    /** @param resource $socket */
    private function writeCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    /** @param int[] $allowedCodes */
    private function assertResponseCode(string $response, array $allowedCodes): void
    {
        if (!$this->responseHasCode($response, $allowedCodes)) {
            throw new \RuntimeException('SMTP σφάλμα: ' . trim($response));
        }
    }

    /** @param int[] $allowedCodes */
    private function responseHasCode(string $response, array $allowedCodes): bool
    {
        $code = (int) substr($response, 0, 3);

        return in_array($code, $allowedCodes, true);
    }

    private function encodeHeaderValue(string $value): string
    {
        $clean = str_replace(["\r", "\n"], '', $value);

        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($clean, 'UTF-8', 'B', "\r\n");
        }

        return '=?UTF-8?B?' . base64_encode($clean) . '?=';
    }

    private function formatAddressHeader(string $email, string $name): string
    {
        $cleanEmail = trim(str_replace(["\r", "\n"], '', $email));
        $cleanName = trim(str_replace(["\r", "\n"], '', $name));

        if ($cleanName === '') {
            return '<' . $cleanEmail . '>';
        }

        return $this->encodeHeaderValue($cleanName) . ' <' . $cleanEmail . '>';
    }
}
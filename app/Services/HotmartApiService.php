<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use RuntimeException;

class HotmartApiService
{
    private ?string $token = null;
    private int $expiresAt = 0;

    /** Retorna uma página; use page_info.next_page_token para continuar. */
    public function sales(array $filters = []): array
    {
        $allowed = ['transaction', 'transaction_status', 'product_id', 'buyer_email', 'start_date', 'end_date', 'max_results', 'page_token'];
        if (array_diff(array_keys($filters), $allowed) !== []) {
            throw new \InvalidArgumentException('Filtro Hotmart inválido.');
        }
        $environment = $_ENV['HOTMART_API_ENV'] ?? 'production';
        if (!in_array($environment, ['production', 'sandbox'], true)) {
            throw new RuntimeException('HOTMART_API_ENV deve ser production ou sandbox.');
        }
        $host = $environment === 'sandbox' ? 'https://sandbox.hotmart.com' : 'https://developers.hotmart.com';
        $url = $host . '/payments/api/v1/sales/history?' . http_build_query($filters);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            [$status, $body] = $this->request($url, ['Authorization: Bearer ' . $this->accessToken()]);
            if ($status === 401 && $attempt === 0) {
                $this->token = null;
                continue;
            }
            return $this->decode($status, $body);
        }
        throw new RuntimeException('Falha na autenticação Hotmart.');
    }

    private function accessToken(): string
    {
        if ($this->token !== null && time() < $this->expiresAt) {
            return $this->token;
        }
        $id = trim($_ENV['HOTMART_CLIENT_ID'] ?? '');
        $secret = trim($_ENV['HOTMART_CLIENT_SECRET'] ?? '');
        $basic = trim($_ENV['HOTMART_BASIC'] ?? '');
        $basic = preg_replace('/^Basic\s+/i', '', $basic);
        if ($id === '' || $secret === '' || $basic === '' || preg_match('/[\r\n]/', $basic)) {
            throw new RuntimeException('Configure HOTMART_CLIENT_ID, HOTMART_CLIENT_SECRET e HOTMART_BASIC.');
        }
        [$status, $body] = $this->request('https://api-sec-vlc.hotmart.com/security/oauth/token',
            ['Authorization: Basic ' . $basic, 'Content-Type: application/x-www-form-urlencoded'],
            http_build_query(['grant_type' => 'client_credentials', 'client_id' => $id, 'client_secret' => $secret]));
        $data = $this->decode($status, $body);
        if (!is_string($data['access_token'] ?? null) || $data['access_token'] === '') {
            throw new RuntimeException('Resposta OAuth Hotmart inválida.');
        }
        $this->token = $data['access_token'];
        $this->expiresAt = time() + max(0, (int) ($data['expires_in'] ?? 0) - 60);
        return $this->token;
    }

    private function request(string $url, array $headers, ?string $post = null): array
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('Habilite a extensão PHP cURL.');
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers)]);
        if ($post !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($body === false) {
            throw new RuntimeException('Falha de conexão com Hotmart.');
        }
        return [$status, $body];
    }

    private function decode(int $status, string $body): array
    {
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Hotmart respondeu HTTP ' . $status . '.');
        }
        try {
            $data = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new RuntimeException('Resposta JSON Hotmart inválida.');
        }
        if (!is_array($data)) {
            throw new RuntimeException('Resposta Hotmart inválida.');
        }
        return $data;
    }
}

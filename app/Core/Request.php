<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Core;

class Request
{
    private ?array $jsonData = null;

    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        $json = $this->json();

        return $json[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($_POST, $this->json());
    }

    private function json(): array
    {
        if ($this->jsonData !== null) {
            return $this->jsonData;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (
            stripos(
                $contentType,
                'application/json'
            ) === false
        ) {
            $this->jsonData = [];

            return $this->jsonData;
        }

        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            $this->jsonData = [];

            return $this->jsonData;
        }

        $data = json_decode($raw, true);

        if (!is_array($data)) {
            $this->jsonData = [];

            return $this->jsonData;
        }

        $this->jsonData = $data;

        return $this->jsonData;
    }
}
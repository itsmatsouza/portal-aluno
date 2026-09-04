<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Core;

class SessionManager
{
    private const SESSION_LIFETIME = 3600; // 1 hora

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !self::isLocal(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function regenerate(): void
    {
        self::start();

        session_regenerate_id(true);
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();

        $_SESSION[$key] = $value;
    }

    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        self::start();

        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();

        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();

        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => 'Lax',
                ]
            );
        }

        session_destroy();
    }

    public static function isAuthenticated(): bool
    {
        return self::get('user_id') !== null;
    }

    private static function isLocal(): bool
    {
        $environment = $_ENV['APP_ENV'] ?? 'production';

        return $environment === 'local';
    }
}

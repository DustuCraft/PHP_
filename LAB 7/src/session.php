<?php
declare(strict_types=1);

/**
 * Единая точка настройки и запуска сессии.
 * Подключается первой строкой каждой страницы, до любого вывода.
 */

const IDLE_TIMEOUT = 900;                       // 15 минут бездействия
const SESSION_COOKIE_NAME = 'DORMSESSION';

function isHttps(): bool
{
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');

session_name(SESSION_COOKIE_NAME);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isHttps(),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require __DIR__ . '/i18n.php';
require __DIR__ . '/data.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function loginUser(int $id, string $email, string $role): void
{
    session_regenerate_id(true);          // защита от фиксации сессии
    unset($_SESSION['login_csrf']);
    $_SESSION['user_id']       = $id;
    $_SESSION['email']         = $email;
    $_SESSION['role']          = $role;
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token']    = bin2hex(random_bytes(32));
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function requireLogin(): void
{
    header('Cache-Control: no-store');    // страницы кабинета не кэшируются

    $userId       = $_SESSION['user_id'] ?? null;
    $lastActivity = $_SESSION['last_activity'] ?? null;

    if (!is_int($userId) || !is_int($lastActivity)) {
        redirect('/login.php');
    }
    if (time() - $lastActivity > IDLE_TIMEOUT) {
        logoutUser();
        redirect('/login.php?expired=1');
    }
    $_SESSION['last_activity'] = time();
}

/** Проверка входа и роли. Роль берётся только из серверной сессии. */
function requireRole(string $role): void
{
    requireLogin();
    if (($_SESSION['role'] ?? null) !== $role) {
        http_response_code(403);
        exit(h(t('forbidden')));
    }
}

function csrfValid(string $sessionKey = 'csrf_token'): bool
{
    $token = $_POST['csrf_token'] ?? null;
    $known = $_SESSION[$sessionKey] ?? null;
    return is_string($token) && is_string($known) && hash_equals($known, $token);
}

function denyCsrf(): never
{
    http_response_code(403);
    exit('Invalid CSRF token');
}

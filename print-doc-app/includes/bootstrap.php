<?php
declare(strict_types=1);

$scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/print-doc-app/index.php'));
$segments = explode('/', trim($scriptPath, '/'));
$appPosition = array_search('print-doc-app', $segments, true);
$appUrl = $appPosition === false
    ? '/print-doc-app'
    : '/' . implode('/', array_slice($segments, 0, $appPosition + 1));
define('APP_URL', $appUrl);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pricing.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    return (string) $_SESSION['csrf_token'];
}

function valid_csrf(mixed $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function current_user(): ?array
{
    global $pdo;
    static $loaded = false;
    static $user = null;
    if ($loaded) {
        return $user;
    }

    $loaded = true;
    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    if ($userId) {
        $statement = $pdo->prepare('SELECT id, name, email, role, signature_path FROM print_doc_users WHERE id = ?');
        $statement->execute([$userId]);
        $user = $statement->fetch() ?: null;
        if ($user === null) {
            unset($_SESSION['user_id']);
        }
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
    return $user;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function app_settings(): array
{
    global $pdo;
    $settings = [];
    foreach ($pdo->query('SELECT setting_key, setting_value FROM print_doc_settings') as $row) {
        $settings[$row['setting_key']] = (float) $row['setting_value'];
    }
    return $settings;
}

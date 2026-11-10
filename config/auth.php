<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
session_name(SESSION_NAME);
session_start();

function require_admin(array $roles = []): void
{
    if (empty($_SESSION['admin_id'])) {
        json_response(['error' => 'Authentication required.'], 401);
    }
    if ($roles !== [] && !in_array($_SESSION['admin_role'] ?? '', $roles, true)) {
        json_response(['error' => 'Insufficient permissions.'], 403);
    }
}

function admin_has_role(string|array $roles): bool
{
    if (empty($_SESSION['admin_id'])) {
        return false;
    }
    $roles = is_array($roles) ? $roles : [$roles];
    return in_array($_SESSION['admin_role'] ?? '', $roles, true);
}

function current_admin(): array
{
    require_admin();
    return [
        'id' => (int)$_SESSION['admin_id'],
        'email' => (string)($_SESSION['admin_email'] ?? ''),
        'role' => (string)($_SESSION['admin_role'] ?? 'owner'),
    ];
}

function audit_log(string $action, ?string $entityType = null, ?int $entityId = null, array $details = []): void
{
    $statement = db()->prepare(
        'INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $_SESSION['admin_id'] ?? null,
        $action,
        $entityType,
        $entityId,
        $details === [] ? null : json_encode($details, JSON_UNESCAPED_SLASHES),
    ]);
}

function issue_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

<?php
declare(strict_types=1);

const APP_NAME = 'Mambo ya Tanga';
const DB_PATH = __DIR__ . '/../data/mambo.sqlite';
const SESSION_NAME = 'mambo_admin_session';

function env_value(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false ? $default : trim($value);
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cache-Control: no-store');

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

set_exception_handler(static function (Throwable $error): void {
    error_log($error->getMessage());
    json_response(['error' => 'An unexpected server error occurred.'], 500);
});

function request_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response(['error' => 'Request body must be valid JSON.'], 400);
    }
    return $data;
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);
        json_response(['error' => 'Method not allowed.'], 405);
    }

}

function clean_text(mixed $value, int $maxLength = 255): string
{
    $value = trim((string)$value);
    if ($value === '' || mb_strlen($value) > $maxLength) {
        json_response(['error' => 'Please provide valid text within the allowed length.'], 422);
    }
    return $value;
}

function clean_phone(mixed $value): string
{
    $phone = preg_replace('/[\s().-]+/', '', trim((string)$value));
    if ($phone === null || !preg_match('/^\+?[0-9]{9,15}$/', $phone)) {
        json_response(['error' => 'Enter a valid phone number using 9 to 15 digits.'], 422);
    }
    return $phone;
}

function clean_email(mixed $value, bool $required = false): string
{
    $email = strtolower(trim((string)$value));
    if ($email === '' && !$required) return '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
        json_response(['error' => 'Enter a valid email address.'], 422);
    }
    return $email;
}

function request_origin_is_trusted(): bool
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    if ($origin === '') return true; // Non-browser clients retain API compatibility.
    $originHost = parse_url($origin, PHP_URL_HOST);
    $requestHost = $_SERVER['HTTP_HOST'] ?? '';
    return is_string($originHost) && hash_equals(strtolower((string)$requestHost), strtolower($originHost . (parse_url($origin, PHP_URL_PORT) ? ':' . parse_url($origin, PHP_URL_PORT) : '')));
}

function require_csrf(): void
{
    if (!request_origin_is_trusted()) json_response(['error' => 'Cross-site request blocked.'], 403);
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['csrf_token'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if ($token !== '' && !hash_equals($_SESSION['csrf_token'], $token)) {
            json_response(['error' => 'Invalid CSRF token.'], 403);
        }
    }
}

function enforce_rate_limit(PDO $pdo, string $bucket, int $limit, int $windowSeconds = 60): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = hash('sha256', $bucket . '|' . $ip);
    $now = time();
    $statement = $pdo->prepare('SELECT window_started, request_count FROM api_rate_limits WHERE rate_key = ?');
    $statement->execute([$key]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$row || $now - (int)$row['window_started'] >= $windowSeconds) {
        $upsert = $pdo->prepare('INSERT INTO api_rate_limits (rate_key,window_started,request_count) VALUES (?,?,1) ON CONFLICT(rate_key) DO UPDATE SET window_started=excluded.window_started, request_count=1');
        $upsert->execute([$key, $now]);
        return;
    }
    if ((int)$row['request_count'] >= $limit) {
        header('Retry-After: ' . max(1, $windowSeconds - ($now - (int)$row['window_started'])));
        json_response(['error' => 'Too many requests. Please try again shortly.'], 429);
    }
    $pdo->prepare('UPDATE api_rate_limits SET request_count = request_count + 1 WHERE rate_key = ?')->execute([$key]);
}

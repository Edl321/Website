<?php
// security/harden.php
// Central session hardening — must run BEFORE anything else.

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// ---- Idle timeout (30 minutes) ----
$idleLimit = 1800;

if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $idleLimit) {

    session_unset();
    session_destroy();
    session_start();
}

$_SESSION['last_activity'] = time();

// ---- Session fingerprint (detect hijack) ----
// NOTE: We only use the User-Agent here, NOT the IP address.
// An IP can legitimately change mid-session (mobile networks, VPNs,
// load balancers) and using it causes users to be logged out randomly.
$fingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

if (!isset($_SESSION['fingerprint'])) {
    $_SESSION['fingerprint'] = $fingerprint;
} elseif ($_SESSION['fingerprint'] !== $fingerprint) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['fingerprint'] = $fingerprint;
}

// ---- Security headers ----
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// ---- Error handling ----
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php-errors.log');

set_exception_handler(function ($e) {
    error_log('[UNCAUGHT] ' . $e->getMessage() .
            ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    exit('Something went wrong. Please try again.');
});

// ---- Rate limit helper ----
function rateLimitExceeded(string $key, int $max = 5, int $window = 300): bool
{
    $bucket = $_SESSION['ratelimit'][$key] ?? ['count' => 0, 'start' => time()];

    if (time() - $bucket['start'] > $window) {
        $_SESSION['ratelimit'][$key] = ['count' => 1, 'start' => time()];
        return false;
    }

    $bucket['count']++;
    $_SESSION['ratelimit'][$key] = $bucket;

    return $bucket['count'] > $max;
}

function rateLimitReset(string $key): void
{
    unset($_SESSION['ratelimit'][$key]);
}

// ---- Security event logging ----
function securityLog(string $event, array $details = []): void
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $line = sprintf(
        "[%s] %s | user=%s | ip=%s | ua=%s | %s\n",
        date('Y-m-d H:i:s'),
        $event,
        $_SESSION['user_id'] ?? 'guest',
        $_SERVER['REMOTE_ADDR'] ?? '-',
        substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 80),
        json_encode($details)
    );

    @file_put_contents($logDir . '/security.log', $line, FILE_APPEND | LOCK_EX);
}
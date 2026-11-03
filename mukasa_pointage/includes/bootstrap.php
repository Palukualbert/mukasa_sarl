<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
date_default_timezone_set('Africa/Kinshasa'); // Heure locale de Kinshasa (UTC+1). Pour Lubumbashi, utiliser Africa/Lubumbashi.
require_once __DIR__ . '/../config/database.php';

function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function is_admin(): bool { return isset($_SESSION['admin_id']); }
function require_admin(): void {
    if (!is_admin()) { header('Location: login.php'); exit; }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Session expirée. Revenez en arrière et réessayez.');
    }
}
function flash(string $type, string $message): void { $_SESSION['flash'] = [$type, $message]; }
function render_flash(): void {
    if (!empty($_SESSION['flash'])) {
        [$type, $message] = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert alert-'.e($type).' alert-dismissible fade show" role="alert">'.e($message).'<button class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

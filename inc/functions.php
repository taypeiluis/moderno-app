<?php
// inc/functions.php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * e() - escapar para HTML
 */
function e($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * csrf_token() / check_csrf()
 * (opcionales, se pueden usar para formularios críticos)
 */
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function check_csrf($token) {
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

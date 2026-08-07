<?php
/**
 * Shared authentication guard.
 * Include this at the top of every protected PHP page.
 * For API endpoints that return JSON, use the inline session check instead.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . str_repeat('../', substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__), '/') - 1) . 'frontend/login.php');
    exit();
}

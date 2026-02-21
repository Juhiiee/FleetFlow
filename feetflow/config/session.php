<?php
/**
 * FleetFlow – Session Management & Auth Guard
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the user is logged in, redirect to login if not
 */
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /feetflow/auth/login.php');
        exit;
    }
}

/**
 * Check if the current user has one of the allowed roles
 */
function requireRole(array $allowedRoles): void {
    requireLogin();
    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><title>Access Denied</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
        <body class="bg-dark text-white d-flex align-items-center justify-content-center" style="min-height:100vh">
        <div class="text-center"><h1 class="display-1">403</h1><p class="lead">Access Denied – Insufficient Permissions</p>
        <a href="/feetflow/dashboard/" class="btn btn-outline-light mt-3">Back to Dashboard</a></div></body></html>';
        exit;
    }
}

/**
 * Check if user is logged in (returns bool)
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Get the current user's role
 */
function currentRole(): string {
    return $_SESSION['role'] ?? '';
}

/**
 * Get the current user's full name
 */
function currentUserName(): string {
    return $_SESSION['full_name'] ?? 'Guest';
}

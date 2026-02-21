<?php
/**
 * FleetFlow – Utility Helpers
 */

/**
 * Sanitize input string
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Format a number as currency
 */
function formatCurrency(float $amount): string {
    return '$' . number_format($amount, 2);
}

/**
 * Return Bootstrap badge HTML for vehicle status
 */
function vehicleStatusBadge(string $status): string {
    $map = [
        'Available' => 'success',
        'On Trip'   => 'warning',
        'In Shop'   => 'danger',
        'Retired'   => 'secondary',
    ];
    $cls = $map[$status] ?? 'light';
    return '<span class="badge bg-' . $cls . '">' . sanitize($status) . '</span>';
}

/**
 * Return Bootstrap badge HTML for driver status
 */
function driverStatusBadge(string $status): string {
    $map = [
        'Off Duty'  => 'info',
        'On Duty'   => 'warning',
        'Suspended' => 'danger',
    ];
    $cls = $map[$status] ?? 'light';
    return '<span class="badge bg-' . $cls . '">' . sanitize($status) . '</span>';
}

/**
 * Return Bootstrap badge HTML for trip status
 */
function tripStatusBadge(string $status): string {
    $map = [
        'Draft'      => 'secondary',
        'Dispatched' => 'primary',
        'Completed'  => 'success',
        'Cancelled'  => 'danger',
    ];
    $cls = $map[$status] ?? 'light';
    return '<span class="badge bg-' . $cls . '">' . sanitize($status) . '</span>';
}

/**
 * Flash message helpers
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message HTML
 */
function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $type = sanitize($flash['type']);
    $msg  = sanitize($flash['message']);
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
         . $msg
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

/**
 * RBAC module access map
 */
function getModuleAccess(): array {
    return [
        'Fleet Manager'    => ['dashboard','vehicles','drivers','trips','maintenance','fuel','analytics'],
        'Dispatcher'       => ['dashboard','vehicles','drivers','trips'],
        'Safety Officer'   => ['dashboard','drivers','trips','maintenance'],
        'Financial Analyst'=> ['dashboard','fuel','maintenance','analytics'],
    ];
}

/**
 * Check if current role can access a module
 */
function canAccess(string $module): bool {
    $role = currentRole();
    $access = getModuleAccess();
    return in_array($module, $access[$role] ?? [], true);
}

/**
 * Get the base URL
 */
function baseUrl(): string {
    return '/feetflow';
}

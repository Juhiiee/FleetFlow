<?php
/**
 * FleetFlow – Root Entry Point
 * Redirects to dashboard or login
 */
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    header('Location: /feetflow/dashboard/');
} else {
    header('Location: /feetflow/auth/login.php');
}
exit;

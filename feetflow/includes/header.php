<?php
/**
 * FleetFlow – Header Template
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';

$pageTitle = $pageTitle ?? 'FleetFlow';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> – FleetFlow</title>
    <meta name="description" content="FleetFlow – Modular Fleet & Logistics Management System">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="<?= baseUrl() ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark ff-navbar fixed-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white me-2 d-lg-none" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand fw-bold" href="<?= baseUrl() ?>/dashboard/">
                <i class="bi bi-truck me-2"></i>Fleet<span class="text-accent">Flow</span>
            </a>
            <div class="d-flex align-items-center ms-auto">
                <span class="badge bg-primary me-3 d-none d-md-inline-block"><?= sanitize(currentRole()) ?></span>
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= sanitize(currentUserName()) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                        <li><a class="dropdown-item" href="<?= baseUrl() ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="d-flex" id="wrapper">

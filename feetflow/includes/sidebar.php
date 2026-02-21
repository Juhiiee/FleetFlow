<?php
/**
 * FleetFlow – Sidebar Navigation (Role-Aware)
 */
$activeModule = $activeModule ?? '';
$role = currentRole();
$access = getModuleAccess();
$allowedModules = $access[$role] ?? [];

$navItems = [
    'dashboard'   => ['icon' => 'bi-speedometer2',   'label' => 'Dashboard',    'url' => baseUrl() . '/dashboard/'],
    'vehicles'    => ['icon' => 'bi-truck',           'label' => 'Vehicles',     'url' => baseUrl() . '/vehicles/'],
    'drivers'     => ['icon' => 'bi-person-badge',    'label' => 'Drivers',      'url' => baseUrl() . '/drivers/'],
    'trips'       => ['icon' => 'bi-map',             'label' => 'Trips',        'url' => baseUrl() . '/trips/'],
    'maintenance' => ['icon' => 'bi-wrench',          'label' => 'Maintenance',  'url' => baseUrl() . '/maintenance/'],
    'fuel'        => ['icon' => 'bi-fuel-pump',       'label' => 'Fuel Logs',    'url' => baseUrl() . '/fuel/'],
    'analytics'   => ['icon' => 'bi-graph-up',        'label' => 'Analytics',    'url' => baseUrl() . '/analytics/'],
];
?>
<!-- Sidebar -->
<nav class="ff-sidebar" id="sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-header d-lg-none px-3 py-2 d-flex justify-content-end">
            <button class="btn btn-link text-white" id="sidebarClose"><i class="bi bi-x-lg"></i></button>
        </div>
        <ul class="nav flex-column">
            <?php foreach ($navItems as $mod => $item): ?>
                <?php if (in_array($mod, $allowedModules, true)): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeModule === $mod ? 'active' : '' ?>" href="<?= $item['url'] ?>">
                            <i class="bi <?= $item['icon'] ?> me-2"></i>
                            <span><?= $item['label'] ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <div class="sidebar-footer mt-auto px-3 py-3 text-center">
            <small class="text-muted">&copy; 2026 FleetFlow</small>
        </div>
    </div>
</nav>

<!-- Page Content Wrapper -->
<div class="ff-content" id="pageContent">
    <div class="container-fluid py-4">
        <?= renderFlash() ?>

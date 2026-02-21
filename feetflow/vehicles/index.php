<?php
/**
 * FleetFlow – Vehicle Registry
 */
$pageTitle = 'Vehicles';
$activeModule = 'vehicles';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Dispatcher','Safety Officer']);

$db = getDB();

// ---- Fetch vehicles ----
$vehicles = $db->query("SELECT * FROM vehicles ORDER BY created_at DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-truck"></i>Vehicle Registry</h1>
    <a href="<?= baseUrl() ?>/vehicles/add.php" class="btn btn-ff">
        <i class="bi bi-plus-lg me-1"></i>Add Vehicle
    </a>
</div>

<!-- Search -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="ff-search">
        <i class="bi bi-search"></i>
        <input type="text" id="tableSearch" placeholder="Search vehicles...">
    </div>
    <span class="text-muted-light" style="font-size:0.85rem"><?= count($vehicles) ?> vehicles</span>
</div>

<div class="ff-table-wrapper">
    <div class="table-responsive">
        <table class="table ff-table" id="dataTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vehicle</th>
                    <th>License Plate</th>
                    <th>Type</th>
                    <th>Max Load (kg)</th>
                    <th>Odometer</th>
                    <th>Region</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vehicles)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No vehicles found.</td></tr>
                <?php else: ?>
                    <?php foreach ($vehicles as $i => $v): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= sanitize($v['vehicle_name']) ?></td>
                        <td><code><?= sanitize($v['license_plate']) ?></code></td>
                        <td><?= sanitize($v['vehicle_type']) ?></td>
                        <td><?= number_format($v['max_load_capacity'], 0) ?></td>
                        <td><?= number_format($v['odometer'], 0) ?> km</td>
                        <td><?= sanitize($v['region']) ?></td>
                        <td><?= vehicleStatusBadge($v['status']) ?></td>
                        <td class="action-btns">
                            <a href="<?= baseUrl() ?>/vehicles/edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-info" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="<?= baseUrl() ?>/vehicles/delete.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" data-confirm="Delete this vehicle?">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

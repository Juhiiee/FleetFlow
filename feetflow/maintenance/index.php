<?php
/**
 * FleetFlow – Maintenance Logs
 */
$pageTitle = 'Maintenance';
$activeModule = 'maintenance';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Safety Officer','Financial Analyst']);

$db = getDB();
$logs = $db->query("
    SELECT m.*, v.vehicle_name, v.license_plate
    FROM maintenance_logs m
    JOIN vehicles v ON m.vehicle_id = v.id
    ORDER BY m.service_date DESC
")->fetchAll();

// Total maintenance cost
$totalCost = $db->query("SELECT COALESCE(SUM(cost), 0) FROM maintenance_logs")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-wrench"></i>Maintenance Logs</h1>
    <div>
        <span class="badge bg-danger me-2 py-2 px-3">Total: <?= formatCurrency($totalCost) ?></span>
        <a href="<?= baseUrl() ?>/maintenance/add.php" class="btn btn-ff">
            <i class="bi bi-plus-lg me-1"></i>Add Log
        </a>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="ff-search">
        <i class="bi bi-search"></i>
        <input type="text" id="tableSearch" placeholder="Search logs...">
    </div>
    <span class="text-muted-light" style="font-size:0.85rem"><?= count($logs) ?> records</span>
</div>

<div class="ff-table-wrapper">
    <div class="table-responsive">
        <table class="table ff-table" id="dataTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vehicle</th>
                    <th>Service</th>
                    <th>Cost</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No maintenance logs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $i => $l): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= sanitize($l['vehicle_name']) ?><br><small class="text-muted-light"><?= sanitize($l['license_plate']) ?></small></td>
                        <td><?= sanitize($l['service_description']) ?></td>
                        <td class="fw-semibold text-danger"><?= formatCurrency($l['cost']) ?></td>
                        <td><?= $l['service_date'] ?></td>
                        <td class="action-btns">
                            <a href="<?= baseUrl() ?>/maintenance/delete.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Delete this log?"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

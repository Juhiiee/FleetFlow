<?php
/**
 * FleetFlow – Fuel Logs
 */
$pageTitle = 'Fuel Logs';
$activeModule = 'fuel';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Financial Analyst']);

$db = getDB();
$logs = $db->query("
    SELECT f.*, v.vehicle_name, v.license_plate
    FROM fuel_logs f
    JOIN vehicles v ON f.vehicle_id = v.id
    ORDER BY f.fuel_date DESC
")->fetchAll();

$totalFuelCost = $db->query("SELECT COALESCE(SUM(cost), 0) FROM fuel_logs")->fetchColumn();
$totalLiters   = $db->query("SELECT COALESCE(SUM(liters), 0) FROM fuel_logs")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-fuel-pump"></i>Fuel Logs</h1>
    <div>
        <span class="badge bg-warning text-dark me-2 py-2 px-3">Total: <?= formatCurrency($totalFuelCost) ?></span>
        <span class="badge bg-info me-2 py-2 px-3"><?= number_format($totalLiters) ?> L</span>
        <a href="<?= baseUrl() ?>/fuel/add.php" class="btn btn-ff">
            <i class="bi bi-plus-lg me-1"></i>Add Fuel Log
        </a>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="ff-search">
        <i class="bi bi-search"></i>
        <input type="text" id="tableSearch" placeholder="Search fuel logs...">
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
                    <th>Liters</th>
                    <th>Cost</th>
                    <th>Price/L</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No fuel logs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $i => $l): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= sanitize($l['vehicle_name']) ?><br><small class="text-muted-light"><?= sanitize($l['license_plate']) ?></small></td>
                        <td><?= number_format($l['liters'], 1) ?> L</td>
                        <td class="fw-semibold text-warning"><?= formatCurrency($l['cost']) ?></td>
                        <td><?= $l['liters'] > 0 ? formatCurrency($l['cost'] / $l['liters']) : '—' ?></td>
                        <td><?= $l['fuel_date'] ?></td>
                        <td class="action-btns">
                            <a href="<?= baseUrl() ?>/fuel/delete.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Delete this fuel log?"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

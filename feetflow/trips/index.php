<?php
/**
 * FleetFlow – Trip List
 */
$pageTitle = 'Trips';
$activeModule = 'trips';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Dispatcher','Safety Officer']);

$db = getDB();
$trips = $db->query("
    SELECT t.*, v.vehicle_name, v.license_plate, d.full_name as driver_name
    FROM trips t
    JOIN vehicles v ON t.vehicle_id = v.id
    JOIN drivers d ON t.driver_id = d.id
    ORDER BY t.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-map"></i>Trip Dispatcher</h1>
    <a href="<?= baseUrl() ?>/trips/create.php" class="btn btn-ff">
        <i class="bi bi-plus-lg me-1"></i>Create Trip
    </a>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="ff-search">
        <i class="bi bi-search"></i>
        <input type="text" id="tableSearch" placeholder="Search trips...">
    </div>
    <span class="text-muted-light" style="font-size:0.85rem"><?= count($trips) ?> trips</span>
</div>

<div class="ff-table-wrapper">
    <div class="table-responsive">
        <table class="table ff-table" id="dataTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Cargo</th>
                    <th>Route</th>
                    <th>Distance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trips)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No trips found.</td></tr>
                <?php else: ?>
                    <?php foreach ($trips as $i => $t): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= sanitize($t['vehicle_name']) ?><br><small class="text-muted-light"><?= sanitize($t['license_plate']) ?></small></td>
                        <td><?= sanitize($t['driver_name']) ?></td>
                        <td><?= sanitize($t['cargo_description'] ?? '—') ?><br><small class="text-muted-light"><?= number_format($t['cargo_weight']) ?> kg</small></td>
                        <td><?= sanitize($t['origin']) ?> → <?= sanitize($t['destination']) ?></td>
                        <td><?= $t['distance'] ? number_format($t['distance']) . ' km' : '—' ?></td>
                        <td><?= tripStatusBadge($t['status']) ?></td>
                        <td class="action-btns">
                            <?php if ($t['status'] === 'Draft'): ?>
                                <a href="<?= baseUrl() ?>/trips/dispatch.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary" title="Dispatch" data-confirm="Dispatch this trip?">
                                    <i class="bi bi-send"></i>
                                </a>
                                <a href="<?= baseUrl() ?>/trips/cancel.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger" title="Cancel" data-confirm="Cancel this trip?">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php elseif ($t['status'] === 'Dispatched'): ?>
                                <a href="<?= baseUrl() ?>/trips/complete.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-success" title="Complete">
                                    <i class="bi bi-check-lg"></i>
                                </a>
                                <a href="<?= baseUrl() ?>/trips/cancel.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger" title="Cancel" data-confirm="Cancel this trip?">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted-light" style="font-size:0.75rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

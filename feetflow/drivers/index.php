<?php
/**
 * FleetFlow – Driver Management
 */
$pageTitle = 'Drivers';
$activeModule = 'drivers';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Dispatcher','Safety Officer']);

$db = getDB();
$drivers = $db->query("SELECT * FROM drivers ORDER BY created_at DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-person-badge"></i>Driver Management</h1>
    <a href="<?= baseUrl() ?>/drivers/add.php" class="btn btn-ff">
        <i class="bi bi-plus-lg me-1"></i>Add Driver
    </a>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="ff-search">
        <i class="bi bi-search"></i>
        <input type="text" id="tableSearch" placeholder="Search drivers...">
    </div>
    <span class="text-muted-light" style="font-size:0.85rem"><?= count($drivers) ?> drivers</span>
</div>

<div class="ff-table-wrapper">
    <div class="table-responsive">
        <table class="table ff-table" id="dataTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>License Cat.</th>
                    <th>License Expiry</th>
                    <th>Safety Score</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($drivers)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No drivers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($drivers as $i => $d): ?>
                    <?php
                        $expired = strtotime($d['license_expiry']) < time();
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= sanitize($d['full_name']) ?></td>
                        <td><span class="badge bg-secondary"><?= sanitize($d['license_category']) ?></span></td>
                        <td>
                            <?= $d['license_expiry'] ?>
                            <?php if ($expired): ?>
                                <span class="badge bg-danger ms-1">EXPIRED</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= $d['safety_score'] >= 80 ? 'text-success' : ($d['safety_score'] >= 60 ? 'text-warning' : 'text-danger') ?> fw-semibold">
                                <?= $d['safety_score'] ?>
                            </span>
                        </td>
                        <td><?= sanitize($d['phone'] ?? '—') ?></td>
                        <td><?= driverStatusBadge($d['status']) ?></td>
                        <td class="action-btns">
                            <a href="<?= baseUrl() ?>/drivers/edit.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-pencil"></i></a>
                            <a href="<?= baseUrl() ?>/drivers/delete.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Delete this driver?"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

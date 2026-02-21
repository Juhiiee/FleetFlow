<?php
/**
 * FleetFlow – Add Maintenance Log
 * Automatically sets vehicle status to "In Shop"
 */
$pageTitle = 'Add Maintenance';
$activeModule = 'maintenance';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Safety Officer']);

$db = getDB();
$vehicles = $db->query("SELECT * FROM vehicles WHERE status != 'Retired' ORDER BY vehicle_name")->fetchAll();

$errors = [];
$data = ['vehicle_id' => '', 'service_description' => '', 'cost' => '', 'service_date' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'vehicle_id'          => intval($_POST['vehicle_id'] ?? 0),
        'service_description' => trim($_POST['service_description'] ?? ''),
        'cost'                => floatval($_POST['cost'] ?? 0),
        'service_date'        => $_POST['service_date'] ?? date('Y-m-d'),
    ];

    if ($data['vehicle_id'] <= 0) $errors[] = 'Please select a vehicle.';
    if (empty($data['service_description'])) $errors[] = 'Service description is required.';
    if ($data['cost'] < 0) $errors[] = 'Cost cannot be negative.';
    if (empty($data['service_date'])) $errors[] = 'Service date is required.';

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // Insert maintenance log
            $stmt = $db->prepare("INSERT INTO maintenance_logs (vehicle_id, service_description, cost, service_date)
                VALUES (:vi, :sd, :co, :dt)");
            $stmt->execute([
                ':vi' => $data['vehicle_id'], ':sd' => $data['service_description'],
                ':co' => $data['cost'], ':dt' => $data['service_date'],
            ]);

            // Automatically set vehicle to "In Shop"
            $stmt = $db->prepare("UPDATE vehicles SET status = 'In Shop' WHERE id = :id AND status != 'Retired'");
            $stmt->execute([':id' => $data['vehicle_id']]);

            $db->commit();
            setFlash('success', 'Maintenance log added. Vehicle status set to "In Shop".');
        } catch (Exception $e) {
            $db->rollBack();
            setFlash('danger', 'Failed to add maintenance log.');
        }
        redirect(baseUrl() . '/maintenance/');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-wrench"></i>Add Maintenance Log</h1>
    <a href="<?= baseUrl() ?>/maintenance/" class="btn btn-ff-outline"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="ff-card">
    <div class="card-body">
        <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:0.85rem">
            <i class="bi bi-exclamation-triangle me-1"></i>Adding a maintenance log will automatically set the vehicle status to <strong>"In Shop"</strong>.
        </div>
        <form method="POST" class="ff-form">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Vehicle *</label>
                    <select class="form-select" name="vehicle_id" required>
                        <option value="">-- Select Vehicle --</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= $data['vehicle_id'] == $v['id'] ? 'selected' : '' ?>>
                                <?= sanitize($v['vehicle_name']) ?> (<?= sanitize($v['license_plate']) ?>) – <?= $v['status'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Service Description *</label>
                    <input type="text" class="form-control" name="service_description" value="<?= sanitize($data['service_description']) ?>" required placeholder="e.g. Brake pad replacement">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cost ($)</label>
                    <input type="number" step="0.01" class="form-control" name="cost" value="<?= $data['cost'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Service Date *</label>
                    <input type="date" class="form-control" name="service_date" value="<?= sanitize($data['service_date']) ?>" required>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-ff"><i class="bi bi-check-lg me-1"></i>Save Log</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

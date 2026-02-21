<?php
/**
 * FleetFlow – Add Fuel Log
 */
$pageTitle = 'Add Fuel Log';
$activeModule = 'fuel';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Financial Analyst']);

$db = getDB();
$vehicles = $db->query("SELECT * FROM vehicles WHERE status != 'Retired' ORDER BY vehicle_name")->fetchAll();

$errors = [];
$data = ['vehicle_id' => '', 'liters' => '', 'cost' => '', 'fuel_date' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'vehicle_id' => intval($_POST['vehicle_id'] ?? 0),
        'liters'     => floatval($_POST['liters'] ?? 0),
        'cost'       => floatval($_POST['cost'] ?? 0),
        'fuel_date'  => $_POST['fuel_date'] ?? date('Y-m-d'),
    ];

    if ($data['vehicle_id'] <= 0) $errors[] = 'Please select a vehicle.';
    if ($data['liters'] <= 0) $errors[] = 'Liters must be positive.';
    if ($data['cost'] < 0) $errors[] = 'Cost cannot be negative.';
    if (empty($data['fuel_date'])) $errors[] = 'Fuel date is required.';

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO fuel_logs (vehicle_id, liters, cost, fuel_date) VALUES (:vi, :lt, :co, :fd)");
        $stmt->execute([
            ':vi' => $data['vehicle_id'], ':lt' => $data['liters'],
            ':co' => $data['cost'], ':fd' => $data['fuel_date'],
        ]);
        setFlash('success', 'Fuel log added successfully!');
        redirect(baseUrl() . '/fuel/');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-fuel-pump"></i>Add Fuel Log</h1>
    <a href="<?= baseUrl() ?>/fuel/" class="btn btn-ff-outline"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="ff-card">
    <div class="card-body">
        <form method="POST" class="ff-form">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Vehicle *</label>
                    <select class="form-select" name="vehicle_id" required>
                        <option value="">-- Select Vehicle --</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= $data['vehicle_id'] == $v['id'] ? 'selected' : '' ?>>
                                <?= sanitize($v['vehicle_name']) ?> (<?= sanitize($v['license_plate']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fuel Date *</label>
                    <input type="date" class="form-control" name="fuel_date" value="<?= sanitize($data['fuel_date']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Liters *</label>
                    <input type="number" step="0.01" class="form-control" name="liters" value="<?= $data['liters'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cost ($) *</label>
                    <input type="number" step="0.01" class="form-control" name="cost" value="<?= $data['cost'] ?>" required>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-ff"><i class="bi bi-check-lg me-1"></i>Save Fuel Log</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

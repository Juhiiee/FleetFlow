<?php
/**
 * FleetFlow – Add Vehicle
 */
$pageTitle = 'Add Vehicle';
$activeModule = 'vehicles';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Dispatcher']);

$errors = [];
$data = [
    'vehicle_name' => '', 'license_plate' => '', 'vehicle_type' => 'Truck',
    'max_load_capacity' => '', 'odometer' => '0', 'acquisition_cost' => '',
    'region' => '', 'status' => 'Available'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'vehicle_name'      => trim($_POST['vehicle_name'] ?? ''),
        'license_plate'     => strtoupper(trim($_POST['license_plate'] ?? '')),
        'vehicle_type'      => $_POST['vehicle_type'] ?? 'Truck',
        'max_load_capacity' => floatval($_POST['max_load_capacity'] ?? 0),
        'odometer'          => floatval($_POST['odometer'] ?? 0),
        'acquisition_cost'  => floatval($_POST['acquisition_cost'] ?? 0),
        'region'            => trim($_POST['region'] ?? ''),
        'status'            => $_POST['status'] ?? 'Available',
    ];

    // Validation
    if (empty($data['vehicle_name'])) $errors[] = 'Vehicle name is required.';
    if (empty($data['license_plate'])) $errors[] = 'License plate is required.';
    if ($data['max_load_capacity'] <= 0) $errors[] = 'Max load capacity must be positive.';
    if ($data['acquisition_cost'] < 0) $errors[] = 'Acquisition cost cannot be negative.';

    // Check unique license plate
    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM vehicles WHERE license_plate = :lp");
        $stmt->execute([':lp' => $data['license_plate']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'License plate already exists.';
        }
    }

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO vehicles (vehicle_name, license_plate, vehicle_type, max_load_capacity, odometer, acquisition_cost, region, status)
            VALUES (:vn, :lp, :vt, :ml, :od, :ac, :rg, :st)");
        $stmt->execute([
            ':vn' => $data['vehicle_name'], ':lp' => $data['license_plate'],
            ':vt' => $data['vehicle_type'], ':ml' => $data['max_load_capacity'],
            ':od' => $data['odometer'], ':ac' => $data['acquisition_cost'],
            ':rg' => $data['region'], ':st' => $data['status'],
        ]);
        setFlash('success', 'Vehicle added successfully!');
        redirect(baseUrl() . '/vehicles/');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-truck"></i>Add Vehicle</h1>
    <a href="<?= baseUrl() ?>/vehicles/" class="btn btn-ff-outline"><i class="bi bi-arrow-left me-1"></i>Back</a>
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
                    <label class="form-label">Vehicle Name/Model *</label>
                    <input type="text" class="form-control" name="vehicle_name" value="<?= sanitize($data['vehicle_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">License Plate *</label>
                    <input type="text" class="form-control" name="license_plate" value="<?= sanitize($data['license_plate']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vehicle Type</label>
                    <select class="form-select" name="vehicle_type">
                        <?php foreach (['Truck','Van','Sedan','SUV','Bus'] as $t): ?>
                            <option value="<?= $t ?>" <?= $data['vehicle_type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Max Load Capacity (kg) *</label>
                    <input type="number" step="0.01" class="form-control" name="max_load_capacity" value="<?= $data['max_load_capacity'] ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Odometer (km)</label>
                    <input type="number" step="0.01" class="form-control" name="odometer" value="<?= $data['odometer'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Acquisition Cost ($)</label>
                    <input type="number" step="0.01" class="form-control" name="acquisition_cost" value="<?= $data['acquisition_cost'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Region</label>
                    <input type="text" class="form-control" name="region" value="<?= sanitize($data['region']) ?>" placeholder="e.g. North">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['Available','On Trip','In Shop','Retired'] as $s): ?>
                            <option value="<?= $s ?>" <?= $data['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-ff"><i class="bi bi-check-lg me-1"></i>Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

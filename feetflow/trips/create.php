<?php
/**
 * FleetFlow – Create Trip
 * Core Logic: Capacity validation, vehicle availability, driver license check
 */
$pageTitle = 'Create Trip';
$activeModule = 'trips';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Dispatcher']);

$db = getDB();

// Only Available vehicles (not In Shop, not On Trip, not Retired)
$vehicles = $db->query("SELECT * FROM vehicles WHERE status = 'Available' ORDER BY vehicle_name")->fetchAll();

// Only eligible drivers: Off Duty, license not expired, not Suspended
$today = date('Y-m-d');
$drivers = $db->prepare("SELECT * FROM drivers WHERE status = 'Off Duty' AND license_expiry >= :today ORDER BY full_name");
$drivers->execute([':today' => $today]);
$drivers = $drivers->fetchAll();

$errors = [];
$data = [
    'vehicle_id' => '', 'driver_id' => '', 'cargo_description' => '',
    'cargo_weight' => '', 'origin' => '', 'destination' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'vehicle_id'       => intval($_POST['vehicle_id'] ?? 0),
        'driver_id'        => intval($_POST['driver_id'] ?? 0),
        'cargo_description'=> trim($_POST['cargo_description'] ?? ''),
        'cargo_weight'     => floatval($_POST['cargo_weight'] ?? 0),
        'origin'           => trim($_POST['origin'] ?? ''),
        'destination'      => trim($_POST['destination'] ?? ''),
    ];

    if ($data['vehicle_id'] <= 0) $errors[] = 'Please select a vehicle.';
    if ($data['driver_id'] <= 0) $errors[] = 'Please select a driver.';
    if ($data['cargo_weight'] <= 0) $errors[] = 'Cargo weight must be positive.';
    if (empty($data['origin'])) $errors[] = 'Origin is required.';
    if (empty($data['destination'])) $errors[] = 'Destination is required.';

    // Backend: Validate vehicle is still Available
    if ($data['vehicle_id'] > 0) {
        $stmt = $db->prepare("SELECT * FROM vehicles WHERE id = :id");
        $stmt->execute([':id' => $data['vehicle_id']]);
        $vehicle = $stmt->fetch();
        if (!$vehicle) {
            $errors[] = 'Selected vehicle not found.';
        } elseif ($vehicle['status'] !== 'Available') {
            $errors[] = 'Vehicle is not available (current status: ' . $vehicle['status'] . ').';
        } elseif ($data['cargo_weight'] > $vehicle['max_load_capacity']) {
            $errors[] = 'Cargo weight (' . number_format($data['cargo_weight']) . ' kg) exceeds vehicle max capacity (' . number_format($vehicle['max_load_capacity']) . ' kg).';
        }
    }

    // Backend: Validate driver eligibility
    if ($data['driver_id'] > 0) {
        $stmt = $db->prepare("SELECT * FROM drivers WHERE id = :id");
        $stmt->execute([':id' => $data['driver_id']]);
        $driver = $stmt->fetch();
        if (!$driver) {
            $errors[] = 'Selected driver not found.';
        } elseif ($driver['status'] === 'Suspended') {
            $errors[] = 'Driver is suspended and cannot be assigned.';
        } elseif (strtotime($driver['license_expiry']) < time()) {
            $errors[] = 'Driver license expired on ' . $driver['license_expiry'] . '.';
        } elseif ($driver['status'] === 'On Duty') {
            $errors[] = 'Driver is already on duty.';
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO trips (vehicle_id, driver_id, cargo_description, cargo_weight, origin, destination, start_odometer, status)
            VALUES (:vi, :di, :cd, :cw, :og, :ds, :so, 'Draft')");
        $stmt->execute([
            ':vi' => $data['vehicle_id'], ':di' => $data['driver_id'],
            ':cd' => $data['cargo_description'], ':cw' => $data['cargo_weight'],
            ':og' => $data['origin'], ':ds' => $data['destination'],
            ':so' => $vehicle['odometer'],
        ]);
        setFlash('success', 'Trip created as Draft!');
        redirect(baseUrl() . '/trips/');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-map"></i>Create Trip</h1>
    <a href="<?= baseUrl() ?>/trips/" class="btn btn-ff-outline"><i class="bi bi-arrow-left me-1"></i>Back</a>
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
                    <label class="form-label">Vehicle (Available Only) *</label>
                    <select class="form-select" name="vehicle_id" required id="vehicleSelect">
                        <option value="">-- Select Vehicle --</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= $v['id'] ?>" data-capacity="<?= $v['max_load_capacity'] ?>"
                                <?= $data['vehicle_id'] == $v['id'] ? 'selected' : '' ?>>
                                <?= sanitize($v['vehicle_name']) ?> (<?= sanitize($v['license_plate']) ?>) – Max <?= number_format($v['max_load_capacity']) ?> kg
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($vehicles)): ?>
                        <small class="text-danger">No available vehicles. All are on trip, in shop, or retired.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Driver (Eligible Only) *</label>
                    <select class="form-select" name="driver_id" required>
                        <option value="">-- Select Driver --</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $data['driver_id'] == $d['id'] ? 'selected' : '' ?>>
                                <?= sanitize($d['full_name']) ?> (Cat. <?= $d['license_category'] ?>, Exp: <?= $d['license_expiry'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($drivers)): ?>
                        <small class="text-danger">No eligible drivers. All are on duty, suspended, or have expired licenses.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cargo Weight (kg) *</label>
                    <input type="number" step="0.01" class="form-control" name="cargo_weight" value="<?= $data['cargo_weight'] ?>" required id="cargoWeight">
                    <small class="text-muted" id="capacityHint"></small>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Cargo Description</label>
                    <input type="text" class="form-control" name="cargo_description" value="<?= sanitize($data['cargo_description']) ?>" placeholder="e.g. Electronic Components">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Origin *</label>
                    <input type="text" class="form-control" name="origin" value="<?= sanitize($data['origin']) ?>" required placeholder="e.g. Chicago">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Destination *</label>
                    <input type="text" class="form-control" name="destination" value="<?= sanitize($data['destination']) ?>" required placeholder="e.g. Detroit">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-ff"><i class="bi bi-check-lg me-1"></i>Create Trip (Draft)</button>
            </div>
        </form>
    </div>
</div>

<script>
// Show capacity hint when vehicle is selected
document.getElementById('vehicleSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const cap = opt?.dataset?.capacity;
    const hint = document.getElementById('capacityHint');
    if (cap && hint) hint.textContent = 'Max capacity: ' + Number(cap).toLocaleString() + ' kg';
    else if (hint) hint.textContent = '';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

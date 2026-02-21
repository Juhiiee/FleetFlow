<?php
/**
 * FleetFlow – Complete Trip
 * Enter end odometer, calculate distance, restore statuses
 */
$pageTitle = 'Complete Trip';
$activeModule = 'trips';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Dispatcher']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { redirect(baseUrl() . '/trips/'); }

$stmt = $db->prepare("
    SELECT t.*, v.vehicle_name, v.license_plate, v.odometer as current_odometer, d.full_name as driver_name
    FROM trips t
    JOIN vehicles v ON t.vehicle_id = v.id
    JOIN drivers d ON t.driver_id = d.id
    WHERE t.id = :id AND t.status = 'Dispatched'
");
$stmt->execute([':id' => $id]);
$trip = $stmt->fetch();

if (!$trip) {
    setFlash('danger', 'Trip not found or not in Dispatched status.');
    redirect(baseUrl() . '/trips/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $endOdometer = floatval($_POST['end_odometer'] ?? 0);
    $startOdo = $trip['start_odometer'] ?: $trip['current_odometer'];

    if ($endOdometer <= 0) {
        $errors[] = 'End odometer is required.';
    } elseif ($endOdometer <= $startOdo) {
        $errors[] = 'End odometer must be greater than start odometer (' . number_format($startOdo) . ' km).';
    }

    if (empty($errors)) {
        $distance = $endOdometer - $startOdo;

        $db->beginTransaction();
        try {
            // Update trip
            $stmt = $db->prepare("UPDATE trips SET status = 'Completed', end_odometer = :eo, distance = :dist, completed_at = NOW() WHERE id = :id");
            $stmt->execute([':eo' => $endOdometer, ':dist' => $distance, ':id' => $id]);

            // Vehicle → Available + update odometer
            $stmt = $db->prepare("UPDATE vehicles SET status = 'Available', odometer = :odo WHERE id = :id");
            $stmt->execute([':odo' => $endOdometer, ':id' => $trip['vehicle_id']]);

            // Driver → Off Duty
            $stmt = $db->prepare("UPDATE drivers SET status = 'Off Duty' WHERE id = :id");
            $stmt->execute([':id' => $trip['driver_id']]);

            $db->commit();
            setFlash('success', 'Trip completed! Distance: ' . number_format($distance) . ' km.');
        } catch (Exception $e) {
            $db->rollBack();
            setFlash('danger', 'Failed to complete trip: ' . $e->getMessage());
        }
        redirect(baseUrl() . '/trips/');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-check-circle"></i>Complete Trip</h1>
    <a href="<?= baseUrl() ?>/trips/" class="btn btn-ff-outline"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="ff-card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-info-circle me-2 text-accent"></i>Trip Details</h6>
                <table class="table table-sm table-borderless" style="font-size:0.875rem">
                    <tr><td class="text-muted-light" style="width:40%">Vehicle</td><td><?= sanitize($trip['vehicle_name']) ?> (<?= sanitize($trip['license_plate']) ?>)</td></tr>
                    <tr><td class="text-muted-light">Driver</td><td><?= sanitize($trip['driver_name']) ?></td></tr>
                    <tr><td class="text-muted-light">Route</td><td><?= sanitize($trip['origin']) ?> → <?= sanitize($trip['destination']) ?></td></tr>
                    <tr><td class="text-muted-light">Cargo</td><td><?= number_format($trip['cargo_weight']) ?> kg</td></tr>
                    <tr><td class="text-muted-light">Start Odometer</td><td><?= number_format($trip['start_odometer'] ?: $trip['current_odometer']) ?> km</td></tr>
                    <tr><td class="text-muted-light">Dispatched</td><td><?= $trip['dispatched_at'] ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="ff-card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-speedometer me-2 text-accent"></i>Enter End Odometer</h6>
                <form method="POST" class="ff-form">
                    <div class="mb-3">
                        <label class="form-label">End Odometer (km) *</label>
                        <input type="number" step="0.01" class="form-control" name="end_odometer" required
                               min="<?= $trip['start_odometer'] ?: $trip['current_odometer'] ?>"
                               placeholder="Must be > <?= number_format($trip['start_odometer'] ?: $trip['current_odometer']) ?>">
                    </div>
                    <button type="submit" class="btn btn-ff w-100"><i class="bi bi-check-circle me-1"></i>Complete Trip</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

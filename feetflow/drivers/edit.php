<?php
/**
 * FleetFlow – Edit Driver
 */
$pageTitle = 'Edit Driver';
$activeModule = 'drivers';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Dispatcher']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { redirect(baseUrl() . '/drivers/'); }

$stmt = $db->prepare("SELECT * FROM drivers WHERE id = :id");
$stmt->execute([':id' => $id]);
$data = $stmt->fetch();
if (!$data) { setFlash('danger', 'Driver not found.'); redirect(baseUrl() . '/drivers/'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = array_merge($data, [
        'full_name'        => trim($_POST['full_name'] ?? ''),
        'license_category' => $_POST['license_category'] ?? 'B',
        'license_expiry'   => $_POST['license_expiry'] ?? '',
        'safety_score'     => floatval($_POST['safety_score'] ?? 100),
        'phone'            => trim($_POST['phone'] ?? ''),
        'status'           => $_POST['status'] ?? 'Off Duty',
    ]);

    if (empty($data['full_name'])) $errors[] = 'Full name is required.';
    if (empty($data['license_expiry'])) $errors[] = 'License expiry date is required.';
    if ($data['safety_score'] < 0 || $data['safety_score'] > 100) $errors[] = 'Safety score must be 0–100.';

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE drivers SET full_name=:fn, license_category=:lc, license_expiry=:le,
            safety_score=:ss, phone=:ph, status=:st WHERE id=:id");
        $stmt->execute([
            ':fn' => $data['full_name'], ':lc' => $data['license_category'],
            ':le' => $data['license_expiry'], ':ss' => $data['safety_score'],
            ':ph' => $data['phone'], ':st' => $data['status'], ':id' => $id,
        ]);
        setFlash('success', 'Driver updated successfully!');
        redirect(baseUrl() . '/drivers/');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-person-badge"></i>Edit Driver</h1>
    <a href="<?= baseUrl() ?>/drivers/" class="btn btn-ff-outline"><i class="bi bi-arrow-left me-1"></i>Back</a>
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
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-control" name="full_name" value="<?= sanitize($data['full_name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">License Category</label>
                    <select class="form-select" name="license_category">
                        <?php foreach (['A','B','C','D','E'] as $c): ?>
                            <option value="<?= $c ?>" <?= $data['license_category'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">License Expiry *</label>
                    <input type="date" class="form-control" name="license_expiry" value="<?= $data['license_expiry'] ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Safety Score (0–100)</label>
                    <input type="number" step="0.1" min="0" max="100" class="form-control" name="safety_score" value="<?= $data['safety_score'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" name="phone" value="<?= sanitize($data['phone'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['Off Duty','On Duty','Suspended'] as $s): ?>
                            <option value="<?= $s ?>" <?= $data['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-ff"><i class="bi bi-check-lg me-1"></i>Update Driver</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

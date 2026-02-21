<?php
/**
 * FleetFlow – Command Center Dashboard
 */
$pageTitle = 'Dashboard';
$activeModule = 'dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();

$db = getDB();

// ---- Filters ----
$filterType   = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterRegion = $_GET['region'] ?? '';

$whereClauses = [];
$params = [];
if ($filterType) { $whereClauses[] = "vehicle_type = :vt"; $params[':vt'] = $filterType; }
if ($filterStatus) { $whereClauses[] = "status = :vs"; $params[':vs'] = $filterStatus; }
if ($filterRegion) { $whereClauses[] = "region = :vr"; $params[':vr'] = $filterRegion; }
$whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// ---- KPIs ----
$totalVehicles  = $db->query("SELECT COUNT(*) FROM vehicles WHERE status != 'Retired'")->fetchColumn();
$activeFleet    = $db->query("SELECT COUNT(*) FROM vehicles WHERE status = 'On Trip'")->fetchColumn();
$inShop         = $db->query("SELECT COUNT(*) FROM vehicles WHERE status = 'In Shop'")->fetchColumn();
$utilization    = $totalVehicles > 0 ? round(($activeFleet / $totalVehicles) * 100, 1) : 0;
$pendingTrips   = $db->query("SELECT COUNT(*) FROM trips WHERE status = 'Draft'")->fetchColumn();

$fuelCost = $db->query("SELECT COALESCE(SUM(cost), 0) FROM fuel_logs")->fetchColumn();
$maintCost = $db->query("SELECT COALESCE(SUM(cost), 0) FROM maintenance_logs")->fetchColumn();
$totalOpCost = $fuelCost + $maintCost;

// ---- Fleet status breakdown for chart ----
$statusBreakdown = $db->query("SELECT status, COUNT(*) as cnt FROM vehicles WHERE status != 'Retired' GROUP BY status")->fetchAll();
$statusLabels = [];
$statusCounts = [];
$statusColors = ['Available' => '#34d399', 'On Trip' => '#fbbf24', 'In Shop' => '#f87171'];
$chartColors = [];
foreach ($statusBreakdown as $row) {
    $statusLabels[] = $row['status'];
    $statusCounts[] = (int)$row['cnt'];
    $chartColors[]  = $statusColors[$row['status']] ?? '#64748b';
}

// ---- Recent trips ----
$recentTrips = $db->query("
    SELECT t.*, v.vehicle_name, v.license_plate, d.full_name as driver_name
    FROM trips t
    JOIN vehicles v ON t.vehicle_id = v.id
    JOIN drivers d ON t.driver_id = d.id
    ORDER BY t.created_at DESC LIMIT 5
")->fetchAll();

// ---- Filter options ----
$regions = $db->query("SELECT DISTINCT region FROM vehicles ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);
$vTypes  = ['Truck','Van','Sedan','SUV','Bus'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-speedometer2"></i>Command Center</h1>
    <span class="text-muted-light" style="font-size:0.85rem">
        <i class="bi bi-clock me-1"></i><?= date('l, F j, Y – g:i A') ?>
    </span>
</div>

<!-- Filter Bar -->
<form class="ff-filter-bar" method="GET">
    <div>
        <label class="form-label mb-1" style="font-size:0.7rem;">Vehicle Type</label>
        <select name="type" class="form-select form-select-sm">
            <option value="">All Types</option>
            <?php foreach ($vTypes as $t): ?>
                <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label mb-1" style="font-size:0.7rem;">Status</label>
        <select name="status" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <option value="Available" <?= $filterStatus === 'Available' ? 'selected' : '' ?>>Available</option>
            <option value="On Trip" <?= $filterStatus === 'On Trip' ? 'selected' : '' ?>>On Trip</option>
            <option value="In Shop" <?= $filterStatus === 'In Shop' ? 'selected' : '' ?>>In Shop</option>
        </select>
    </div>
    <div>
        <label class="form-label mb-1" style="font-size:0.7rem;">Region</label>
        <select name="region" class="form-select form-select-sm">
            <option value="">All Regions</option>
            <?php foreach ($regions as $r): ?>
                <option value="<?= sanitize($r) ?>" <?= $filterRegion === $r ? 'selected' : '' ?>><?= sanitize($r) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <button type="submit" class="btn btn-ff btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="<?= baseUrl() ?>/dashboard/" class="btn btn-ff-outline btn-sm ms-1">Reset</a>
    </div>
</form>

<!-- KPI Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg fade-in-up">
        <div class="kpi-card kpi-blue">
            <div class="kpi-icon"><i class="bi bi-truck"></i></div>
            <div class="kpi-value"><?= $activeFleet ?></div>
            <div class="kpi-label">Active Fleet</div>
        </div>
    </div>
    <div class="col-md-4 col-lg fade-in-up">
        <div class="kpi-card kpi-red">
            <div class="kpi-icon"><i class="bi bi-wrench"></i></div>
            <div class="kpi-value"><?= $inShop ?></div>
            <div class="kpi-label">In Shop</div>
        </div>
    </div>
    <div class="col-md-4 col-lg fade-in-up">
        <div class="kpi-card kpi-green">
            <div class="kpi-icon"><i class="bi bi-percent"></i></div>
            <div class="kpi-value"><?= $utilization ?>%</div>
            <div class="kpi-label">Utilization Rate</div>
        </div>
    </div>
    <div class="col-md-4 col-lg fade-in-up">
        <div class="kpi-card kpi-yellow">
            <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
            <div class="kpi-value"><?= $pendingTrips ?></div>
            <div class="kpi-label">Pending Cargo</div>
        </div>
    </div>
    <div class="col-md-4 col-lg fade-in-up">
        <div class="kpi-card kpi-purple">
            <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-value"><?= formatCurrency($totalOpCost) ?></div>
            <div class="kpi-label">Total Op. Cost</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Fleet Status Chart -->
    <div class="col-lg-5 fade-in-up">
        <div class="ff-card h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-pie-chart me-2 text-accent"></i>Fleet Status</h6>
                <div class="chart-container" style="max-height:280px;display:flex;align-items:center;justify-content:center;">
                    <canvas id="fleetStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Trips -->
    <div class="col-lg-7 fade-in-up">
        <div class="ff-card h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-clock-history me-2 text-accent"></i>Recent Trips</h6>
                <?php if (empty($recentTrips)): ?>
                    <div class="empty-state"><i class="bi bi-map d-block"></i>No trips yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table ff-table table-sm">
                            <thead>
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Driver</th>
                                    <th>Route</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTrips as $trip): ?>
                                <tr>
                                    <td><?= sanitize($trip['vehicle_name']) ?><br><small class="text-muted-light"><?= sanitize($trip['license_plate']) ?></small></td>
                                    <td><?= sanitize($trip['driver_name']) ?></td>
                                    <td><?= sanitize($trip['origin']) ?> → <?= sanitize($trip['destination']) ?></td>
                                    <td><?= tripStatusBadge($trip['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('fleetStatusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusCounts) ?>,
            backgroundColor: <?= json_encode($chartColors) ?>,
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 16, font: { size: 12, family: 'Inter' } } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

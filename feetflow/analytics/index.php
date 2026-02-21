<?php
/**
 * FleetFlow – Analytics & Reports
 * Fuel Efficiency, Monthly Expenses, Vehicle ROI
 */
$pageTitle = 'Analytics';
$activeModule = 'analytics';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Financial Analyst']);

$db = getDB();

// ---- 1. Fuel Efficiency per Vehicle (km/L) ----
$fuelEfficiency = $db->query("
    SELECT v.id, v.vehicle_name, v.license_plate,
        COALESCE(SUM(t.distance), 0) as total_distance,
        COALESCE(SUM(f.liters), 0) as total_liters,
        CASE WHEN COALESCE(SUM(f.liters), 0) > 0
            THEN ROUND(COALESCE(SUM(t.distance), 0) / SUM(f.liters), 2)
            ELSE 0 END as efficiency
    FROM vehicles v
    LEFT JOIN trips t ON t.vehicle_id = v.id AND t.status = 'Completed'
    LEFT JOIN fuel_logs f ON f.vehicle_id = v.id
    WHERE v.status != 'Retired'
    GROUP BY v.id
    ORDER BY efficiency DESC
")->fetchAll();

$effLabels = []; $effData = [];
foreach ($fuelEfficiency as $row) {
    $effLabels[] = $row['vehicle_name'];
    $effData[]   = floatval($row['efficiency']);
}

// ---- 2. Monthly Expense (Fuel + Maintenance) ----
$monthlyFuel = $db->query("
    SELECT DATE_FORMAT(fuel_date, '%Y-%m') as month, SUM(cost) as total
    FROM fuel_logs GROUP BY month ORDER BY month
")->fetchAll();

$monthlyMaint = $db->query("
    SELECT DATE_FORMAT(service_date, '%Y-%m') as month, SUM(cost) as total
    FROM maintenance_logs GROUP BY month ORDER BY month
")->fetchAll();

// Merge months
$allMonths = [];
foreach ($monthlyFuel as $r)  $allMonths[$r['month']]['fuel']  = floatval($r['total']);
foreach ($monthlyMaint as $r) $allMonths[$r['month']]['maint'] = floatval($r['total']);
ksort($allMonths);

$expMonths = []; $expFuel = []; $expMaint = [];
foreach ($allMonths as $month => $data) {
    $expMonths[] = $month;
    $expFuel[]   = $data['fuel'] ?? 0;
    $expMaint[]  = $data['maint'] ?? 0;
}

// ---- 3. Vehicle ROI ----
// ROI = (Revenue - (Fuel + Maintenance)) / Acquisition Cost
// Since we don't track revenue, we'll estimate revenue from completed trips count * avg rate
$vehicleROI = $db->query("
    SELECT v.id, v.vehicle_name, v.license_plate, v.acquisition_cost,
        COALESCE(fc.fuel_total, 0) as fuel_cost,
        COALESCE(mc.maint_total, 0) as maint_cost,
        COALESCE(tc.trip_count, 0) as trip_count,
        COALESCE(tc.total_distance, 0) as total_distance,
        CASE WHEN v.acquisition_cost > 0
            THEN ROUND(((COALESCE(tc.total_distance, 0) * 2) - (COALESCE(fc.fuel_total, 0) + COALESCE(mc.maint_total, 0))) / v.acquisition_cost * 100, 2)
            ELSE 0 END as roi
    FROM vehicles v
    LEFT JOIN (SELECT vehicle_id, SUM(cost) as fuel_total FROM fuel_logs GROUP BY vehicle_id) fc ON fc.vehicle_id = v.id
    LEFT JOIN (SELECT vehicle_id, SUM(cost) as maint_total FROM maintenance_logs GROUP BY vehicle_id) mc ON mc.vehicle_id = v.id
    LEFT JOIN (SELECT vehicle_id, COUNT(*) as trip_count, SUM(distance) as total_distance FROM trips WHERE status='Completed' GROUP BY vehicle_id) tc ON tc.vehicle_id = v.id
    WHERE v.status != 'Retired'
    ORDER BY roi DESC
")->fetchAll();

$roiLabels = []; $roiData = []; $roiColors = [];
foreach ($vehicleROI as $row) {
    $roiLabels[] = $row['vehicle_name'];
    $roiData[]   = floatval($row['roi']);
    $roiColors[] = $row['roi'] >= 0 ? '#34d399' : '#f87171';
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-graph-up"></i>Analytics & Reports</h1>
    <div>
        <a href="<?= baseUrl() ?>/analytics/export_csv.php" class="btn btn-ff-outline btn-sm me-1">
            <i class="bi bi-filetype-csv me-1"></i>Export CSV
        </a>
        <a href="<?= baseUrl() ?>/analytics/export_pdf.php" class="btn btn-ff-outline btn-sm">
            <i class="bi bi-filetype-pdf me-1"></i>Export PDF
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Fuel Efficiency Chart -->
    <div class="col-lg-6 fade-in-up">
        <div class="ff-card h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-speedometer2 me-2 text-accent"></i>Fuel Efficiency (km/L)</h6>
                <div class="chart-container">
                    <canvas id="fuelEffChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Expenses Chart -->
    <div class="col-lg-6 fade-in-up">
        <div class="ff-card h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-cash-stack me-2 text-accent"></i>Monthly Expenses</h6>
                <div class="chart-container">
                    <canvas id="monthlyExpChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle ROI Chart -->
<div class="row g-4 mb-4">
    <div class="col-12 fade-in-up">
        <div class="ff-card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-bar-chart me-2 text-accent"></i>Vehicle ROI (%)</h6>
                <div class="chart-container">
                    <canvas id="roiChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Table -->
<div class="row g-4">
    <div class="col-12 fade-in-up">
        <div class="ff-card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-table me-2 text-accent"></i>Vehicle Cost Breakdown</h6>
                <div class="table-responsive">
                    <table class="table ff-table">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Trips</th>
                                <th>Distance (km)</th>
                                <th>Fuel Cost</th>
                                <th>Maint. Cost</th>
                                <th>Total Cost</th>
                                <th>Acquisition</th>
                                <th>ROI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicleROI as $v): ?>
                            <tr>
                                <td class="fw-semibold"><?= sanitize($v['vehicle_name']) ?><br><small class="text-muted-light"><?= sanitize($v['license_plate']) ?></small></td>
                                <td><?= $v['trip_count'] ?></td>
                                <td><?= number_format($v['total_distance']) ?></td>
                                <td class="text-warning"><?= formatCurrency($v['fuel_cost']) ?></td>
                                <td class="text-danger"><?= formatCurrency($v['maint_cost']) ?></td>
                                <td class="fw-semibold"><?= formatCurrency($v['fuel_cost'] + $v['maint_cost']) ?></td>
                                <td><?= formatCurrency($v['acquisition_cost']) ?></td>
                                <td><span class="badge <?= $v['roi'] >= 0 ? 'bg-success' : 'bg-danger' ?>"><?= $v['roi'] ?>%</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fuel Efficiency Bar Chart
new Chart(document.getElementById('fuelEffChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($effLabels) ?>,
        datasets: [{
            label: 'km/L',
            data: <?= json_encode($effData) ?>,
            backgroundColor: 'rgba(79, 140, 255, 0.6)',
            borderColor: '#4f8cff',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
            x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } }
        }
    }
});

// Monthly Expense Chart
new Chart(document.getElementById('monthlyExpChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($expMonths) ?>,
        datasets: [
            {
                label: 'Fuel',
                data: <?= json_encode($expFuel) ?>,
                borderColor: '#fbbf24',
                backgroundColor: 'rgba(251, 191, 36, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4
            },
            {
                label: 'Maintenance',
                data: <?= json_encode($expMaint) ?>,
                borderColor: '#f87171',
                backgroundColor: 'rgba(248, 113, 113, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#94a3b8' } } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
            x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
        }
    }
});

// ROI Bar Chart
new Chart(document.getElementById('roiChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($roiLabels) ?>,
        datasets: [{
            label: 'ROI %',
            data: <?= json_encode($roiData) ?>,
            backgroundColor: <?= json_encode($roiColors) ?>,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
            y: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 12 } } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

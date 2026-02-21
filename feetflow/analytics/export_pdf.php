<?php
/**
 * FleetFlow – PDF Export (Print-friendly HTML)
 * Opens a clean printable page that users can save as PDF via browser
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Financial Analyst']);

$db = getDB();

$data = $db->query("
    SELECT v.vehicle_name, v.license_plate, v.vehicle_type, v.acquisition_cost,
        COALESCE(fc.fuel_total, 0) as fuel_cost,
        COALESCE(mc.maint_total, 0) as maint_cost,
        COALESCE(tc.trip_count, 0) as trip_count,
        COALESCE(tc.total_distance, 0) as total_distance,
        CASE WHEN COALESCE(fc.fuel_liters, 0) > 0
            THEN ROUND(COALESCE(tc.total_distance, 0) / fc.fuel_liters, 2)
            ELSE 0 END as fuel_efficiency,
        CASE WHEN v.acquisition_cost > 0
            THEN ROUND(((COALESCE(tc.total_distance, 0) * 2) - (COALESCE(fc.fuel_total, 0) + COALESCE(mc.maint_total, 0))) / v.acquisition_cost * 100, 2)
            ELSE 0 END as roi
    FROM vehicles v
    LEFT JOIN (SELECT vehicle_id, SUM(cost) as fuel_total, SUM(liters) as fuel_liters FROM fuel_logs GROUP BY vehicle_id) fc ON fc.vehicle_id = v.id
    LEFT JOIN (SELECT vehicle_id, SUM(cost) as maint_total FROM maintenance_logs GROUP BY vehicle_id) mc ON mc.vehicle_id = v.id
    LEFT JOIN (SELECT vehicle_id, COUNT(*) as trip_count, SUM(distance) as total_distance FROM trips WHERE status='Completed' GROUP BY vehicle_id) tc ON tc.vehicle_id = v.id
    WHERE v.status != 'Retired'
    ORDER BY v.vehicle_name
")->fetchAll();

$totalFuel = array_sum(array_column($data, 'fuel_cost'));
$totalMaint = array_sum(array_column($data, 'maint_cost'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FleetFlow Analytics Report – <?= date('Y-m-d') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a2e; padding: 40px; font-size: 13px; }
        h1 { font-size: 22px; margin-bottom: 5px; color: #1a1a2e; }
        .subtitle { color: #666; margin-bottom: 25px; font-size: 13px; }
        .summary { display: flex; gap: 20px; margin-bottom: 25px; }
        .summary-box { background: #f5f7fa; padding: 12px 20px; border-radius: 8px; flex: 1; }
        .summary-box .label { font-size: 11px; color: #888; text-transform: uppercase; }
        .summary-box .value { font-size: 20px; font-weight: 700; color: #1a1a2e; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1a1a2e; color: #fff; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        td { padding: 9px 12px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background: #f9fafb; }
        .positive { color: #059669; font-weight: 600; }
        .negative { color: #dc2626; font-weight: 600; }
        .footer { margin-top: 30px; text-align: center; color: #999; font-size: 11px; }
        @media print {
            body { padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:20px">
        <button onclick="window.print()" style="padding:8px 20px; background:#1a1a2e; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:13px;">
            🖨️ Print / Save as PDF
        </button>
        <button onclick="window.close()" style="padding:8px 20px; background:#e5e7eb; color:#333; border:none; border-radius:6px; cursor:pointer; font-size:13px; margin-left:8px;">
            Close
        </button>
    </div>

    <h1>🚛 FleetFlow Analytics Report</h1>
    <p class="subtitle">Generated: <?= date('F j, Y – g:i A') ?></p>

    <div class="summary">
        <div class="summary-box">
            <div class="label">Total Fuel Cost</div>
            <div class="value"><?= formatCurrency($totalFuel) ?></div>
        </div>
        <div class="summary-box">
            <div class="label">Total Maintenance Cost</div>
            <div class="value"><?= formatCurrency($totalMaint) ?></div>
        </div>
        <div class="summary-box">
            <div class="label">Total Operations Cost</div>
            <div class="value"><?= formatCurrency($totalFuel + $totalMaint) ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Plate</th>
                <th>Trips</th>
                <th>Distance</th>
                <th>Fuel ($)</th>
                <th>Maint. ($)</th>
                <th>Total ($)</th>
                <th>Efficiency</th>
                <th>ROI</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><strong><?= sanitize($row['vehicle_name']) ?></strong></td>
                <td><?= sanitize($row['license_plate']) ?></td>
                <td><?= $row['trip_count'] ?></td>
                <td><?= number_format($row['total_distance']) ?> km</td>
                <td><?= formatCurrency($row['fuel_cost']) ?></td>
                <td><?= formatCurrency($row['maint_cost']) ?></td>
                <td><strong><?= formatCurrency($row['fuel_cost'] + $row['maint_cost']) ?></strong></td>
                <td><?= $row['fuel_efficiency'] ?> km/L</td>
                <td class="<?= $row['roi'] >= 0 ? 'positive' : 'negative' ?>"><?= $row['roi'] ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>FleetFlow – Modular Fleet & Logistics Management System</p>
    </div>
</body>
</html>

<?php
/**
 * FleetFlow – CSV Export
 * Exports vehicle analytics data as CSV
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Financial Analyst']);

$db = getDB();

$data = $db->query("
    SELECT v.vehicle_name, v.license_plate, v.vehicle_type, v.acquisition_cost, v.status,
        COALESCE(fc.fuel_total, 0) as fuel_cost,
        COALESCE(fc.fuel_liters, 0) as total_liters,
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

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=fleetflow_analytics_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// CSV header
fputcsv($output, ['Vehicle', 'License Plate', 'Type', 'Status', 'Trips', 'Distance (km)', 'Fuel Cost ($)', 'Fuel (L)', 'Efficiency (km/L)', 'Maintenance Cost ($)', 'Total Cost ($)', 'Acquisition Cost ($)', 'ROI (%)']);

foreach ($data as $row) {
    fputcsv($output, [
        $row['vehicle_name'],
        $row['license_plate'],
        $row['vehicle_type'],
        $row['status'],
        $row['trip_count'],
        $row['total_distance'],
        $row['fuel_cost'],
        $row['total_liters'],
        $row['fuel_efficiency'],
        $row['maint_cost'],
        round($row['fuel_cost'] + $row['maint_cost'], 2),
        $row['acquisition_cost'],
        $row['roi'],
    ]);
}

fclose($output);
exit;

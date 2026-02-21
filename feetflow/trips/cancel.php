<?php
/**
 * FleetFlow – Cancel Trip
 * Restore vehicle and driver statuses
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Dispatcher']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { redirect(baseUrl() . '/trips/'); }

$stmt = $db->prepare("SELECT * FROM trips WHERE id = :id AND status IN ('Draft','Dispatched')");
$stmt->execute([':id' => $id]);
$trip = $stmt->fetch();

if (!$trip) {
    setFlash('danger', 'Trip not found or cannot be cancelled.');
    redirect(baseUrl() . '/trips/');
}

$db->beginTransaction();
try {
    $stmt = $db->prepare("UPDATE trips SET status = 'Cancelled' WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Restore statuses only if trip was Dispatched
    if ($trip['status'] === 'Dispatched') {
        $stmt = $db->prepare("UPDATE vehicles SET status = 'Available' WHERE id = :id");
        $stmt->execute([':id' => $trip['vehicle_id']]);

        $stmt = $db->prepare("UPDATE drivers SET status = 'Off Duty' WHERE id = :id");
        $stmt->execute([':id' => $trip['driver_id']]);
    }

    $db->commit();
    setFlash('success', 'Trip cancelled.');
} catch (Exception $e) {
    $db->rollBack();
    setFlash('danger', 'Failed to cancel trip.');
}

redirect(baseUrl() . '/trips/');

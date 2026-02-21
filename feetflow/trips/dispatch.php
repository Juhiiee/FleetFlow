<?php
/**
 * FleetFlow – Dispatch Trip
 * Sets trip → Dispatched, vehicle → On Trip, driver → On Duty
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager','Dispatcher']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { redirect(baseUrl() . '/trips/'); }

$stmt = $db->prepare("SELECT * FROM trips WHERE id = :id AND status = 'Draft'");
$stmt->execute([':id' => $id]);
$trip = $stmt->fetch();

if (!$trip) {
    setFlash('danger', 'Trip not found or not in Draft status.');
    redirect(baseUrl() . '/trips/');
}

// Begin transaction for atomic update
$db->beginTransaction();
try {
    // Update trip status
    $stmt = $db->prepare("UPDATE trips SET status = 'Dispatched', dispatched_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Vehicle → On Trip
    $stmt = $db->prepare("UPDATE vehicles SET status = 'On Trip' WHERE id = :id");
    $stmt->execute([':id' => $trip['vehicle_id']]);

    // Driver → On Duty
    $stmt = $db->prepare("UPDATE drivers SET status = 'On Duty' WHERE id = :id");
    $stmt->execute([':id' => $trip['driver_id']]);

    $db->commit();
    setFlash('success', 'Trip dispatched! Vehicle and driver status updated.');
} catch (Exception $e) {
    $db->rollBack();
    setFlash('danger', 'Failed to dispatch trip: ' . $e->getMessage());
}

redirect(baseUrl() . '/trips/');

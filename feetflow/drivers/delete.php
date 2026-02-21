<?php
/**
 * FleetFlow – Delete Driver
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { redirect(baseUrl() . '/drivers/'); }

// Check for active trips
$stmt = $db->prepare("SELECT COUNT(*) FROM trips WHERE driver_id = :id AND status IN ('Draft','Dispatched')");
$stmt->execute([':id' => $id]);
if ($stmt->fetchColumn() > 0) {
    setFlash('danger', 'Cannot delete driver with active trips.');
    redirect(baseUrl() . '/drivers/');
}

$stmt = $db->prepare("DELETE FROM drivers WHERE id = :id");
$stmt->execute([':id' => $id]);
setFlash('success', 'Driver deleted successfully.');
redirect(baseUrl() . '/drivers/');

<?php
/**
 * FleetFlow – Delete Vehicle
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { redirect(baseUrl() . '/vehicles/'); }

// Check if vehicle has active trips
$stmt = $db->prepare("SELECT COUNT(*) FROM trips WHERE vehicle_id = :id AND status IN ('Draft','Dispatched')");
$stmt->execute([':id' => $id]);
if ($stmt->fetchColumn() > 0) {
    setFlash('danger', 'Cannot delete vehicle with active trips.');
    redirect(baseUrl() . '/vehicles/');
}

$stmt = $db->prepare("DELETE FROM vehicles WHERE id = :id");
$stmt->execute([':id' => $id]);
setFlash('success', 'Vehicle deleted successfully.');
redirect(baseUrl() . '/vehicles/');

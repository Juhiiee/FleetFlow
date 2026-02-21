<?php
/**
 * FleetFlow – Delete Maintenance Log
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();
requireRole(['Fleet Manager']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { redirect(baseUrl() . '/maintenance/'); }

$stmt = $db->prepare("DELETE FROM maintenance_logs WHERE id = :id");
$stmt->execute([':id' => $id]);
setFlash('success', 'Maintenance log deleted.');
redirect(baseUrl() . '/maintenance/');

<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$category = $_GET['category'] ?? '';
$search   = $_GET['search'] ?? '';

$sql = "SELECT * FROM parts WHERE 1=1";
$params = [];
$types  = '';

if ($category) {
    $sql    .= " AND category = ?";
    $types  .= 's';
    $params[] = $category;
}
if ($search) {
    $sql    .= " AND name LIKE ?";
    $types  .= 's';
    $like     = "%$search%";
    $params[] = $like;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$parts = [];
while ($row = $result->fetch_assoc()) $parts[] = $row;

echo json_encode(['status' => 'success', 'parts' => $parts]);
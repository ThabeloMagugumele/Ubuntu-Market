<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

$q = sanitize($_GET['q'] ?? '');
if (strlen($q) < 2) { echo '[]'; exit; }

$pdo  = getDB();
$stmt = $pdo->prepare(
    'SELECT id, title, price, image_main FROM listings
     WHERE status = "active" AND (title LIKE ? OR description LIKE ?)
     ORDER BY views DESC LIMIT 6'
);
$like = '%' . $q . '%';
$stmt->execute([$like, $like]);
echo json_encode($stmt->fetchAll());

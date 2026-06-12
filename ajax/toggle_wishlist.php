<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['status' => 'login_required']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['status' => 'error']); exit; }

$listingId = (int)($_POST['listing_id'] ?? 0);
$userId    = (int)$_SESSION['user_id'];
if (!$listingId) { echo json_encode(['status' => 'error']); exit; }

$pdo  = getDB();
$chk  = $pdo->prepare('SELECT id FROM wishlist WHERE user_id = ? AND listing_id = ?');
$chk->execute([$userId, $listingId]);

if ($chk->fetch()) {
    $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND listing_id = ?')->execute([$userId, $listingId]);
    echo json_encode(['status' => 'removed']);
} else {
    $pdo->prepare('INSERT INTO wishlist (user_id, listing_id) VALUES (?,?)')->execute([$userId, $listingId]);
    echo json_encode(['status' => 'added']);
}

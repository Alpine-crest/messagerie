<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit; }
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare(
    "SELECT u.username, MAX(m.created_at) as last_msg
     FROM users u
     JOIN messages m ON (u.id = m.sender_id AND m.receiver_id = ?) OR (u.id = m.receiver_id AND m.sender_id = ?)
     WHERE u.id != ?
     GROUP BY u.id
     ORDER BY last_msg DESC"
);
$stmt->execute([$user_id, $user_id, $user_id]);
echo json_encode(['status'=>'success','conversations'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
?>
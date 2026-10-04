<?php
require '../../connect_pgsql.php'; // Database connection
require '../../connect_redis.php'; // Redis connection
session_start();
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $_SESSION['user_id'];
$amount = $data['amount'] ?? 0;

if ($amount > 0) {
    // Deduct coins only if the user has enough
    $sql = "UPDATE users SET coins = coins - :amount WHERE id = :user_id AND coins >= :amount";
    $stmt = $pgsql->prepare($sql);
    $stmt->execute(['amount' => $amount, 'user_id' => $user_id]);

    // Get updated coin count
    $sql = "SELECT coins FROM users WHERE id = :user_id";
    $stmt = $pgsql->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // **Delete Redis key only when amount is exactly 100**
    if ($amount == 100) {
        $redis->del("question1_".$user_id);
    }

    // Return updated coins
    echo json_encode([
        'status' => 'success',
        'updated_coins' => $user['coins']
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid amount']);
}
?>

<?php include "../../connect_redis.php"; ?>

<?php

// Remove session_start() if not needed
session_start();

$user_id = $_SESSION['user_id'];

$quiz_key = "quiz_timer_user_1_".$user_id;

if ($redis->exists($quiz_key)) {
    
    $redis->del($quiz_key); // Delete timer key
    // echo $redis->get($quiz_key);
    echo json_encode(["message" => "Timer stopped."]);
} else {
    echo json_encode(["message" => "No active timer."]);
}
?>

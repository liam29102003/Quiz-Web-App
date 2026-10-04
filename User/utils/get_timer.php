<?php include "../../connect_redis.php"; ?>

<?php
session_start();
$user_id = $_SESSION['user_id'];
$quiz_key = "quiz_timer_user_1";
$start_time = $redis->get($quiz_key."_".$user_id);

if (!$start_time) {
    $start_time = time();
    $redis->setex($quiz_key."_".$user_id, 33, $start_time); // Set timer for 1000 seconds
}

$remaining_time = $redis->ttl($quiz_key."_".$user_id);
// $redis->del($quiz_key)
echo json_encode(["remaining_time" => max(0, $remaining_time)]);
                                                                                                                                                                                           
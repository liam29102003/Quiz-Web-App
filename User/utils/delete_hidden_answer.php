<?php
require '../../connect_redis.php'; // Include Redis connection file
session_start();
$user_id = $_SESSION['user_id']; // Redis key for the current question
$hiddenAnswerKey = "hidden_answer_" . $user_id; // Unique key for hidden answer per question

$data = json_decode(file_get_contents("php://input"), true);

// Check if an answer is already hidden for this question
if ($redis->exists($hiddenAnswerKey)) {
    $redis->del($hiddenAnswerKey); // Store the hidden answer
}
?>

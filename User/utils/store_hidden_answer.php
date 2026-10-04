<?php
require '../../connect_redis.php'; // Include Redis connection file
session_start();
$user_id = $_SESSION['user_id']; // Redis key for the current question
$hiddenAnswerKey = "hidden_answer_" . $user_id; // Unique key for hidden answer per question

$data = json_decode(file_get_contents("php://input"), true);
$answer = $data['answer'] ?? '';

// Check if an answer is already hidden for this question
if (!empty($answer) && !$redis->exists($hiddenAnswerKey)) {
    $redis->set($hiddenAnswerKey, json_encode($answer)); // Store the hidden answer
}

// Call update_coins.php to update coins
$coinUpdateResponse = file_get_contents("http://localhost/Quiz/User/utils/update_coins.php", false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode(['user_id' => $user_id, 'amount' => 30])
    ]
]));

echo $coinUpdateResponse; // Return the coin update response to JavaScript
?>

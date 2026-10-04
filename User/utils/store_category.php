<?php 
include "../../connect_pgsql.php";
require '../../connect_redis.php'; // Ensure Redis connection
session_start();
$user_id = $_SESSION['user_id'];
header("Content-Type: application/json");

// Get category from AJAX request
$data = json_decode(file_get_contents("php://input"), true);
$category = $data['category'] ?? null;

if ($category) {
    // Store category in Redis
    $redis->set("user_category_".$user_id, $category);

    // Assuming you have a user ID stored in a session or passed from frontend
    session_start();
    $user_id = $_SESSION['user_id']; // Adjust this as needed

    if ($user_id) {
        // Get user score from PostgreSQL
        $sql = "SELECT score FROM users WHERE id = :user_id";
        $stmt = $pgsql->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Decode JSON score data
        $scoreData = json_decode($user['score'], true);
        $score = $scoreData[strtolower($category)] ?? 0;

        // Return response as JSON
        echo json_encode([
            "message" => "Category stored in Redis",
            "score" => $score
        ]);
    } else {
        echo json_encode(["message" => "User ID not found", "score" => null]);
    }
} else {
    echo json_encode(["message" => "Category not provided", "score" => null]);
}
?>

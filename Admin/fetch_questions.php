<?php
require '../User/connect.php';
header("Content-Type: application/json");

// Validate input parameters
$category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
$amount = isset($_GET['amount']) ? (int) $_GET['amount'] : 10;
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'medium';

// Ensure valid inputs
if ($category_id <= 0 || $amount < 1) {
    echo json_encode(["error" => "Invalid category or amount."]);
    exit;
}

// Get API ID from database
$categoryQuery = pg_query_params($conn, "SELECT api_id, category_name FROM category WHERE id = $1", [$category_id]);
$categoryData = pg_fetch_assoc($categoryQuery);

// Validate category existence
if (!$categoryData) {
    echo json_encode(["error" => "Invalid category selected."]);
    exit;
}

$apiCategoryId = $categoryData['api_id'];
$categoryName = $categoryData['category_name'];

// OpenTDB API URL
$apiUrl = "https://opentdb.com/api.php?amount={$amount}&category={$apiCategoryId}&difficulty={$difficulty}&type=multiple";

// Fetch questions from OpenTDB
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

// Log the API URL and response (for debugging)
file_put_contents("debug_log.txt", "🔹 Generated API URL: {$apiUrl}" . PHP_EOL, FILE_APPEND);
file_put_contents("debug_log.txt", "🔹 OpenTDB API Response: " . print_r($data, true) . PHP_EOL, FILE_APPEND);

// Check API response
if (!$data || !isset($data['results']) || count($data['results']) == 0) {
    echo json_encode(["error" => "No questions available for this category."]);
    exit;
}

// Prepare questions for UI
$questions = [];

foreach ($data['results'] as $question) {
    $questions[] = [
        "question" => htmlspecialchars_decode($question['question']),
        "incorrect_answers" => array_map('htmlspecialchars_decode', $question['incorrect_answers']),
        "correct_answer" => htmlspecialchars_decode($question['correct_answer']),
        "category_name" => $categoryName, // Use the database category name
        "difficulty_level" => ucfirst($question['difficulty']),
    ];
}

// Send JSON response
echo json_encode(["questions" => $questions], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
?>

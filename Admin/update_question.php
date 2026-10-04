<?php
header("Content-Type: application/json");
require_once("../User/connect.php");

// Enable error logging (for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["error" => "Invalid request method"]);
    exit;
}

// Read input data (JSON format)
$raw_data = file_get_contents("php://input");
$data = json_decode($raw_data, true);

if (!$data || !isset($data['id'], $data['question'], $data['incorrect_answers'], $data['correct_answer'], $data['difficulty_level'])) {
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// Sanitize input values
$id = (int) $data['id'];
$question = trim($data['question']);
$incorrect_answers = $data['incorrect_answers']; // Expecting an array
$correct_answer = trim($data['correct_answer']);
$difficulty_level = strtolower(trim($data['difficulty_level'])); // Ensure consistency

// ✅ Validate difficulty level (must be easy, medium, or hard)
$valid_difficulty_levels = ["easy", "medium", "hard"];
if (!in_array($difficulty_level, $valid_difficulty_levels)) {
    echo json_encode(["error" => "Invalid difficulty level. Must be 'easy', 'medium', or 'hard'."]);
    exit;
}

// ✅ Ensure incorrect answers is an array & properly formatted
if (!is_array($incorrect_answers) || count($incorrect_answers) < 1) {
    echo json_encode(["error" => "Incorrect answers must be a non-empty array."]);
    exit;
}

// Convert incorrect answers to PostgreSQL array format
function toPgArray($arr) {
    global $conn; // Ensure database connection is available

    // Trim and escape answers, ensuring no empty values
    $cleaned_answers = array_filter(array_map(fn($val) => trim($val), $arr));

    if (empty($cleaned_answers)) {
        return '{}'; // Return empty PostgreSQL array
    }

    // Format as a PostgreSQL array
    $formattedArray = "{" . implode(",", array_map(fn($val) => '"' . pg_escape_string($conn, $val) . '"', $cleaned_answers)) . "}";
    return $formattedArray;
}

$incorrect_answers_pg = toPgArray($incorrect_answers);

// ✅ Check if the updated question already exists (case-insensitive)
$check_query = "SELECT COUNT(*) FROM question WHERE LOWER(question) = LOWER($1) AND id != $2";
$check_stmt = pg_prepare($conn, "check_question_exists", $check_query);
$check_result = pg_execute($conn, "check_question_exists", [$question, $id]);

if (!$check_result) {
    echo json_encode(["error" => "Database error while checking duplicates: " . pg_last_error($conn)]);
    exit;
}

$existing_count = pg_fetch_result($check_result, 0, 0);
if ($existing_count > 0) {
    echo json_encode(["error" => "A similar question already exists in the database!"]);
    exit;
}

// ✅ Update question in database
$query = "UPDATE question SET question = $1, incorrect_answers = $2::TEXT[], correct_answer = $3, difficulty_level = $4 WHERE id = $5";
$stmt = pg_prepare($conn, "update_question", $query);
$result = pg_execute($conn, "update_question", [$question, $incorrect_answers_pg, $correct_answer, $difficulty_level, $id]);

if (!$result) {
    echo json_encode(["error" => "Failed to update question: " . pg_last_error($conn)]);
    exit;
}

echo json_encode(["success" => "Question updated successfully!"]);
exit;
?>

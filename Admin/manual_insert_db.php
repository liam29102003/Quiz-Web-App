<?php
require_once("../User/connect.php");

// Ensure no prior output
ob_start();
header("Content-Type: application/json; charset=UTF-8");

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["error" => "Invalid request method."]);
    exit;
}

// Read the JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Debug: Log input data (DO NOT REMOVE until issue is fixed)
file_put_contents("debug.log", print_r($data, true));

if (!$data) {
    echo json_encode(["error" => "Invalid JSON received."]);
    exit;
}

// Validate required fields
if (!isset($data['question'], $data['incorrect_answers'], $data['correct_answer'], $data['category_id'], $data['difficulty_level'])) {
    echo json_encode(["error" => "Missing required fields."]);
    exit;
}

// Sanitize input
$question = trim($data['question']);
$incorrect_answers = $data['incorrect_answers'];
$correct_answer = trim($data['correct_answer']);
$category_id = (int)$data['category_id'];
$difficulty_level = strtolower(trim($data['difficulty_level'])); // Ensure lowercase consistency

// ✅ Validate difficulty level (must be easy, medium, or hard)
$valid_difficulty_levels = ["easy", "medium", "hard"];
if (!in_array($difficulty_level, $valid_difficulty_levels)) {
    echo json_encode(["error" => "Invalid difficulty level. Must be 'easy', 'medium', or 'hard'."]);
    exit;
}

// Validate category ID
if ($category_id <= 0) {
    echo json_encode(["error" => "Invalid category selected."]);
    exit;
}

// Ensure incorrect_answers is an array
if (!is_array($incorrect_answers) || count($incorrect_answers) < 1) {
    echo json_encode(["error" => "Incorrect answers must be an array."]);
    exit;
}

// Convert incorrect answers to PostgreSQL array format
function toPgArray($arr) {
    return "{" . implode(",", array_map(fn($val) => '"' . pg_escape_string($val) . '"', $arr)) . "}";
}

$incorrect_answers_pg = toPgArray($incorrect_answers);

// ✅ **CHECK FOR DUPLICATE QUESTION**
$check_query = "SELECT COUNT(*) FROM question WHERE LOWER(question) = LOWER($1)";
$check_stmt = pg_prepare($conn, "check_question", $check_query);
$check_result = pg_execute($conn, "check_question", [$question]);
$existing_count = pg_fetch_result($check_result, 0, 0);

// ❌ If the question already exists, return an error
if ($existing_count > 0) {
    echo json_encode(["error" => "This question already exists in the database!"]);
    exit;
}

// ✅ **CHECK FOR DUPLICATE QUESTION WITH SAME ANSWERS**
$check_answers_query = "SELECT COUNT(*) FROM question WHERE LOWER(question) = LOWER($1) AND incorrect_answers = $2 AND correct_answer = $3";
$check_answers_stmt = pg_prepare($conn, "check_question_answers", $check_answers_query);
$check_answers_result = pg_execute($conn, "check_question_answers", [$question, $incorrect_answers_pg, $correct_answer]);

$existing_answers_count = pg_fetch_result($check_answers_result, 0, 0);

// ❌ If the same question with the same answers exists, return an error
if ($existing_answers_count > 0) {
    echo json_encode(["error" => "This question with the same answers already exists in the database!"]);
    exit;
}

// ✅ Insert the new question
$query = "INSERT INTO question (question, incorrect_answers, correct_answer, category_id, difficulty_level) VALUES ($1, $2, $3, $4, $5)";
$stmt = pg_prepare($conn, "insert_question", $query);
$result = pg_execute($conn, "insert_question", [$question, $incorrect_answers_pg, $correct_answer, $category_id, $difficulty_level]);

// If insertion is successful, return JSON response
if ($result) {
    echo json_encode(["success" => "Question added successfully!"]);
} else {
    echo json_encode(["error" => "Failed to insert question: " . pg_last_error($conn)]);
}

// Prevent unwanted output
ob_end_flush();
exit;
?>

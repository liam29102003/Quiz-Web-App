<?php
ob_start(); // Prevent unwanted output
header("Content-Type: application/json");
require '../User/connect.php';

// Capture Raw Data
$raw_data = file_get_contents("php://input");
$data = json_decode($raw_data, true);

// Validate JSON Input
if (!$data || !isset($data['question'], $data['incorrect_answers'], $data['correct_answer'], $data['category_id'], $data['difficulty_level'])) {
    echo json_encode(["error" => "Invalid input data."]);
    exit;
}

// Convert incorrect_answers array into PostgreSQL array format
function toPgArray($array, $conn) {
    if (!is_array($array) || empty($array)) return '{}';
    $formattedArray = array_map(function ($val) use ($conn) {
        return '"' . pg_escape_string($conn, trim((string)$val)) . '"';
    }, array_filter($array));
    return '{' . implode(',', $formattedArray) . '}';
}

// Prepare Data
$question_text = trim($data['question']);
$incorrect_answers_pg = toPgArray($data['incorrect_answers'], $conn);
$correct_answer = trim($data['correct_answer']);
$difficulty_level = strtolower(trim($data['difficulty_level']));
$category_id = (int) $data['category_id'];

// Validate Category ID
$categoryQuery = pg_query_params($conn, "SELECT id FROM category WHERE id = $1", [$category_id]);
if (!$categoryQuery || pg_num_rows($categoryQuery) == 0) {
    echo json_encode(["error" => "Invalid category selected."]);
    exit;
}

// Check for Duplicate Question
$check_query = "SELECT COUNT(*) FROM question WHERE question = $1";
$check_stmt = pg_query_params($conn, $check_query, [$question_text]);

if (!$check_stmt) {
    echo json_encode(["error" => "Error checking for duplicates: " . pg_last_error($conn)]);
    exit;
}

$count = pg_fetch_result($check_stmt, 0, 0);
if ($count > 0) {
    echo json_encode(["error" => "⚠️ This question already exists!"]);
    exit;
}

// Insert Question into Database
$insert_query = "INSERT INTO question (question, incorrect_answers, correct_answer, category_id, difficulty_level) 
                 VALUES ($1, $2::TEXT[], $3, $4, $5)";

$insert_stmt = pg_query_params($conn, $insert_query, [$question_text, $incorrect_answers_pg, $correct_answer, $category_id, $difficulty_level]);

// Log the Database Execution
if (!$insert_stmt) {
    echo json_encode(["error" => "Failed to insert question: " . pg_last_error($conn)]);
    exit;
}

// Success Response
ob_clean(); // Ensure no unwanted output
echo json_encode(["success" => "✅ Question added successfully!"], JSON_UNESCAPED_UNICODE);
exit;
?>

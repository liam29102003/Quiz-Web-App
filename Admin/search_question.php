<?php
require '../User/connect.php';

// Set the response header to JSON
header("Content-Type: application/json");

// Get the search query parameter and trim any surrounding whitespace
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;  // Default to page 1
$limit = 20;  // Number of results per page
$offset = ($page - 1) * $limit;  // Offset for pagination

// If the search query is empty, return an empty JSON array
if ($query === '') {
    echo json_encode([]);
    exit;
}

// Prepare the SQL query with a parameterized search for case-insensitive matching and pagination
$searchQuery = "SELECT q.id, q.question, q.incorrect_answers, q.correct_answer, q.difficulty_level, c.category_name
                FROM question q 
                JOIN category c ON q.category_id = c.id 
                WHERE LOWER(q.question) LIKE LOWER($1) 
                ORDER BY q.id ASC
                LIMIT $2 OFFSET $3";  // Limit and offset for pagination

// Execute the query with the user input, page limit, and offset
$result = pg_query_params($conn, $searchQuery, ["%$query%", $limit, $offset]);

if (!$result) {
    // In case of a query error, return an error response
    echo json_encode(['error' => 'Database query failed']);
    exit;
}

// Fetch all the result rows
$questions = pg_fetch_all($result) ?: [];

// Get the total number of questions to calculate the total pages
$totalQuery = "SELECT COUNT(*) FROM question WHERE LOWER(question) LIKE LOWER($1)";
$totalResult = pg_query_params($conn, $totalQuery, ["%$query%"]);
$totalRow = pg_fetch_row($totalResult);
$totalQuestions = intval($totalRow[0]);

// Calculate total pages
$totalPages = ceil($totalQuestions / $limit);

// Return the results and pagination info as a JSON object
echo json_encode([
    'questions' => $questions,
    'total_pages' => $totalPages,
    'current_page' => $page
]);
?>

<?php
require '../User/connect.php';
header('Content-Type: application/json');

$query = $_GET['query'] ?? '';

// Ensure safe query execution
$searchQuery = "SELECT * FROM category WHERE category_name ILIKE $1 ORDER BY id ASC";
$result = pg_query_params($conn, $searchQuery, ["%$query%"]);

$categories = [];
while ($row = pg_fetch_assoc($result)) {
    $categories[] = [
        'id' => $row['id'],
        'category_name' => $row['category_name'],
        'category_photo' => !empty($row['category_photo']) ? $row['category_photo'] : null,
        'is_api' => ($row['is_api'] === 't' || $row['is_api'] === 'true' || $row['is_api'] == 1) ? true : false,
    ];
}

echo json_encode($categories);

?>

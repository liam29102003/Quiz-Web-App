<?php
require '../User/connect.php';
header("Content-Type: application/json");

// Ensure output is clean before JSON response
if (ob_get_length()) ob_end_clean();

// Fetch API categories from OpenTDB
$apiUrl = "https://opentdb.com/api_category.php";
$response = file_get_contents($apiUrl);
$apiData = json_decode($response, true);

// Validate API response
if (!$apiData || !isset($apiData['trivia_categories'])) {
    echo json_encode(["error" => "Failed to fetch categories from API."]);
    exit;
}

$categories = [];

foreach ($apiData['trivia_categories'] as $category) {
    $apiCategoryId = $category["id"];
    $apiCategoryName = $category["name"];

    // Check if the category exists by API ID
    $existsQuery = pg_query_params($conn, "SELECT id FROM category WHERE api_id = $1", [$apiCategoryId]);
    if ($existsQuery && $existingCategory = pg_fetch_assoc($existsQuery)) {
        $categoryId = $existingCategory['id']; // Use existing category ID
    } else {
        // Check if a category with the same name exists (but without an API ID)
        $existsNameQuery = pg_query_params($conn, "SELECT id FROM category WHERE category_name = $1 AND api_id IS NULL", [$apiCategoryName]);
        if ($existsNameQuery && $existingName = pg_fetch_assoc($existsNameQuery)) {
            // Update existing category with API ID
            $updateQuery = pg_query_params($conn, "UPDATE category SET api_id = $1, is_api = TRUE WHERE id = $2 RETURNING id", [$apiCategoryId, $existingName['id']]);
            $categoryId = $updateQuery ? pg_fetch_result($updateQuery, 0, 0) : $existingName['id'];
        } else {
            // Insert new category with API ID
            $insertQuery = pg_query_params($conn, 
                "INSERT INTO category (category_name, api_id, is_api) VALUES ($1, $2, TRUE) RETURNING id",
                [$apiCategoryName, $apiCategoryId]
            );
            $categoryId = $insertQuery ? pg_fetch_result($insertQuery, 0, 0) : null;
        }
    }

    // Append to categories list
    if ($categoryId) {
        $categories[] = [
            "id" => $categoryId,     // Local database ID
            "api_id" => $apiCategoryId, // API ID
            "name" => $apiCategoryName,
            "is_api" => true
        ];
    }
}

// Send JSON response
echo json_encode(["categories" => $categories], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
?>

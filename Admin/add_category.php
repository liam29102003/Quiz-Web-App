<?php
require '../User/connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category_name = trim($_POST['category_name'] ?? '');
    $default_photo = "default.png"; // ✅ Default image file
    $category_photo = $default_photo; // ✅ Set default first

    // ✅ Validate category name
    if (empty($category_name)) {
        echo json_encode(["success" => false, "message" => "Category name cannot be empty."]);
        exit;
    }

    // ✅ Check if category name already exists (case-insensitive)
    $check_query = "SELECT COUNT(*) FROM category WHERE LOWER(category_name) = LOWER($1)";
    $check_result = pg_query_params($conn, $check_query, [$category_name]);
    $row = pg_fetch_row($check_result);

    if ($row[0] > 0) {
        echo json_encode(["success" => false, "message" => "Category name already exists."]);
        exit;
    }

    // ✅ Handle file upload (if provided)
    if (!empty($_FILES['category_photo']['name'])) {
        $upload_dir = "../images/categories/";
        $file_name = basename($_FILES["category_photo"]["name"]);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES["category_photo"]["tmp_name"], $target_file)) {
            $category_photo = $file_name; // ✅ Update with uploaded image
        }
    }

    // ✅ Insert into database (Use default image if no upload)
    $query = "INSERT INTO category (category_name, category_photo, is_api) VALUES ($1, $2, 'false')";
    $result = pg_query_params($conn, $query, [$category_name, $category_photo]);

    if ($result) {
        echo json_encode(["success" => true, "message" => "Category added successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . pg_last_error($conn)]);
    }
}
?>

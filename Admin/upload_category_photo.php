<?php
require '../User/connect.php';

header("Content-Type: application/json"); // ✅ Ensure response is JSON format

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["category_photo"])) {
    $category_name = trim($_POST['category_name']);

    // ✅ Validate category name
    if (empty($category_name)) {
        echo json_encode(["success" => false, "message" => "Category name is required."]);
        exit;
    }

    // ✅ Ensure 'uploads/' directory exists
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // ✅ Validate file upload
    if ($_FILES["category_photo"]["error"] !== 0) {
        echo json_encode(["success" => false, "message" => "File upload error."]);
        exit;
    }

    // ✅ Allowed file types
    $allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
    $fileType = mime_content_type($_FILES["category_photo"]["tmp_name"]);

    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(["success" => false, "message" => "Invalid file type. Allowed: JPG, PNG, GIF, WEBP."]);
        exit;
    }

    // ✅ Generate a unique filename to prevent overwriting
    $fileExt = pathinfo($_FILES["category_photo"]["name"], PATHINFO_EXTENSION);
    $uniqueFileName = uniqid("category_", true) . "." . $fileExt;
    $uploadFile = $uploadDir . $uniqueFileName;

    // ✅ Move uploaded file to the 'uploads/' directory
    if (move_uploaded_file($_FILES["category_photo"]["tmp_name"], $uploadFile)) {
        // ✅ Save only the file name in the database (not the full path)
        $query = "INSERT INTO category (category_name, category_photo) VALUES ($1, $2)";
        $result = pg_query_params($conn, $query, [$category_name, $uniqueFileName]);

        if ($result) {
            echo json_encode(["success" => true, "message" => "Category added successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . pg_last_error($conn)]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Image upload failed."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>

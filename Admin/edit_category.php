<?php
require '../User/connect.php';
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json'); 
$response = ["success" => false, "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['category_name'] ?? '');

    // ✅ Fetch category details
    $checkQuery = "SELECT category_name, category_photo, is_api FROM category WHERE id = $1";
    $checkResult = pg_query_params($conn, $checkQuery, [$id]);
    $category = pg_fetch_assoc($checkResult);

    if (!$category) {
        $response["message"] = "Category not found.";
        echo json_encode($response);
        exit;
    }

    $isApi = $category['is_api'] === 't' || $category['is_api'] === 'true' || $category['is_api'] == 1;

    // ✅ API categories → Keep existing name
    if ($isApi) {
        $name = $category['category_name']; 
    } elseif (empty($name)) {
        $response["message"] = "Category name cannot be empty.";
        echo json_encode($response);
        exit;
    }

    // ✅ Handle Image Upload and Removal
    if (!empty($_POST['remove_photo'])) {
        $query = "UPDATE category SET category_photo = NULL WHERE id = $1";
        $params = [$id];
    } elseif (!empty($_FILES['category_photo']['name'])) {
        $targetDir = "../images/categories/";
        $photo = basename($_FILES["category_photo"]["name"]);
        $targetFilePath = $targetDir . $photo;

        if (move_uploaded_file($_FILES["category_photo"]["tmp_name"], $targetFilePath)) {
            $query = "UPDATE category SET category_photo = $1 WHERE id = $2";
            $params = [$photo, $id];
        } else {
            $response["message"] = "Error uploading image.";
            echo json_encode($response);
            exit;
        }
    } else {
        $query = "UPDATE category SET category_name = $1 WHERE id = $2";
        $params = [$name, $id];
    }

    $result = pg_query_params($conn, $query, $params);

    if ($result) {
        $response["success"] = true;
        $response["message"] = "Category updated successfully!";
    } else {
        $response["message"] = "Database error: " . pg_last_error($conn);
    }
}

echo json_encode($response);
?>

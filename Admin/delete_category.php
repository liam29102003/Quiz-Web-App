<?php
require '../User/connect.php';

if (isset($_GET['id'])) {
    $category_id = $_GET['id'];

    $query = "DELETE FROM category WHERE id = $1";
    $result = pg_query_params($conn, $query, [$category_id]);

    if ($result) {
        echo json_encode(["success" => true, "message" => "Category deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete category."]);
    }
}
?>

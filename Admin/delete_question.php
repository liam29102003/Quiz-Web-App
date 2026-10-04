<?php
require_once("../User/connect.php");

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $delete_query = "DELETE FROM question WHERE id = $1";
    $delete_stmt = pg_prepare($conn, "delete_question", $delete_query);
    $delete_result = pg_execute($conn, "delete_question", [$delete_id]);

    echo json_encode(["success" => $delete_result ? "Question deleted successfully!" : "Failed to delete question."]);
    exit;
}
?>

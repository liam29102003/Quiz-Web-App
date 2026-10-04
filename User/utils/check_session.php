<?php
session_start();

$response = ["logged_in" => isset($_SESSION['user_id'])];

header('Content-Type: application/json');
echo json_encode($response);
?>

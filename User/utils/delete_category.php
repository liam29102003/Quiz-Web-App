<?php
require '../../connect_redis.php'; // Ensure Redis connection is established
session_start();
// Delete category from Redis
$redis->del("user_category_".$_SESSION['user_id']);

echo json_encode(["message" => "Category deleted successfully"]);
?>

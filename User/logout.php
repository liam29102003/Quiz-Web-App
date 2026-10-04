<?php
session_start();


require '../connect_redis.php';
$userId = $_SESSION['user_id'];


//$redis->del('active_user:' . $_SESSION['user_id']);  
if (isset($_SESSION['user_id'])) {
       $redis->srem("active_users", $_SESSION['user_id']);
       //$redis->del('active_user:' . $_SESSION['user_id']);
}

if (!$redis->exists("active_user:" . $userId)) {

    // User's key has expired, log them out
    session_unset();
    session_destroy(); // Destroy the session to log the user out
   
    // Redirect or provide feedback
    header("Location: home.php");
    exit();
} 

// Destroy the session to log the user out

session_destroy();

// Redirect to the home page after logout
header('Location: home.php');
exit();
?>

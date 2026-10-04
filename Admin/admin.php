<?php
require '../User/connect.php'; // Ensure this contains your PostgreSQL connection `$conn`

// Admin details
$name = "admin";
$email = "admin@gmail.com";
$password = "Admin@12345"; // Change this to a strong password
$hashed_password = password_hash($password, PASSWORD_BCRYPT); // Hash password securely
$profile_picture = ""; // Default profile picture

// SQL query using prepared statements
$query = "INSERT INTO admin (name, email, password_hash, profile_picture) VALUES ($1, $2, $3, $4) RETURNING id";
$result = pg_query_params($conn, $query, [$name, $email, $hashed_password, $profile_picture]);

// Check if insertion was successful
if ($result) {
    $admin_id = pg_fetch_result($result, 0, 'id'); // Fetch the inserted ID
    echo "Admin inserted successfully! Admin ID: " . $admin_id;
} else {
    die("Error inserting admin: " . pg_last_error($conn));
}
?>

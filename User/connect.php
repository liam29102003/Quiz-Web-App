<?php
$host = "localhost";
$port = "5432";
$dbname = "QuizDB";  // Change to your database name
$user = "postgres";  // Default PostgreSQL superuser
$password = "kymt@311498";  // Use your PostgreSQL password

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

// Check connection
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}


?>
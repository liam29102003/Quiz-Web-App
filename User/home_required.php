<?php
require 'connect.php';

$query = "SELECT COUNT(*) AS total_users FROM users";
$result = pg_query($conn, $query);

if ($result) {
    $row = pg_fetch_assoc($result);
    $total_users = $row['total_users'];
    
} else {
    echo "Error retrieving user count.";
}


$query2 = "SELECT COUNT(*) AS total_categoriesh
            FROM (
                SELECT c.id
                FROM category c
                JOIN question q ON c.id = q.category_id
                GROUP BY c.id
                HAVING COUNT(q.id) >= 10
            ) AS subquery;
            ";
$result2 = pg_query($conn, $query2);

if ($result2) {
    $row2 = pg_fetch_assoc($result2);
    $total_categoriesh = $row2['total_categoriesh'];
   
} else {
    echo "Error retrieving category count: " . pg_last_error($conn);
}

//select top 5 players
$query3= "WITH UserScores AS (
            SELECT 
                u.id,
                u.name, 
                u.country,
                u.profile_picture,
                u.score,  -- Keep full JSONB score
                (SELECT COALESCE(SUM(value::INT), 0) FROM jsonb_each_text(u.score)) AS total_score
            FROM users u
            WHERE EXISTS (SELECT 1 FROM jsonb_each_text(u.score))  -- Ensure score contains at least one key-value pair
        ),
        UserAttempts AS (
            SELECT 
                uh.user_id,
                COUNT(uh.id) AS total_attempts
            FROM user_history uh
            GROUP BY uh.user_id
        )
        SELECT 
            us.name,
            us.country,
            us.profile_picture,
            us.score,  -- Full JSONB score (category-wise scores)
            us.total_score,
            COALESCE(ua.total_attempts, 0) AS total_attempts,
            RANK() OVER (ORDER BY us.total_score DESC, ua.total_attempts ASC) AS rank
        FROM UserScores us
        LEFT JOIN UserAttempts ua ON us.id = ua.user_id
        ORDER BY rank
        LIMIT 5;
        ";

$result3 = pg_query($conn, $query3);

// //country flag
// // Load the country JSON file
// $countries = json_decode(file_get_contents('country.json'), true);

// // Initialize variables to hold flag and country name
// $countryFlag = '';
// $countryName = '';
// // Close the database connection

$query4 = "SELECT COUNT(*) AS total_questions FROM question";
$result4 = pg_query($conn, $query4);

if ($result4) {
    $row4 = pg_fetch_assoc($result4);
    $total_questions = $row4['total_questions'];
    
} else {
    echo "Error retrieving user count.";
}


$query5 = "SELECT COUNT(*) AS total_categories
            FROM category  ";
$result5 = pg_query($conn, $query5);

if ($result5) {
    $row5 = pg_fetch_assoc($result5);
    $total_categories = $row5['total_categories'];
   
} else {
    echo "Error retrieving category count: " . pg_last_error($conn);
}

pg_close($conn);

?>
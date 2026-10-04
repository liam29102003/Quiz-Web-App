<?php
require '../User/connect.php'; // Ensure this correctly connects to PostgreSQL
require '../connect_redis.php'; //redis connection

// Get search query from request
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
if ($query === '') {
    echo json_encode([]);
    exit;
}
// Prevent SQL injection by using parameterized queries
$sql = "SELECT id, name, email, country, profile_picture, 
               (SELECT SUM(value::INT) FROM jsonb_each_text(score)) AS total_score 
        FROM users 
        WHERE LOWER(name) LIKE LOWER($1) 
           OR LOWER(email) LIKE LOWER($1) 
           OR LOWER(country) LIKE LOWER($1) 
        ORDER BY id ASC
        LIMIT 50";

$result = pg_query_params($conn, $sql, ["%$query%"]);
if (!$result) {
    echo json_encode([]);
    exit;
}
// Fetch users and return JSON
$users = pg_fetch_all($result);
//echo json_encode($users ?: []);

// Load the country.json data
$countries = json_decode(file_get_contents('../User/country.json'), true); // Ensure this is a valid JSON file

foreach ($users as &$user) {
    $userId = $user['id'];
    $user['status'] = $redis->sIsMember('active_users', $userId) ? 'active' : 'offline';

    // Ensure `profile_picture` is not empty
    $user['profile_picture'] = !empty($user['profile_picture'])
        ? trim($user['profile_picture'])  // Remove accidental spaces
        : ".../images/users/dfuser.png"; // Use default image if missing

    // Initialize variables for country flag and name
    $countryFlag = '';
    $countryName = '';

    // Check if the country exists in the country data
    foreach ($countries as $country) {
        if ($country['value'] === $user['country']) {
            $countryFlag = $country['data-image']; // Assign the flag URL
            $countryName = $country['value']; // Assign the country name
            break;
        }
    }

    // Add the flag and country name to the user data
    $user['country_flag'] = $countryFlag;
    $user['country_name'] = $countryName;
}

echo json_encode($users);


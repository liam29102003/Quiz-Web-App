<?php
session_start();

// Database connection
$conn = pg_connect("host=localhost dbname=QuizDB user=postgres password=kymt@311498");
if (!$conn) {
    die("Connection failed");
}

// Retrieve form data
$name = $_POST['name'];
$country = $_POST['country']; // This will be the country value (e.g., "United States")
$email = $_POST['email'];
$newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

// Handle profile image upload
$profileImagePath = null;
// In update_profile.php, after successful update:
$_SESSION['profile_picture'] = $profileImagePath; // Add this line
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
    $targetDir = "../images/users/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true); // Create the directory if it doesn't exist
    }
    $fileName = uniqid() . "_" . basename($_FILES["profile_image"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($imageFileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
            $profileImagePath = $targetFilePath;
            $_SESSION['profile_picture']=$targetFilePath;
        } else {
            echo "<script>alert('Error uploading image.'); window.history.back();</script>";
            exit();
        }
    } else {
        echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.'); window.history.back();</script>";
        exit();
    }
}

// If a new password is provided, hash it.
if (!empty($newPassword)) {
    $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
}

// Build the update query based on which fields need updating
if ($profileImagePath && !empty($newPassword)) {
    // Update name, country, profile picture, and password_hash
    $query = "UPDATE users SET name = $1, country = $2, profile_picture = $3, password_hash = $4 WHERE email = $5";
    $params = [$name, $country, $profileImagePath, $hashed_password, $email];
} elseif ($profileImagePath && empty($newPassword)) {
    // Update name, country, and profile picture only
    $query = "UPDATE users SET name = $1, country = $2, profile_picture = $3 WHERE email = $4";
    $params = [$name, $country, $profileImagePath, $email];
} elseif (!$profileImagePath && !empty($newPassword)) {
    // Update name, country, and password_hash only
    $query = "UPDATE users SET name = $1, country = $2, password_hash = $3 WHERE email = $4";
    $params = [$name, $country, $hashed_password, $email];
} else {
    // Update name and country only
    $query = "UPDATE users SET name = $1, country = $2 WHERE email = $3";
    $params = [$name, $country, $email];
}

// Execute the query
$result = pg_query_params($conn, $query, $params);

if ($result) {
    echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php?email=" . urlencode($email) . "';</script>";
} else {
    // Log the error for debugging
    error_log("Database error: " . pg_last_error($conn));
    echo "<script>alert('Error updating profile. Please try again.'); window.history.back();</script>";
}

// Close the database connection
pg_close($conn);
?>

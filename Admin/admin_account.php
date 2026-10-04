<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../User/login.php');
    exit();
}

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../User/connect.php'; // PostgreSQL connection

// Admin ID (Assuming one admin)
$admin_id = $_SESSION['admin_id'];

// Initialize $admin with default values
$admin = [];

// Fetch Admin Details
$query = "SELECT id, name, email, profile_picture, password_hash FROM admin WHERE id = $1";
$result = pg_query_params($conn, $query, [$admin_id]);

if (!$result) {
    die("Database query failed: " . pg_last_error($conn));
}

$fetched_admin = pg_fetch_assoc($result);
if ($fetched_admin) {
    $admin = $fetched_admin; // Override default values with fetched data
}

// Handle Profile Picture Upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['profile_picture'])) {
    // Check if a file was provided
    if ($_FILES["profile_picture"]["error"] === UPLOAD_ERR_NO_FILE) {
        // No file was uploaded, so use the default photo.
        $defaultPhoto = "admin_profile/profile1.png"; 
        $update_query = "UPDATE admin SET profile_picture = $1 WHERE id = $2";
        $update_result = pg_query_params($conn, $update_query, [$defaultPhoto, $admin_id]);

        if (!$update_result) {
            die("Failed to update profile picture to default: " . pg_last_error($conn));
        }
        // Refresh admin data after update
        $result = pg_query_params($conn, $query, [$admin_id]);
        $admin = pg_fetch_assoc($result);

        header("Location: admin_account.php?msg=profile_pic_updated");
        exit;
    }

    // If a file was uploaded, process it normally.
    $target_dir = "uploads/";
    $image_file = basename($_FILES["profile_picture"]["name"]);
    $target_path = $target_dir . $image_file;

    // Check if the file is an image
    $image_file_type = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($image_file_type, $allowed_types)) {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_path)) {
            $update_query = "UPDATE admin SET profile_picture = $1 WHERE id = $2";
            $update_result = pg_query_params($conn, $update_query, [$image_file, $admin_id]);
            $_SESSION['profile_picture']=$image_file;
            if (!$update_result) {
                die("Failed to update profile picture: " . pg_last_error($conn));
            }

            // Refresh admin data after update
            $result = pg_query_params($conn, $query, [$admin_id]);
            $admin = pg_fetch_assoc($result);

            header("Location: admin_account.php?msg=profile_pic_updated");
            exit;
        } else {
            die("Failed to upload file.");
        }
    } else {
        die("Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.");
    }
}

// Handle Profile Update (Name & Email) – if used
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_admin'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name) || empty($email)) {
        die("Name and email are required.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }
    $_SESSION['admin_name'] = $name;
    $update_query = "UPDATE admin SET name = $1, email = $2 WHERE id = $3";
    $update_result = pg_query_params($conn, $update_query, [$name, $email, $admin_id]);

    if (!$update_result) {
        die("Failed to update admin details: " . pg_last_error($conn));
    }

    // Refresh admin data after update
    $result = pg_query_params($conn, $query, [$admin_id]);
    $admin = pg_fetch_assoc($result);

    header("Location: admin_account.php?msg=update_success");
    exit;
}

// Handle Name Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_name'])) {
    $name = trim($_POST['name'] ?? '');
    
    if (empty($name)) {
        die("Name is required.");
    }
    
    $update_query = "UPDATE admin SET name = $1 WHERE id = $2";
    $update_result = pg_query_params($conn, $update_query, [$name, $admin_id]);
    
    $_SESSION['admin_name'] = $name;
    if (!$update_result) {
        die("Failed to update name: " . pg_last_error($conn));
    }
    
    // Refresh admin data after update
    $result = pg_query_params($conn, $query, [$admin_id]);
    $admin = pg_fetch_assoc($result);
    
    header("Location: admin_account.php?msg=name_update_success");
    exit;
}

// Handle Email Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_email'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        die("Email is required.");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }
    
    $update_query = "UPDATE admin SET email = $1 WHERE id = $2";
    $update_result = pg_query_params($conn, $update_query, [$email, $admin_id]);
    
    if (!$update_result) {
        die("Failed to update email: " . pg_last_error($conn));
    }
    
    // Refresh admin data after update
    $result = pg_query_params($conn, $query, [$admin_id]);
    $admin = pg_fetch_assoc($result);
    
    header("Location: admin_account.php?msg=email_update_success");
    exit;
}

// Handle Password Change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    if (!password_verify($old_password, $admin['password_hash'])) {
        // Redirect with an error reason if the current password is incorrect
        header("Location: admin_account.php?msg=pw_change_error&reason=Current+password+is+incorrect");
        exit;
    } else {
        $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $update_password_query = "UPDATE admin SET password_hash = $1 WHERE id = $2";
        $update_result = pg_query_params($conn, $update_password_query, [$new_hashed_password, $admin_id]);

        if (!$update_result) {
            // You might also want to pass an error reason here
            header("Location: admin_account.php?msg=pw_change_error&reason=Failed+to+update+password");
            exit;
        }

        header("Location: admin_account.php?msg=pw_change_success");
        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Itim', cursive; }
    </style>
</head>

<body class="bg-gray-100 p-0">
    <div class="relative flex h-screen w-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="w-64 bg-[#6859DE] text-white p-3 fixed top-0 left-0 h-full flex-shrink-0 md:block">
            <h1 class="text-2xl font-bold m-3 text-left">🎯 Quizzie</h1>
            <ul class="mt-8">
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_dashboard.php">📊 Dashboard</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_leaderboard.php">🏆 Leaderboard</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_user.php">👥 Manage Players</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_category.php">📜 Manage Categories</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_question.php">❓ Manage Questions</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_account.php">⚙️ Admin Account</a></li>
            </ul>
            <a href="../User/logout.php" class="absolute bottom-0 left-0 p-4 flex items-center space-x-2 cursor-pointer">
                <i class="fas fa-sign-out-alt text-lg pl-4 py-2"></i>
                <span class="text-lg">Logout</span>
            </a>
        </div>

        <!-- Main Content -->
        <div class="flex-1 h-screen bg-white pl-72 pr-4 pt-6 flex flex-col">
            <div class="flex justify-between mb-8">
                <h2 class="text-2xl font-bold">⚙️ Admin Account</h2>
                <div class="flex items-center">
                    <span class="mr-2"><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : "admin"; ?></span>
                    <img src="uploads/<?php echo isset($_SESSION['admin_name']) ? $_SESSION['profile_picture']  : "" ?>" class="w-10 h-10 bg-pink-300 rounded-full" />
                </div>
             
            </div>
            <div class="flex justify-center items-center h-screen">
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 text-white rounded-2xl shadow-lg p-6 w-full max-w-lg mb-20">
                    <h2 class="text-xl font-bold">👤 Admin Profile</h2>

                    <!-- Profile Picture -->
                    <div class="flex flex-col items-center mt-4">
                        <div class="relative">
                            <img id="profilePreview" src="uploads/<?= htmlspecialchars($admin['profile_picture'] ?? 'default.jpg') ?>" class="w-24 h-24 rounded-full border-4 border-white object-cover">
                            <button id="profileEditBtn" class="absolute bottom-0 right-0 bg-white text-indigo-600 p-2 rounded-full">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Profile Details -->
                    <div class="mt-6 space-y-4">
                        <div class="flex justify-between items-center bg-gray-100 p-4 rounded-lg">
                            <p class="text-gray-500">Name</p>
                            <p class="font-medium text-black"><?= htmlspecialchars($admin['name']) ?></p>
                            <button id="nameEditBtn">
                                <i class="fas fa-pen text-indigo-600"></i>
                            </button>
                        </div>

                        <div class="flex justify-between items-center bg-gray-100 p-4 rounded-lg">
                            <p class="text-gray-500">Email</p>
                            <p class="font-medium text-black"><?= htmlspecialchars($admin['email']) ?></p>
                            <button id="emailEditBtn">
                                <i class="fas fa-pen text-indigo-600"></i>
                            </button>
                        </div>

                        <!-- New Password Change Div -->
                        <div class="flex justify-between items-center bg-gray-100 p-4 rounded-lg">
                            <p class="text-gray-500">Password</p>
                            <p class="font-medium text-black">********</p>
                            <button id="passwordEditBtn">
                                <i class="fas fa-pen text-indigo-600"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Name Edit Modal -->
    <div id="nameModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center">
        <div class="bg-white p-6 rounded-lg w-96">
            <h2 class="text-xl font-bold mb-4">Edit Name</h2>
            <form action="admin_account.php" method="POST">
                <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" class="w-full p-2 border rounded mb-4" required>
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('nameModal')" class="bg-gray-500 text-white px-4 py-2 rounded mr-2">Cancel</button>
                    <button type="submit" name="update_name" class="bg-indigo-600 text-white px-4 py-2 rounded">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Email Edit Modal -->
    <div id="emailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center">
        <div class="bg-white p-6 rounded-lg w-96">
            <h2 class="text-xl font-bold mb-4">Edit Email</h2>
            <form action="admin_account.php" method="POST">
                <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" class="w-full p-2 border rounded mb-4" required>
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('emailModal')" class="bg-gray-500 text-white px-4 py-2 rounded mr-2">Cancel</button>
                    <button type="submit" name="update_email" class="bg-indigo-600 text-white px-4 py-2 rounded">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Profile Picture Edit Modal -->
    <div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center">
        <div class="bg-white p-6 rounded-lg w-96">
            <h2 class="text-xl font-bold mb-4">Edit Profile Picture</h2>
            <form action="admin_account.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_picture" class="w-full p-2 border rounded mb-4" required>
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('profileModal')" class="bg-gray-500 text-white px-4 py-2 rounded mr-2">Cancel</button>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Change Modal -->
<div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center">
    <div class="bg-white p-6 rounded-lg w-96">
        <h2 class="text-xl font-bold mb-4">Change Password</h2>
        <form action="admin_account.php" method="POST">
            <!-- Old Password Field with Toggle -->
            <div class="relative mb-4">
                <input type="password" id="oldPassword" name="old_password" placeholder="Current Password" class="w-full p-2 border rounded" required>
                <span class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword('oldPassword', this)">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <!-- New Password Field with Toggle -->
            <div class="relative mb-4">
                <input type="password" id="newPassword" name="new_password" placeholder="New Password" class="w-full p-2 border rounded" required>
                <span class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword('newPassword', this)">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="closeModal('passwordModal')" class="bg-gray-500 text-white px-4 py-2 rounded mr-2">Cancel</button>
                <button type="submit" name="change_password" class="bg-indigo-600 text-white px-4 py-2 rounded">Change Password</button>
            </div>
        </form>
    </div>
</div>


    <!-- JavaScript for Modal Functions and Alert Box -->
    <script>
        // Global modal open/close functions
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.remove("hidden");
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.add("hidden");
        }
        function togglePassword(inputId, iconContainer) {
    const input = document.getElementById(inputId);
    // Toggle the input type
    if (input.type === "password") {
        input.type = "text";
        // Change the icon to eye-slash indicating password is visible
        iconContainer.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        input.type = "password";
        // Change the icon back to eye
        iconContainer.innerHTML = '<i class="fas fa-eye"></i>';
    }
}
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("nameEditBtn").addEventListener("click", function () {
        openModal("nameModal");
    });

    document.getElementById("emailEditBtn").addEventListener("click", function () {
        openModal("emailModal");
    });

    document.getElementById("profileEditBtn").addEventListener("click", function () {
        openModal("profileModal");
    });

    document.getElementById("passwordEditBtn").addEventListener("click", function () {
        openModal("passwordModal");
    });

    // Check URL for a "msg" parameter and show an alert if present
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('msg')) {
        const msg = urlParams.get('msg');
        const reason = urlParams.get('reason'); // Declare reason here
        let alertOptions = {};
        switch (msg) {
            case 'update_success':
                alertOptions = { icon: 'success', title: 'Success', text: 'Profile updated successfully.' };
                break;
            case 'name_update_success':
                alertOptions = { icon: 'success', title: 'Success', text: 'Name updated successfully.' };
                break;
            case 'email_update_success':
                alertOptions = { icon: 'success', title: 'Success', text: 'Email updated successfully.' };
                break;
            case 'profile_pic_updated':
                alertOptions = { icon: 'success', title: 'Success', text: 'Profile picture updated successfully.' };
                break;
            case 'pw_change_success':
                alertOptions = { icon: 'success', title: 'Success', text: 'Password changed successfully.' };
                break;
            case 'pw_change_error':
                alertOptions = { 
                    icon: 'error', 
                    title: 'Error', 
                    text: reason ? reason : 'Password update failed.' 
                };
                break;
            // No default case here means nothing will show if msg doesn't match
        }
        if (alertOptions && Object.keys(alertOptions).length > 0) {
            Swal.fire(alertOptions).then(() => {
                // Remove query parameters from the URL after the alert is closed
                history.replaceState({}, document.title, window.location.pathname);
            });
        }
    }
});

    </script>
</body>
</html>
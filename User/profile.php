<?php
session_start();
require_once '../connect_pgsql.php';

if (!isset($_SESSION['isLoggedIn']) || !$_SESSION['isLoggedIn']) {
    die("You must be logged in to view this page.");
}

$email = $_SESSION['user_email'];
$stmt = $pgsql->prepare("SELECT * FROM users WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Corrected path
$defaultProfilePicture = '../images/users/dfuser.png';
$profilePicture = !empty($user['profile_picture']) ? $user['profile_picture'] : $defaultProfilePicture;
$_SESSION['profile_picture']=$user['profile_picture'];
$countries = json_decode(file_get_contents('country.json'), true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="UserProfile2.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            border: 2px solid black;
            border-radius: 15px;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 550px;
            padding: 15px 20px 20px;
        }
        .modal-header {
            background-color: #D6B4FC;
            padding: 15px 20px;
            text-align: center;
            position: relative;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .modal-header .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            background: none;
            border: none;
            cursor: pointer;
        }
        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .modal-body .flex.items-center.space-x-3 {
            align-items: center;
            gap: 10px;
        }
        .modal-body label {
            min-width: 80px;
            font-weight: bold;
        }
        .modal-body input {
            width: 100%;
            padding: 10px;
            font-size: 18px;
            border: 1px solid #000;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .modal-footer {
            text-align: right;
            padding: 10px;
        }
        .modal-close {
            background-color: #FFC107;
            border: 2px solid black;
            font-size: 20px;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .modal-close:hover {
            background-color: #e0a800;
        }
        .profile-img-container {
            position: relative;
        }
        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
           
        }
        .edit-img-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: #FFC107;
            border: none;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
        }
        .edit-img-btn i {
            font-size: 20px;
            color: #fff;
        }
        .file-input {
            display: none;
        }
        body {
            font-family: 'Itim', cursive;
            font-size: 18px;
        }
        h1 {
            font-size: 32px;
        }
        h2 {
            font-size: 28px;
        }
        p, span, label {
            font-size: 20px;
        }
        button, input {
            font-size: 18px;
        }
        .select2-container {
          width: 75% !important;
        }
        .select2-container--default .select2-selection--single {
            height: 45px !important;
            padding: 8px !important;
            border: 1px solid #000 !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            display: flex !important;
            align-items: center !important;
            line-height: normal !important;
        }
        .select2-container--default .select2-results__option {
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-results__option img {
            margin-right: 10px !important;
            width: 20px !important;
        }
        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 15px;
        }
        .info-label {
            min-width: 100px;
            font-weight: bold;
        }
        .info-value {
            flex:1;
        }
        #logout-container {
            position: absolute;
            top: 45px;
            right: 20px;
            background-color: white;
            border-radius: 5px;
            padding: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
    </style>
</head>
<body>
<nav class="w-full bg-[#D6B4FC] text-black text-lg p-4 flex justify-between items-center" id="navbar">
    <h1 class="text-2xl font-bold">🎯 Quizzie</h1>
    <div class="sm:space-x-4 xs:space-x-2 flex flex-nowrap items-center" id="nav-links">
        <a href="home.php" class="hover:underline">Home</a>
        <a href="leaderboard.php" class="hover:underline">Leaderboard</a>
        <a href="login.php" class="hover:underline" id="login-link">Login</a>
        <a href="signup.php" class="px-4 py-2 bg-[#8035FB] text-white rounded-md mr-2" id="register-link">Register</a>
    </div>

    <div class="flex items-center sm:space-x-4 hidden" id="user-profile">
        <div class="md:space-x-4 sm:space-x-4 xs:flex-wrap">
            <a href="profile.php" class="hover:underline">Profile</a>
            <a href="history.php" class="hover:underline">History</a>
        </div>
        <p class="px-4 py-2 bg-[#8035FB] text-[#FFDF00] rounded-md">
            <i class="fa-solid fa-coins" style="color: #ffffff;"></i>
            &nbsp;<span id="total-score"><?php echo $user['coins']; ?></span>
        </p>
        <div id="profile-container" class="flex items-center cursor-pointer">            
            <img src="<?php echo htmlspecialchars($profilePicture); ?>" 
                 alt="Profile Picture" 
                 id="profile-pic" 
                 class="w-10 h-10 rounded-full cursor-pointer">
        </div>
        <!-- Logout Button (Initially hidden) -->
        <div id="logout-container" class="hidden mt-2">                   
            <button id="logout" class="bg-red-500 text-white px-2 rounded w-auto h-auto">Logout</button>
        </div>
    </div>
</nav>
<div class="container">
    <div class="intro-text">
        <div class="intro-img-container">
            <img src="../images/icons/robot.png" alt="Intro Picture" class="intro-img" id="introImage">
        </div>
        <h2>Hey, <?php echo htmlspecialchars($user['name']); ?>!</h2> 
        <p>Track your progress and show off your high scores—</p>
        <p>Let’s keep the momentum going!</p>
    </div>
    <div class="profile-card">
        <div class="top-section">
            <div class="left-side">
                <div class="profile-img-container">
                    <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture" class="profile-img">
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p>Coins: <?php echo json_decode($user['coins']); ?></p>
                </div>
            </div>
            <div class="right-side">
                <button id="editProfileBtn" class="nav-edit-btn">Edit Profile</button>
            </div>
        </div>
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Location:</span>
                <span class="info-value" id="profile-location">
    <?php
    $userCountryCode = $user['country'];
    if (!empty($userCountryCode)) {
        $countryData = array_filter($countries, function($country) use ($userCountryCode) {
            return $country['value'] === $userCountryCode;
        });
        if (!empty($countryData)) {
            $country = reset($countryData);
            $countryName = $country['value'];  // Define countryName
            $flagUrl = $country['data-image']; // Define flagUrl
            echo "<img src='{$flagUrl}' alt='" . htmlspecialchars($countryName) . "' class='inline-block mr-2'> " . htmlspecialchars($countryName);
        } else {
            echo "Unknown";
        }
    } else {
        echo "No location set";
    }
    ?>
</span>
            </div>
        </div>
    </div>
</div>
<div id="editProfileModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Profile</h2>
            <button class="close-btn" id="closeModalBtn">&times;</button>
        </div>
        <form method="POST" action="update_profile.php" enctype="multipart/form-data" class="space-y-4">
            <div class="flex justify-start items-center py-3 space-x-6">
                <div class="flex items-center space-x-3">
                    <img src="<?php echo htmlspecialchars($profilePicture); ?>" 
                         alt="Profile Picture" 
                         class="w-24 h-24 rounded-lg border border-gray-300 object-cover" 
                         id="modalProfileImage">
                </div>
                <div class="flex items-center space-x-3">
                    <input type="file" name="profile_image" id="fileInput" accept="image/png, image/jpeg" class="hidden">
                    <button type="button" id="uploadPhotoBtn" class="px-4 py-2 bg-[#FFC107] text-black font-semibold rounded-lg border-2 border-black hover:bg-yellow-600">
                        Upload Photo
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <!-- Existing fields for name, email, and location -->
                <div class="flex items-center space-x-3">
                    <label class="w-1/5 text-base font-semibold text-gray-800">Name:</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="flex-1 text-base p-2 bg-gray-200 rounded-lg border border-black text-gray-700">
                </div>
                <div class="flex items-center space-x-3">
                    <label class="w-1/5 text-base font-semibold text-gray-800">Email:</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="flex-1 text-base p-2 bg-gray-200 rounded-lg border border-black text-gray-700" readonly>
                </div>
                <div class="flex items-center space-x-3">
                    <label class="w-1/5 text-base font-semibold text-gray-800">Location:</label>
                    <select name="country" id="country-select" class="flex-1 text-base p-2 bg-gray-200 rounded-lg border border-black text-gray-700" required>
                        <option value="" disabled>Select a country</option>
                    </select>
                </div>
                <!-- New password field (only one input, no confirmation) -->
                <div class="flex items-center space-x-3">
                    <label class="w-1/5 text-base font-semibold text-gray-800">Password:</label>
                    <input type="password" name="new_password" placeholder="Enter new password" class="flex-1 text-base p-2 bg-gray-200 rounded-lg border border-black text-gray-700">
                </div>
                <div id="password-error" class="text-red-500 text-sm hidden mt-1">
    Password must contain: 8+ characters, 1 uppercase, 1 lowercase, 1 number, and 1 special character
</div>
            </div>
            <div class="modal-footer flex justify-end">
                <button type="submit" class="modal-close">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<script>
// User session handling
const user = {          
    isLoggedIn: <?php echo isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] ? 'true' : 'false'; ?>,
    profilePicture: "<?php echo isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : ''; ?>",
    totalCoins: <?php echo isset($_SESSION['coins']) ? $_SESSION['coins'] : 0; ?>
};

// Password validation elements
const passwordInput = document.querySelector('input[name="new_password"]');
const passwordError = document.getElementById('password-error');
const saveButton = document.querySelector('.modal-close');
const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

function validatePassword() {
    const password = passwordInput.value;
    
    if (password === '') {
        passwordError.classList.add('hidden');
        saveButton.disabled = false;
        return true;
    }
    
    if (!passwordRegex.test(password)) {
        passwordError.classList.remove('hidden');
        saveButton.disabled = true;
        return false;
    }
    
    passwordError.classList.add('hidden');
    saveButton.disabled = false;
    return true;
}

if (user.isLoggedIn) {
    document.getElementById("login-link").style.display = "none";
    document.getElementById("register-link").style.display = "none";
    document.getElementById("user-profile").classList.remove("hidden");
    document.getElementById("nav-links").classList.add("xs:flex-wrap");
    //document.getElementById("total-score").innerText = `  ${user.totalCoins}`;
}

// Modal functionality
const modal = document.getElementById("editProfileModal");
const editProfileBtn = document.getElementById("editProfileBtn");
const closeModalBtn = document.getElementById("closeModalBtn");

if (editProfileBtn && modal && closeModalBtn) {
    editProfileBtn.addEventListener("click", () => {
        modal.style.display = "flex";
        validatePassword(); // Validate when modal opens
    });
    closeModalBtn.addEventListener("click", () => modal.style.display = "none");
    window.addEventListener("click", (e) => e.target === modal && (modal.style.display = "none"));
}

// Password validation events
passwordInput.addEventListener('input', validatePassword);

// File upload functionality
const uploadPhotoBtn = document.getElementById("uploadPhotoBtn");
const fileInput = document.getElementById("fileInput");

if (uploadPhotoBtn && fileInput) {
    uploadPhotoBtn.addEventListener("click", () => fileInput.click());
    fileInput.addEventListener("change", function () {
        const file = this.files[0];
        if (file && file.type.startsWith("image/")) {
            const reader = new FileReader();
            reader.onload = (e) => document.getElementById("modalProfileImage").src = e.target.result;
            reader.readAsDataURL(file);
        } else {
            alert("Please upload a valid image file.");
        }
    });
}

// Select2 initialization
$(document).ready(function() {
    fetch('./country.json')
        .then(response => response.json())
        .then(countries => {
            $('#country-select').select2({
                data: countries.map(country => ({
                    id: country.value,
                    text: country.value,
                    image: country['data-image']
                })),
                templateResult: formatCountry,
                templateSelection: formatCountry
            }).val(<?= json_encode($user['country']) ?>).trigger('change');
        });

    function formatCountry(country) {
        if (!country.id) return country.text;
        return $(`
            <span style="display: flex; align-items: center;">
                <img src="${country.image}" style="width: 20px; margin-right: 10px;" />
                ${country.text}
            </span>
        `);
    }

    // Form submission validation
    $('form').on('submit', function(e) {
        if (!validatePassword()) {
            e.preventDefault();
            alert('Please fix password requirements before submitting!');
        }
    });
});

// Profile dropdown and logout functionality
document.getElementById("profile-container").addEventListener("click", function() {
    const logoutContainer = document.getElementById("logout-container");
    logoutContainer.classList.toggle("hidden");
});

document.getElementById("logout").addEventListener("click", function() {
    fetch('logout.php')
        .then(response => {
            if (response.ok) {
                window.location.href = "home.php";
            }
        })
        .catch(error => console.error("Logout failed", error));
});
</script>
</body>
</html>

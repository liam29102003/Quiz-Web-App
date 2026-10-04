<?php
session_start();
require_once '../connect_pgsql.php';

if (!isset($_GET['email']) || !isset($_GET['category_id'])) {
    die("Required parameters not provided.");
}

$email = $_GET['email'];
$category_id = $_GET['category_id'];

$_SESSION["back_email"]= $email  ;
$_SESSION["category_id"]= $category_id ;

// Fetch user data
$stmt = $pgsql->prepare("SELECT * FROM users WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Fetch category details
$catStmt = $pgsql->prepare("SELECT * FROM category WHERE id = :category_id");
$catStmt->bindParam(':category_id', $category_id);
$catStmt->execute();
$category = $catStmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    die("Category not found.");
}

// Fetch user's history for the given category
$historyQuery = "
    SELECT id, date AS attempt_date, final_score
    FROM user_history
    WHERE user_id = :user_id AND category_id = :category_id
    ORDER BY date DESC
";

$historyStmt = $pgsql->prepare($historyQuery);
$historyStmt->bindParam(':user_id', $user['id']);
$historyStmt->bindParam(':category_id', $category_id);
$historyStmt->execute();
$historyRecords = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Detail | Quizzie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Itim&display=swap');

        body {
            font-family: 'Itim', sans-serif;
            font-size: 1.1rem;
            background: linear-gradient(135deg, #6859DE 0%, #8A7CFF 100%);
            min-height: 100vh;
            padding-top: 80px;
        }

        .history-card {
            font-size: 1.15rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .profile-gradient {
            background: linear-gradient(135deg, #8A7CFF 0%, #6859DE 100%);
        }

        .score-badge {
            background: linear-gradient(45deg, #FFC107 0%, #FF9800 100%);
            box-shadow: 0 4px 6px rgba(255, 152, 0, 0.1);
        }

        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }

        thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #D6B4FC;
            border-bottom: 1px solid black;
        }

        


        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #8A7CFF;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #6859DE;
        }

        h1, h2 {
            font-size: 1.5rem;
        }

        .text-sm {
            font-size: 1.05rem;
        }

        .text-xl {
            font-size: 1.25rem;
        }

        .text-lg {
            font-size: 1.125rem;
        }

        .text-base {
            font-size: 1.2rem;
        }
        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .table-container {
            max-height: 300px;
            overflow-y: auto;
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
    <!-- Navigation Bar -->
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
                <a href="profile.php?email=<?php echo urlencode($user['email']); ?>" class="hover:underline">Profile</a>      
                <a href="History.php?email=<?php echo urlencode($user['email']); ?>" class="hover:underline">History</a>
            </div>
            <p class="px-4 py-2 bg-[#8035FB] text-[#FFDF00] rounded-md">
                <i class="fa-solid fa-coins" style="color: #ffffff;"></i>
                &nbsp;<span id="total-score"><?php echo $user['coins']; ?></span>
            </p>
            <div id="profile-container" class="flex items-center cursor-pointer">            
                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                    alt="Profile Picture" 
                    id="profile-pic" 
                    class="w-10 h-10 rounded-full">
            </div>
            <!-- Logout Button (Initially hidden) -->
            <div id="logout-container" class="hidden mt-2">                   
                <button id="logout" class="bg-red-500 text-white px-2 rounded w-auto h-auto">Logout</button>
            </div>
        </div>
    </nav>

    <!-- Category Detail Card -->
    <div class="container mx-auto px-4 py-5">
        <div class="history-card max-w-4xl mx-auto p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-6">
                    <div class="profile-gradient rounded-full p-1.5 shadow-lg">
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                             class="w-24 h-24 rounded-full border-4 border-white"
                             alt="Profile Picture">
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="text-gray-600">Category: <?php echo htmlspecialchars($category['category_name']); ?></p>
                    </div>
                </div>
                <a href="History.php?email=<?php echo urlencode($user['email']); ?>" 
                   class="back-button inline-flex items-center px-6 py-3 border-2 border-black text-base font-medium rounded-md text-black transition-colors"
                   style="background-color: #FFC107;">
                    ← Back to History
                </a>

            </div>

             <!-- History Detail Table -->
             <div class="rounded-xl shadow-sm table-container">
                <table class="w-full">
                    <thead class="border-b border-black">
                        <tr>
                            <th class="px-6 py-4 text-left text-indigo-700">Record ID</th>
                            <th class="px-6 py-4 text-left text-indigo-700">Date & Time</th>
                            <th class="px-6 py-4 text-left text-indigo-700">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if(count($historyRecords) > 0): ?>
                            <?php foreach ($historyRecords as $record) : 
                                $date = date_create($record['attempt_date']);
                                $score = $record['final_score'];
                                $score_sign = $score > 0 ? "+" : "";
                                $score_class = $score >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                            ?>
                            <tr class="history-item transition-transform duration-200 hover:shadow-md cursor-pointer" onclick="window.location='review_detail.php?id=<?= htmlspecialchars($record['id']); ?>'">
                                <td class="px-6 py-4"><?php echo htmlspecialchars($record['id']); ?></td>
                                <td class="px-6 py-4">
                                    <div class="font-medium">
                                        <?php echo htmlspecialchars(date_format($date, 'M j, Y')); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars(date_format($date, 'h:i A')); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-3 py-1 rounded-full <?= $score_class ?>">
                                        <?php echo htmlspecialchars($score_sign . $score); ?> pts
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="px-6 py-4 text-center" colspan="3">No records found for this category.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
<script>
     // Example data for logged-in user (replace with actual data from your backend)
     const user = {          
           
           isLoggedIn: <?php echo isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] ? 'true' : 'false'; ?>,
           profilePicture: "<?php echo isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : ''; ?>",
           totalCoins: <?php echo isset($_SESSION['coins']) ? $_SESSION['coins'] : 0; ?>
       };
      
      
       if (user.isLoggedIn) {
           // Update the navbar for logged-in user
           document.getElementById("login-link").style.display = "none";
           document.getElementById("register-link").style.display = "none";
           document.getElementById("user-profile").classList.remove("hidden");
           document.getElementById("nav-links").classList.add("xs:flex-wrap");
           
           
       }

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
</html>
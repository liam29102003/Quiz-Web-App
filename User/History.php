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

$sql_category = "SELECT * from category";
$categories = $pgsql->query($sql_category);
$categories = $categories->fetchAll(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Get category scores from JSON in users table
$scoreData = json_decode($user['score'], true) ?: [];
$highScore = 0;
$categoryData = [];

if (!empty($scoreData)) {
    // Calculate high score and prepare category data
    $highScore = max(array_values($scoreData));
    foreach ($scoreData as $category => $score) {

        foreach($categories as $cate){
            if(strtolower($cate["category_name"]) == strtolower($category)){
                $categoryData[] = [
                    'category_name' => $category,
                    'category_id' => $cate['id'],
                    'current_score' => (int)$score
                ];
            }
        }
    }
    
    // Sort by score descending
    usort($categoryData, function($a, $b) {
        return $b['current_score'] - $a['current_score'];
    });
}

// Determine user level based on high score
$userLevel = 'Easy';
if ($highScore >= 4000) {
    $userLevel = 'Hard';
} elseif ($highScore >= 2000) {
    $userLevel = 'Medium';
}
?>





<!-- REST OF THE HTML REMAINS THE SAME -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz History | Quizzie</title>
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

        .category-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(104, 89, 222, 0.1);
        }

        .history-item:hover {
            transform: translateY(-2px);
            background: rgba(104, 89, 222, 0.05);
        }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: rgba(104, 89, 222, 0.1);
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #8A7CFF 0%, #6859DE 100%);
            transition: width 0.5s ease-in-out;
        }

        table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-size: 1rem;
    text-align: center;
}


        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }

        thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #D6B4FC; /* Solid background color */
            border-bottom: 1px solid black; /* Ensure the border is visible */
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
            font-size: 1.5rem; /* Increase font size for headings */
        }

        .text-sm {
            font-size: 1.05rem; /* Increase font size for smaller text */
        }

        .text-xl {
            font-size: 1.25rem; /* Increase font size for larger text */
        }

        .text-lg {
            font-size: 1.125rem; /* Increase font size for medium text */
        }

        .text-base {
            font-size: 1.2rem; /* Adjust font size for base text */
        }
        nav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
}
th, td {
    padding: 12px 15px;
    border: 1px solid #ddd;
}

th {
    background-color: #8035FB;
    color: black;
    font-weight: bold;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

tr:hover {
    background-color: #f1f1f1;
}

td {
    color: #333;
}
/* Make table container scrollable */
.overflow-table {
            max-height: 300px;
            overflow: auto;
        }

        
        .fixed-back {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 10px;
            text-align: center;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.2);
            z-index: 100;
        }

        /* Add to your existing CSS */
.bg-blue-100 {
    background-color: #dbeafe;
}
.text-blue-800 {
    color: #1e40af;
}

        
    </style>
</head>
<body>
<nav class="w-full bg-[#D6B4FC] text-black text-lg p-4 flex justify-between items-center" id="navbar">
    <!-- Left Side - Brand -->
    <h1 class="text-2xl font-bold">🎯 Quizzie</h1>

    <!-- Middle - Navigation Links -->
    <div class="flex space-x-4" id="nav-links">
        <a href="home.php" class="hover:underline">Home</a>
        <a href="leaderboard.php" class="hover:underline">Leaderboard</a>
    </div>

    <!-- Right Side - User Section -->
    <div class="flex items-center space-x-4" id="user-profile">
        <!-- Profile/History Links -->
        <div class="flex space-x-4">
            <a href="profile.php" class="hover:underline">Profile</a>
            <a href="history.php" class="hover:underline">History</a>
        </div>
        
        <!-- Coins Display -->
        <p class="px-4 py-2 bg-[#8035FB] text-[#FFDF00] rounded-md">
            <i class="fa-solid fa-coins" style="color: #ffffff;"></i>
            &nbsp;<span id="total-score"><?php echo $user['coins']; ?></span>
        </p>
        
        <!-- Profile Picture with Dropdown -->
        <div id="profile-container" class="flex items-center cursor-pointer">
            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                 alt="Profile Picture" 
                 id="profile-pic" 
                 class="w-10 h-10 rounded-full cursor-pointer">
        </div>
        
        <!-- Logout Button -->
        <div id="logout-container" class="hidden mt-2">                   
            <button id="logout" class="bg-red-500 text-white px-2 rounded w-auto h-auto">Logout</button>
        </div>
    </div>
</nav>
    <!-- User History Card -->

    <div class="container mx-auto px-4 py-5">
        <div class="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center">
                    <img src="<?= htmlspecialchars($user['profile_picture']) ?>" 
                         class="w-20 h-20 rounded-full border-4 border-white" 
                         alt="Profile Picture">
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <?= htmlspecialchars($user['name']) ?>
                        </h2>
                        <p class="text-gray-600">
                            Level: <?= $userLevel ?> (High Score: <?= $highScore ?>)
                        </p>
                    </div>
                </div>
                <div>
                    <a href="profile.php?email=<?= urlencode($user['email']) ?>" 
                       class="inline-block px-6 py-2 bg-[#8035FB] text-white rounded-md">
                        ← Back
                    </a>
                </div>
            </div>

            <div class="overflow-table">
                <table class="w-full border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="border border-gray-300 px-4 py-2">Category</th>
                            <th class="border border-gray-300 px-4 py-2">Your Level</th>
                            <th class="border border-gray-300 px-4 py-2">Total Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoryData as $data): ?>
                            <tr class="hover:bg-gray-100 cursor-pointer" 
                            onclick="window.location.href='category_detail.php?email=<?= urlencode($user['email']) ?>&category_id=<?= urlencode($data['category_id']) ?>'">
                            <td class="border border-gray-300 px-4 py-2">
                                <?= htmlspecialchars($data['category_name']) ?>
                            </td>
                            <td class="border border-gray-300 px-4 py-2">
                                <span class="px-3 py-1 rounded-full 
                                    <?= match($userLevel) {
                                        'Easy' => 'bg-green-100 text-green-800',
                                        'Medium' => 'bg-yellow-100 text-yellow-800',
                                        'Hard' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    } ?>">
                                    <?= $userLevel ?>
                                </span>
                            </td>
                            <td class="border border-gray-300 px-4 py-2">
                                <span class="px-3 py-1 rounded-full 
                                    <?= $data['current_score'] >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $data['current_score'] ?> pts
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Keep existing JavaScript
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
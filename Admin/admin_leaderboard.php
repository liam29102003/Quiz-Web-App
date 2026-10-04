<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../User/login.php');
    exit();
}

require '../User/connect.php';     // postgresql connection


//category retrieve
$query = "SELECT category_name FROM category";
$result = pg_query($conn, $query);
if (!$result) {
    die("Error in SQL query: " . pg_last_error());
}

$options = "";
while ($row = pg_fetch_assoc($result)) {
    $category_name = htmlspecialchars($row['category_name']);
    $options .= "<option value=\"$category_name\">" . strtoupper($category_name) . "</option>\n";
}

$players = [];
$category = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
    $category = trim($category);
    $lcategory = strtolower($category);   

    if (!empty($category)) {
        // Prepare the query
        $query = "
            WITH ScoreAndPlayCount AS (
                SELECT 
                    u.id AS user_id,
                    u.name,
                    COALESCE(
                        (u.score->> $1)::int,
                        (u.score->> $2)::int
                    ) AS category_score,
                    COUNT(uh.id) AS play_count,
                    u.profile_picture,
                    u.country
                FROM users u
                LEFT JOIN user_history uh 
                    ON u.id = uh.user_id AND uh.category_id = (
                        SELECT id FROM category WHERE LOWER(category_name) = LOWER($1)
                    )
                WHERE u.score ? $1 OR u.score ? $2
                GROUP BY u.id, u.name, category_score, u.profile_picture, u.country
            )
            SELECT user_id, name, category_score, play_count, profile_picture, country,
                   RANK() OVER (ORDER BY category_score DESC, play_count ASC) AS rank
            FROM ScoreAndPlayCount
            ORDER BY rank;
        ";

        // Execute the query with parameter
        $result = pg_query_params($conn, $query, [$category,$lcategory]);

        if ($result) {
            $players = pg_fetch_all($result);
        }
    }
}


//country flag
// Load the country JSON file
$countries = json_decode(file_get_contents('../User/country.json'), true);

// Initialize variables to hold flag and country name
$countryFlag = '';
$countryName = '';


?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
	<title>🎯Quizzie</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
               extend:{
                screens: {
                'sm': '300px',   
                'md': '876px' 
                },
                fontFamily: {
                    inter: ['Inter', 'sans-serif'],
                },
                
               }
          }
           
        }
    </script>
    
 	
    <style>
        body {
           font-family: 'Itim', cursive;
        }

        /* tbody tr:nth-child(-n+3) td{
            background-color: #E1DDF5; /* Change to your desired highlight color 
        } */

      
    </style>
   </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" /> 	
     <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

</head>
<body>
    <div class="flex h-screen bg-gray-100 overflow-x-auto">
        <!-- Sidebar -->
        <div class="w-64 bg-[#6859DE] text-white p-3 sticky top-0 h-screen md:block sm:hidden" >
            <h1 class="text-2xl font-bold m-3">🎯 Quizzie</h1>
          <ul class="mt-8">
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_dashboard.php" class="flex items-center"><span class="mr-2 text-lg">📊</span> Dashboard</a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_leaderboard.php" class="flex items-center"><span class="mr-2 text-lg">🏆</span> Leaderboard</a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_user.php" class="flex items-center"><span class="mr-2 text-lg">👥</span> Manage Players</a></li>            
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_category.php" class="flex items-center"><span class="mr-2 text-lg">📜</span> Manage Categories</a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_question.php" class="flex items-center"><span class="mr-2 text-lg">❓</span> Manage Questions</a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_account.php" class="flex items-center"><span class="mr-2 text-lg">⚙️</span> Admin account</a></li>    
          </ul>
           <a href="../User/logout.php" class="absolute bottom-0 left-0 p-4 flex items-center space-x-2 cursor-pointer" >
                <i class="fas fa-sign-out-alt text-lg pl-4 py-2"></i>
                <span class="text-lg">Logout</span>
            </a>
        </div>

        <div class="w-fit bg-[#6859DE] text-white p-3 sticky top-0 h-screen md:hidden sm:block" >
            <h1 class="text-lg font-bold py-2 w-10 flex flex-wrap">🎯Quizzie</h1>
          <ul class="mt-8 text-lg">
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_dashboard.php" class="flex items-center"><span class="mr-2 text-lg"> 📊</span></a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_leaderboard.php" class="flex items-center"><span class="mr-2 text-lg"> 🏆</span></a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_players.php" class="flex items-center"><span class="mr-2 text-lg"> 👥</span></a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_category.php" class="flex items-center"><span class="mr-2 text-lg"> 📜</span></a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_question.php" class="flex items-center"><span class="mr-2 text-lg">❓</span> </a></li>            
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_account.php" class="flex items-center"><span class="mr-2 text-lg"> ⚙️</span></a></li>            
          </ul>
          <a href="../User/logout.php" class="absolute bottom-0 left-0 p-4 flex items-center space-x-2 cursor-pointer " id="logout">
                <i class="fas fa-sign-out-alt text-lg px-4 py-2"></i>                
          </a>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
          <div class="flex justify-between mb-8">
            <h2 class="text-2xl font-bold">🏆 Leaderboard</h2>
            <div class="flex items-center">
                    <span class="mr-2"><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : "admin"; ?></span>
                    <img src="uploads/<?php echo isset($_SESSION['admin_name']) ? $_SESSION['profile_picture']  : "" ?>" class="w-10 h-10 bg-pink-300 rounded-full" />
                </div>
          </div>
      

          <!-- Category Selection Form -->
          <div class="mb-4 flex items-center">
              <form method="POST" class="flex flex-row gap-3">
                 
                  <select name="category" class="border rounded-lg px-2 py-2 bg-white shadow-lg" required>
                      <option value="" class="text-center">Select Category</option>
                      <?php echo $options; ?>
                  </select>
                  <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">Show</button>
              </form>
          </div>

         

          <div class="mt-5 w-full bg-white p-4 rounded shadow mb-8 overflow-x-auto mb-3">
           <!-- Top Players Table -->
            <?php if ($category): ?>
                
                    <h2 class="text-lg mb-3">Players in <span class="text-purple-800"><?php echo strtoupper($category); ?></span></h2>
                    <div class="w-full border rounded-lg overflow-hidden">
                    <table class="w-full bg-white rounded-lg">
                        <thead class="bg-blue-600 text-white uppercase text-sm rounded-t-lg">
                            <tr class="text-left">
                                <th class="p-2">Rank</th>
                                <th class="p-2">Name</th>                                
                                <th class="p-2">Country</th>
                                <th class="p-2">Attempts</th>
                                <th class="p-2">Score</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                        <?php if ($players): ?>
                            <?php foreach ($players as $player): ?>
                                <tr class="hover:bg-gray-100 transition" >
                                
                                    <td class="p-2"><?php echo htmlspecialchars($player['rank']); ?> <span class="rank-emoji rank-<?php echo htmlspecialchars($player['rank']); ?>"></span></td>
                                    <td class="p-2 flex flex-wrap items-center gap-2">
                                    <?php 
$profilePic = !empty($player['profile_picture']) 
    ? htmlspecialchars($player['profile_picture']) 
    : "../images/users/dfuser.png"; // Use default image if profile pic is missing
?>
<img src="<?php echo $profilePic; ?>" class="w-8 h-8 rounded-full">

                                        <?php echo htmlspecialchars($player['name']); ?>
                                    </td>
                                    
                                    <td class="p-2">
                                        <?php 
                                            foreach ($countries as $country) {
                                                if ($country['value'] === $player['country']) {
                                                    $countryFlag = $country['data-image']; // Flag URL
                                                    $countryName = $country['value']; // Country Name
                                                    break;
                                                }
                                            }
                                            if ($countryFlag): ?>
                                            <div class="flex flex-wrap items-center gap-2">
                                            <img src="<?php echo $countryFlag; ?>" class="w-8 h-5 rounded border border-gray-400 shadow-md" alt="Flag of <?php echo $countryName; ?>">
                                            <?php echo $countryName; ?>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-2"><?php echo htmlspecialchars($player['play_count']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($player['category_score']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="border p-2 text-center">No players found for this category.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    </div>

            <?php else:  ?>
                  <p>Select category to view players!</p>
            <?php endif; ?>
           
          </div>
        
        </div>

        
      </div>

     
</body>


<script>
// Add emojis dynamically based on rank
document.addEventListener("DOMContentLoaded", function() {
    // Get all the spans with class 'rank-emoji' and their corresponding rank
    const rankEmojis = {
        1: '🥇', // 1st place - Gold
        2: '🥈', // 2nd place - Silver
        3: '🥉'  // 3rd place - Bronze
    };

    Object.keys(rankEmojis).forEach(rank => {
        const elements = document.querySelectorAll('.rank-' + rank); // Find all elements with the class for that rank
        elements.forEach(element => {
            element.innerHTML = rankEmojis[rank]; // Insert the emoji
        });
    });
});
</script>
</html>
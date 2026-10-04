<!DOCTYPE html>

<?php
session_start();

require 'connect.php';     // postgresql connection

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
    $s_category = strtolower($category);
    if (!empty($category)) {
        // Prepare the query
        $query = "
            WITH ScoreAndPlayCount AS (
                SELECT 
                    u.id AS user_id,
                    u.name,
                    (u.score->> $2)::int AS category_score,
                    COUNT(uh.id) AS play_count,
                    u.profile_picture,
                    u.country
                FROM users u
                LEFT JOIN user_history uh 
                    ON u.id = uh.user_id AND uh.category_id = (
                        SELECT id FROM category WHERE category_name = $1
                    )
                WHERE u.score ? $2
                GROUP BY u.id, u.name, category_score, u.profile_picture, u.country
            )
            SELECT user_id, name, category_score, play_count, profile_picture, country,
                   RANK() OVER (ORDER BY category_score DESC, play_count ASC) AS rank
            FROM ScoreAndPlayCount
            ORDER BY rank;
        ";

        // Execute the query with parameter
        $result = pg_query_params($conn, $query, [$category,$s_category]);

        if ($result) {
            $players = pg_fetch_all($result);

            $topPlayers = [];

            foreach ($players as $index => $player) {
                if ($index < 3) { // Only top 3 players
                    $topPlayers[] = [
                        'name' => $player['name'],
                        'profile_picture' => $player['profile_picture']
                    ];
                }
            }

        }
    }
}


//country flag
// Load the country JSON file
$countries = json_decode(file_get_contents('country.json'), true);

// Initialize variables to hold flag and country name
$countryFlag = '';
$countryName = '';

include "../connect_pgsql.php";
if (isset($_SESSION['user_id'])) {
    $sql_coin = "SELECT coins from users where id = " . $_SESSION['user_id'];
    $temp = $pgsql->query($sql_coin);
    $coins = $temp->fetch(PDO::FETCH_ASSOC);
    $coins = $coins["coins"];
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
               extend:{
                screens: {
                'xs': '280px',
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

        .hidden-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.1s ease-out;
        }
        .expanded {
            max-height: auto; 
            transition: max-height 0.1s ease-in;
        }

        #logout-container {
            position: absolute;
            top: 45px;
            /* Adjust to position below profile picture */
            right: 20px;
            background-color: white;
            border-radius: 5px;
            padding: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>

</head>
<body class="bg-[#6859DE] min-h-screen flex-col" onload="autoExpandDropdown()">

        <!-- <div class="mx-4 mt-3 px-4 py-2 h-fit w-fit justify-self-start"> 
            <a href="home.php"><i class="fa-solid fa-arrow-left" style="color: #ffffff;"></i> &nbsp; <span class="text-white font-bold">Back to Home</span></a>
           
        </div> -->
        <div class="w-full sticky top-0 z-50">
        <nav class="w-full bg-[#D6B4FC] text-black text-lg p-4 flex justify-between items-center" id="navbar">
            <h1 class="text-2xl font-bold">🎯 Quizzie</h1>
            <div class="sm:space-x-4 xs:space-x-2 flex flex-nowrap items-center" id="nav-links">
                <a href="home.php" class="hover:underline">Home</a>
                <a href="leaderboard.php" class="hover:underline">Leaderboard</a>
                <a href="login.php" class="hover:underline" id="login-link">Login</a>
                <a href="signup.php" class="px-4 py-2 bg-[#8035FB] text-white rounded-md mr-2" id="register-link">Register</a>
            </div>

            <div class="flex items-center sm:space-x-4  hidden" id="user-profile">
                <div class="md:space-x-4 sm:space-x-4 xs:flex-wrap">
                    <a href="profile.php" class="hover:underline">Profile</a>
                    <a href="history.php" class="hover:underline">History</a>
                </div>
                <p class="px-4 py-2 bg-[#8035FB] text-[#FFDF00] rounded-md"><i class="fa-solid fa-coins" style="color: #ffffff;"></i> &nbsp; <span id="total-score"><?php echo $coins; ?></span></p>
                <div id="profile-container" class="flex items-center cursor-pointer">
                    <img src="" alt="Profile Picture" id="profile-pic" class="w-10 h-10 rounded-full cursor-pointer">
                </div>
                <!-- Logout Button (Initially hidden) -->
                <div id="logout-container" class="hidden mt-2">
                    <button id="logout" class="bg-red-500 text-white px-2  rounded w-auto h-auto">Logout</button>
                </div>
            </div>
        </nav>
    </div>

        <div class="mt-5 justify-center align-items-center text-center">
            <i class="fa-solid fa-crown transform rotate-12" style="color: #FFD43B; "></i>
            <h1 class="text-white text-3xl font-bold">Leaderboard</h1>
        </div>

        <?php if (isset($topPlayers) && count($topPlayers) >= 3) { ?>
        <div class="justify-center align-items-center mx-30 mt-10 w-full">
            <div class="flex justify-center align-items-center">
                <div class="flex flex-col items-center">
                    <div class="relative w-16 h-16 flex flex-col items-center">
                        <img src="<?php echo $topPlayers[0]['profile_picture'] ?>" class="w-16 h-16 rounded-full justify-self-center">
                        <img src="../images/rank/quality.png" class="w-8 h-8 absolute top-10 right-0 ">                    
                    </div>
                    <p class="bg-[#E1DDF5] text-bold px-4 rounded mt-3"><?php echo $topPlayers[0]['name'] ?></p>
                </div>
                
            </div>
            <div class="flex flex-row justify-center align-items-center gap-36 ">
                <div class="flex flex-col items-center">
                    <div class="relative w-16 h-16 flex flex-col items-center">
                        <img src="<?php echo $topPlayers[1]['profile_picture'] ?>" class="w-16 h-16 rounded-full justify-self-center">
                        <img src="../images/rank/second-rank.png" class="w-8 h-8 absolute top-10 right-0">
                    
                    </div>
                    <p class="bg-[#E1DDF5] text-bold px-4 rounded mt-3"><?php echo $topPlayers[1]['name'] ?></p>
                </div>
                <div class="flex flex-col items-center">
                    <div class="relative w-16 h-16 flex flex-col items-center">
                        <img src="<?php echo $topPlayers[2]['profile_picture'] ?>" class="w-16 h-16 rounded-full justify-self-center">
                        <img src="../images/rank/3rd-place.png" class="w-8 h-8 absolute top-10 right-0 "> 
                    </div>
                    <p class="bg-[#E1DDF5] text-bold px-4 rounded mt-3"><?php echo $topPlayers[2]['name'] ?></p>
                </div>
            </div>
            
        </div>
        <?php } ?>

        <!-- Category Selection Form -->
        <div class="mt-5 mb-2 flex justify-center items-center w-full px-4">
                       <!-- Category Selection Form -->
            <div class="mt-8 mb-4 flex justify-center items-center w-full px-4">
                <form method="POST" class="bg-white rounded w-fit px-4 py-2">
                    <div class="flex flex-col md:flex-row gap-3 justify-center items-center">
                        <label class="text-lg">Category:</label>
                        <select name="category" class="w-fit border rounded px-2 py-2 bg-[#E1DDF5] text-center" required>
                            <option value="" class="text-center">-- Select --</option>
                            <?php echo $options; ?>
                        </select>
                        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded">Show Top Players</button>
                    </div>
                </form>
            </div>

        </div>

        <div class="w-full flex justify-center align-items-center">
            <div class="bg-white lg:w-[850px] sm:w-[500px] h-auto flex flex-col justify-center align-items-center mt-8 px-15 py-3 mb-5 rounded">

                <div class="h-8 flex justify-center align-items-center">
                    <button id="toggleButton" onclick="toggleDropdown()"> ▼ </button>
                    
                </div>



                <div  id="extraContent" class="hidden-content mx-5 my-3">

                        <?php if ($category): ?>
                        <h2 class="text-lg mb-3">Leaderboard of category  <span class="text-purple-800"><?php echo strtoupper($category); ?></span></h2>
                        <table class="mt-3 table-auto w-full border-collapse border-white-300 rounded">
                            <thead>
                            <tr class="rounded ">
                                <th class="border-b-8 border-b-white px-4 py-2 text-left bg-[#F58A9D]">Rank</th>
                                <th class="border-b-8 border-b-white px-4 py-2 text-left bg-[#F58A9D]">Name</th>
                                <th class="border-b-8 border-b-white px-4 py-2 text-left bg-[#F58A9D]">Country</th>
                                <th class="border-b-8 border-b-white px-4 py-2 text-left bg-[#F58A9D]">Attempts</th>                                
                                <th class="border-b-8 border-b-white px-4 py-2 text-left bg-[#F58A9D]">Score</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($players): ?>
                                <?php foreach ($players as $player): ?>
                            <tr>
                                <td class=" border-b-8 border-b-white px-4 py-2 bg-[#E1DDF5] text-left"><?php echo htmlspecialchars($player['rank']); ?></td>
                                <td class=" border-b-8 border-b-white px-4 py-2 bg-[#E1DDF5] text-left">
                                    <div class="flex flex-row justify-self-start">
                                    <img src="<?php echo htmlspecialchars($player['profile_picture']); ?>" class="w-8 h-8 rounded-full">
                                    &nbsp; <?php echo htmlspecialchars($player['name']); ?>
                                    </div>
                                </td>
                                
                                
                                <td class="border-b-8 border-b-white px-4 py-2 bg-[#E1DDF5] text-center">
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
                                                <img src="<?php echo $countryFlag; ?>" alt="Flag of <?php echo $countryName; ?>" class="w-8 h-5 rounded border border-gray-400 shadow-md">
                                                <?php echo $countryName; ?>
                                                </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="border-b-8 border-b-white px-4 py-2 bg-[#E1DDF5] text-left"><?php echo htmlspecialchars($player['play_count']); ?></td>
                                <td class="border-b-8 border-b-white px-4 py-2 bg-[#E1DDF5] text-left"><?php echo htmlspecialchars($player['category_score']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="border p-2 text-center">No players found for this category.</td>
                                </tr>
                            <?php endif; ?>
                            
                            </tbody>
                        </table>
                        <?php else:  ?>
                    <p>Select category to view the leaderboard</p>
                <?php endif; ?>
                </div>
    
            </div>
         </div>

</body>
<script>
    function toggleDropdown() {
        let content = document.getElementById("extraContent");
        let button = document.getElementById("toggleButton");

        if (content.classList.contains("expanded")) {
            content.classList.remove("expanded");
            content.classList.add("hidden-content");
            content.classList.add("my-0");
            button.innerHTML = "▼";
        } else {
            content.classList.remove("hidden-content");
            content.classList.add("expanded");
            button.innerHTML = "▲";
        }
    }

    function autoExpandDropdown() {
        let content = document.getElementById("extraContent");
        let button = document.getElementById("toggleButton");

        // Expand after 1 second
        setTimeout(() => {
            content.classList.remove("hidden-content");
            content.classList.add("expanded");
            button.innerHTML = "▲";
        }, 1000);
    }

    // Example data for logged-in user (replace with actual data from your backend)
    const user = {

isLoggedIn: <?php echo isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] ? 'true' : 'false'; ?>,
profilePicture: "<?php echo isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : ''; ?>"
};

//alert(user.profilePicture);
if (user.isLoggedIn) {
// Update the navbar for logged-in user
document.getElementById("login-link").style.display = "none";
document.getElementById("register-link").style.display = "none";
document.getElementById("user-profile").classList.remove("hidden");
document.getElementById("nav-links").classList.add("xs:flex-wrap");

// Set the profile picture and score
document.getElementById("profile-pic").src = user.profilePicture;
}

//for logout
document.getElementById("profile-container").addEventListener("click", function() {

const logoutContainer = document.getElementById("logout-container");
logoutContainer.classList.toggle("hidden");
});


document.getElementById("logout").addEventListener("click", function() {
// Send logout request to the server (using PHP)
fetch('logout.php') // logout.php to handle session destruction
    .then(response => {
        if (response.ok) {
            // Redirect after logging out
            window.location.href = "home.php";
        }
    })
    .catch(error => console.error("Logout failed", error));
});
</script>

</html>
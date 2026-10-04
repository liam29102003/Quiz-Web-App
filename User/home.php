
<!-- = -->

<!DOCTYPE html>
<html>
<?php
session_start(); // Start session
require 'home_required.php';
require '../connect_redis.php';

$redis->del(keyOrKeys: "match_1");
$redis->del('inserted');

//country flag
// Load the country JSON file
$countries = json_decode(file_get_contents('country.json'), true);

// Initialize variables to hold flag and country name
$countryFlag = '';
$countryName = '';

?>

<script>
    const eventSource = new EventSource("active_user_sse.php");

    eventSource.onopen = function() {
        console.log("SSE Connection Established");
    };

    eventSource.onmessage = function(event) {
        console.log("Received Data:", event.data);

        try {
            const data = JSON.parse(event.data);
            const activeUsers = data.active_users ?? 0;
            const activeUsersElement = document.getElementById("activeUsers");
            
            if (activeUsersElement) {
                activeUsersElement.innerText = activeUsers;
            } else {
                console.error('Element with id "activeUsers" not found.');
            }
        } catch (error) {
            console.error("JSON Parsing Error:", error);
        }
    };

    eventSource.onerror = function(event) {
        console.error("SSE Connection Error:", event);
        if (eventSource.readyState === EventSource.CLOSED) {
            console.log("Connection closed by server.");
        } else if (eventSource.readyState === EventSource.CONNECTING) {
            console.log("Reconnecting...");
        }
    };
</script>


<head>
    <meta charset="UTF-8">
    <title>🎯Quizzie</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
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

        @keyframes floatInLeft {
            0% {
                transform: translateX(-100%);
                /* Start off-screen to the left */
                opacity: 0;
            }

            100% {
                transform: translateX(0);
                /* End at the normal position */
                opacity: 1;
            }
        }

        @keyframes floatInRight {
            0% {
                transform: translateX(100%);
                /* Start off-screen to the right */
                opacity: 0;
            }

            100% {
                transform: translateX(0);
                /* End at the normal position */
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        @keyframes floatUp {
            0% {
                transform: translateY(20px);
                opacity: 0;
            }

            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .hidden-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.2s ease-out;
        }

        .expanded {
            max-height: auto;
            transition: max-height 0.2s ease-in;
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

        .wrapper {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            width: 100%;

        }

        /* Hide the scrollbar but allow scrolling */
        .wrapper {
            overflow-x: scroll;
            -ms-overflow-style: none;
            /* For IE and Edge */
            scrollbar-width: none;
            /* For Firefox */
        }

        .wrapper::-webkit-scrollbar {
            display: none;
            /* For Chrome and Safari */
        }
    </style>
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

</head>
<?php
include "../connect_pgsql.php";
// if (isset($_SESSION['user_id'])) {
//     $sql_coin = "SELECT coins from users where id = " . $_SESSION['user_id'];
//     $temp = $pgsql->query($sql_coin);
//     $coins = $temp->fetch(PDO::FETCH_ASSOC);
//     $coins = $coins["coins"];
// }
if (isset($_SESSION['user_id'])) {
    $sql_coin = "SELECT coins FROM users WHERE id = :user_id";
    $stmt = $pgsql->prepare($sql_coin);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $coins = $stmt->fetch(PDO::FETCH_ASSOC);
        $coins = $coins ? $coins["coins"] : 0; // Ensure it's not null
    } else {
        $coins = 0; // Default if query fails
    }
} else {
    $coins = 0; 
}
?>

<body class="">
    <?php //echo $coins ?>
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
    <div class="w-full flex flex-row mt-10">
        <div class="md:w-1/2 sm:w-full flex flex-col justify-center pl-8 bg-white-900">
            <div class=" flex flex-col" style="animation: floatInLeft 2s ease-out;">
                <h2 class="font-bold text-4xl text-[#8035FB] font-inter"><span class="text-[#FFDF00]">Trivia </span>Quiz Game</h2>
                <p class="flex flex-wrap mt-2 text-base font-bold text-[#8035FB]">Test your knowledge and challenge yourself in exciting trivia quizzes!</p> <br />
                <a href="#" id="playNowBtn" class="bg-[#8035FB] w-fit px-4 py-2 mt-2 rounded-md">
                    <div class="flex flex-row flex-nowrap">
                        <img src="../images/icons/game_console.png" class="w-7 h-7"> &nbsp;
                        <p class="text-lg font-bold text-white text-center">Play Now</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="md:w-1/2 h-[300px] bg-white flex flex-row justify-center align-items-center mt-5 md:flex sm:hidden">
            <img src="../images/icons/Screenshot_2025-02-08_223320-removebg-preview.png" class="animate-bounce  rounded-md px-10 py-3 justify-self-center" alt="trophy">
        </div>

    </div>

    <div class="mt-3 md:w-full px-8 flex md:flex-row md:space-y-0 md:flex-nowrap justify-between sm:flex-wrap sm:space-y-4 " style="animation: floatUp 2s ease-out;">
        <div class="bg-[#D9D9D9] w-[300px] h-[120px] rounded shadow-lg flex flex-row justify-center items-center">
            <img src="../images/icons/players.png" class="w-20 h-20">
            <p class="w-full h-auto text-center text-xl"><?php echo $total_users; ?> Registered players</p>
        </div>
        <div class="bg-[#D9D9D9] w-[300px] h-[120px] rounded shadow-lg flex flex-row justify-center items-center">
            <img src="../images/icons/active_player.png" class="w-20 h-20 ">
            <p class="w-full h-auto text-center text-xl"><span id="activeUsers">0</span> Active Players</p>
        </div>
        <div class="bg-[#D9D9D9] w-[300px] h-[120px] rounded shadow-lg flex flex-row justify-center items-center">
            <img src="../images/icons/category.png" class="w-20 h-20 ">
            <p class="w-full h-auto text-center text-xl "><?php echo $total_categoriesh; ?> Categories to explore</p>
        </div>

    </div>

    <div class="wrapper overflow-x-auto">
        <div class="bg-white w-full md:w-[880px] sm:w-fit xs:w-fit sm:px-0 xs:px-0 justify-center align-items-center mt-10 lg:px-15 py-3 rounded ">

            <div class="h-8 flex justify-center align-items-center">
                <button id="toggleButton" onclick="toggleDropdown()" class="bg-[#8035FB] rounded-full w-10 h-10 text-xl text-bold text-white"> ▼ </button>

            </div>

            <div id="extraContent" class="hidden-content mx-5 my-3 h-fit">
                <h2 class="text-2xl text-[#8035FB] font-bold text-center mt-5">🏆 Top Scorers of the Day</h2>
                <h4 class="text-lg text-[#00000] text-center mt-3">The top 5 are dominating the leaderboard. Can you beat them?"</h4>
                <table class="mt-5 table-auto lg:w-full sm:w-full xs:w-fit border-collapse border-white-300 rounded h-auto">
                    <thead>
                        <tr class="rounded ">
                            <th class="border-b-8 border-b-white px-4 py-2 text-left bg-[#F58A9D]">Rank</th>
                            <th class="border-b-8 border-b-white px-4 py-2 text-left bg-[#F58A9D]">Name</th>
                            <th class="border-b-8 border-b-white px-4 py-2 text-left bg-[#F58A9D]">Country</th>
                            <th class="border-b-8 border-b-white px-4 py-2 text-left bg-[#F58A9D]">Score</th>
                            <th class="border-b-8 border-b-white px-4 py-2 text-left bg-[#F58A9D]">Total Score</th>
                            <th class="border-b-8 border-b-white px-4 py-2 text-left bg-[#F58A9D]">Attempts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        while ($row = pg_fetch_assoc($result3)):
                        ?>
                            <tr>
                                <td class=" border-b-8 border-b-white px-4 py-2 bg-[#E1DDF5] text-left"><?php echo htmlspecialchars($row['rank']);  ?></td>
                                <td class=" border-b-8 border-b-white px-4 py-2 bg-[#E1DDF5] text-left">
                                    <div class="flex flex-row flex-wrap"><img src="<?php echo htmlspecialchars($row['profile_picture']); ?>" class="w-8 h-8 rounded-full">
                                        <p class="ml-3"><?php echo htmlspecialchars($row['name']); ?></p>
                                    </div>
                                </td>
                                <td class="border-b-8 border-b-white px-4 py-2 bg-[#E1DDF5] text-left w-fit">
                                    <?php
                                    foreach ($countries as $country) {
                                        if ($country['value'] === $row['country']) {
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
                                <td class=" border-b-8 border-b-white px-3 py-2 bg-[#E1DDF5] text-left md:text-base sm:text-sm xs:text-xs">
                                    <?php
                                    $scores = json_decode($row['score'], true);
                                    foreach ($scores as $category => $score) {
                                        echo "<b>$category</b>: $score <br>";
                                    }
                                    ?>
                                </td>
                                <td class=" border-b-8 border-b-white px-4 py-2 bg-[#E1DDF5] text-left"><?php echo htmlspecialchars($row['total_score']); ?></td>
                                <td class=" border-b-8 border-b-white px-4 py-2 bg-[#E1DDF5] text-left"><?php echo htmlspecialchars($row['total_attempts']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>



        </div>
    </div>

    <footer class="text-center p-6 bg-gray-800 text-white mt-8">
        <p>© 2025 Quizzie: Trivia Quiz Game. All Rights Reserved.</p>
    </footer>


</body>
<script>
    document.getElementById("playNowBtn").addEventListener("click", function(event) {
        event.preventDefault(); // Prevent default link behavior

        fetch("utils/check_session.php")
            .then(response => response.json())
            .then(data => {
                if (data.logged_in) {
                    window.location.href = "choose_category.php"; // Redirect if user is logged in
                } else {
                    window.location.href = "login.php"; // Redirect to login if no session
                }
            })
            .catch(error => console.error("Error checking session:", error));
    });


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
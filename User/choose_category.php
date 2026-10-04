<?php include "../connect_pgsql.php"; ?>
<?php include "../connect_redis.php"; ?>
<?php

session_start();
$user_id = $_SESSION['user_id'];
// if ($redis->exists("coin".$user_id)) {
//   $coins = $redis->get("coin".$user_id);
// } else {
$redis->del(keyOrKeys: "match_1_".$user_id);
$redis->del('inserted_'.$user_id);
$redis->del('question1_'.$user_id);
$redis->del('quiz_timer_user_1_'.$user_id);
$redis->del('score_'.$user_id);
$sql = "SELECT coins, score, profile_picture, name FROM users WHERE id = :user_id";
$stmt = $pgsql->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$coins = $stmt->fetchColumn();

$scoreData = json_decode($user['score'], true); // Decode JSON to an associative array
// $true_score = $scoreData[strtolower(string: $category)] ?? 0;

$redis->setex("coin_".$user_id . $user_id, 600, $coins);


// }

if (isset($_SESSION['user_id'])) {
  $sql_coin = "SELECT coins from users where id = " . $_SESSION['user_id'];
  $temp = $pgsql->query($sql_coin);
  $coins = $temp->fetch(PDO::FETCH_ASSOC);
  $coins = $coins["coins"];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QuizzyFun - Select Category</title>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Danfo&family=Itim&display=swap"
    rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    body {
      font-family: "Itim", serif;
      font-weight: 200;
      font-style: normal;
    }

    .image-container {
      position: relative;
      display: inline-block;
    }

    .score-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.7);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s ease;
      border-radius: 0.5rem;
      font-size: 1.2rem;
      font-weight: bold;
    }

    .image-container:hover .score-overlay {
      opacity: 1;
    }

    /* Style for the fixed back button */
    .back-button {
      position: fixed;
      bottom: 20px;
      left: 20px;
      background-color: white;
      color: #6859DE;
      padding: 10px 20px;
      border-radius: 50%;
      border: 6px double #6859DE;
      cursor: pointer;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: background-color 0.3s ease;
    }

    .back-button:hover {
      background-color: #D6B4FC;
      color: #6859DE;
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

<body class="bg-gray-100 " style="background-color: white">
  <!-- Fixed Back Button -->
  <div class="back-button" onclick="goBack()">
    <i data-lucide="arrow-left"></i>
  </div>

  <!--nav bar -->
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

  <main class="max-w-6xl mx-auto py-16 px-6">
    <div class="text-center mb-12">
      <h2 class="text-4xl font-bold " style="color:#6859DE">Select a Category</h2>
      <p class="text-xl  mt-4" style="color:#6859DE">Coins Available: <span id="coinCount" class="font-semibold text-yellow-300"><?php echo $user['coins']; ?></span></p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-8">

      <?php
      if ($redis->exists("categories_".$user_id)) {
        $start = microtime(true);
        $jsonCategories = $redis->get("categories_".$user_id);
        $end = microtime(true);
        $execution_time = ($end - $start) * 1000;
        echo "<script>alert('Cache Hit: ' + $execution_time + ' ms.')</script>";
        if ($jsonCategories) {
          $categories = json_decode($jsonCategories, true); // `true` returns as associative array
        }
        //echo "<script type='text/javascript'>alert('Cache Hit. Remaining time = " . $redis->ttl("categories_".$user_id) . " seconds');</script>";
      } else {
        $start = microtime(true);
        $sql = "SELECT c.id, c.category_name, c.category_photo, c.is_api, c.api_id
                FROM category c
                JOIN question q ON c.id = q.category_id
                GROUP BY c.id
                HAVING COUNT(q.id) >= 10;";
        $stmt = $pgsql->query($sql);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $end = microtime(true);
        $execution_time = ($end - $start) * 1000;
        echo "<script>alert('Cache Miss: ' + $execution_time + ' ms.')</script>";

        $redis->setex("categories_".$user_id, 600, json_encode($categories));
       // echo "<script type='text/javascript'>alert('Cache Miss.');</script>";
      }

      if ($categories) {
        foreach ($categories as $category) { ?>
          <div class="shadow-lg hover:shadow-2xl p-2  rounded-2xl text-center transition-all cursor-pointer border-2 border-white" style="background-color: #6859DE;" onclick="showModal('<?php echo htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8'); ?>')">
            <div class="image-container">
              <?php
              echo "<img src='../images/categories/" . $category['category_photo'] . "' alt='Science' class='mx-auto mb-2 rounded-lg' style='width: 200px; height: 150px;'>";
              echo "<div class='score-overlay'>Score: ";
              if (isset($scoreData[strtolower($category['category_name'])])) {
                echo $scoreData[strtolower($category['category_name'])];
              } else {
                echo "0";
              }

              echo "</div>"; // Static placeholder score
              ?>
            </div>
            <?php
            echo "<h3 class=' rounded-md p-1 font-semibold text-xl text-dark mb-2' style='background-color:#D6B4FC'>" . $category['category_name'] . "</h3>";
            ?>
          </div>
      <?php }
      } else {
        echo "No categories found.";
      }
      ?>
    </div>
  </main>

  <!-- Modal -->
  <div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class=" p-5 rounded-lg w-96 text-center" style="background-color:#6859DE">
      <h3 id="categoryName" class="text-2xl font-bold mb-4 text-white"></h3>

      <div class="rounded-lg p-3 mb-5" style="background-color: #D6B4FC;">
        <p class="text-dark mb-2">Player Name: <span class="font-semibold"><?= $user['name'] ?></span></p>
        <p class="text-dark mb-2">Coins Available: <span id="modalCoinCount" class="font-semibold text-yellow-500"><?php echo $user['coins']; ?></span></p>
      </div>

      <a href="game.php"
        style="background-color: #D6B4FC;"
        class="text-dark px-4 py-2 rounded-lg hover:bg-gray-600 transition-all">
        Start Game
      </a>

      <button class="ml-4 text-white hover:text-red-600 transition-all" onclick="closeModal()">Cancel</button>
    </div>
  </div>

  <script>
    let coins = 100; // Initial coin count

    // Update coin count on the page and modal
    function updateCoinDisplay() {
      document.getElementById('coinCount').textContent = coins;
      document.getElementById('modalCoinCount').textContent = coins;
    }

    function showModal(category) {
      document.getElementById("categoryName").textContent = category;
      document.getElementById("categoryModal").classList.remove("hidden");
      console.log(category);
      // Store category in Redis via AJAX
      fetch("utils/store_category.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify({
            category: category
          }),
        })
        .then(response => response.json())
        .then(data => {
          console.log(data.message);
          console.log("Score:", data.score);

          // Display score in the modal (assuming you have an element with id="categoryScore")
          document.getElementById("categoryScore").textContent = `${data.score}`;
        })
        .catch(error => console.log("Error:", error));
    }



    function closeModal() {
      document.getElementById("categoryModal").classList.add("hidden");
      // location.reload();

      // Send request to delete category from Redis
      fetch("utils/delete_category.php", {
          method: "POST",
        })
        .then(response => response.json())
        .then(data => console.log(data.message))
        .catch(error => console.log("Error:", error));
    }


    // Function to go back to the previous page
    function goBack() {
      window.history.back();
    }

    // Initialize Lucide icons
    lucide.createIcons();

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
</body>

</html>
<?php include "../connect_pgsql.php"; ?>
<?php include "../connect_redis.php"; ?>


<?php
session_start();
$category = $redis->get('user_category_'.$_SESSION['user_id']);
// echo $category;
$user_id = $_SESSION['user_id'];
$sql = "SELECT coins, score, profile_picture FROM users WHERE id = :user_id";
$stmt = $pgsql->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
// print_r(value: $user['profile_picture']);
$scoreData = json_decode($user['score'], true); // Decode JSON to an associative array
$true_score = $scoreData[strtolower($category)] ?? 0;
if ($true_score >= 4000) {
    $difficultyLevel  = "hard";
} else if ($true_score >= 2000) {
    $difficultyLevel  = "medium";
} else if ($true_score >= 0) {
    $difficultyLevel  = "easy";
}
// echo $score;
// exit;

// Retrieve all data from the "match_1" list in Redis
$match_data = $redis->lrange("match_1_".$user_id, 0, -1);

// Decode the JSON data
$match_data = array_map('json_decode', $match_data, array_fill(0, count($match_data), true));

// Initialize a counter for correct answers
$correct_count = 0;
$incorrect_count = 0;
// Iterate over match data and count correct answers
foreach ($match_data as $match) {
    if (isset($match['is_correct']) && $match['is_correct'] === 1) {
        $correct_count++;
    } else {
        $incorrect_count++;
    }
}

$question_ids = array_column($match_data, 'question_id');
$question_ids_pgsql = '{' . implode(',', $question_ids) . '}';

$answer = array_column($match_data, 'user_answer');
$answer_pgsql = '{' . implode(', ', array_map(function ($value) {
    return ($value === '' || $value === null) ? '""' : (is_numeric($value) ? $value : '"' . addslashes($value) . '"');
}, $answer)) . '}';

// echo $answer_pgsql;
// return;
$stmt = "select * from category where category_name = :category_name";
$stmt = $pgsql->prepare($stmt);
$stmt->execute(['category_name' => $category]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$user_id = $_SESSION['user_id'];
$category_id = $result['id'];
$coins = ($correct_count * 10) / 2;
$score = $redis->get('score_'.$user_id);
if (!$redis->exists('inserted_'.$user_id)) {
    $sql = "INSERT INTO user_history (user_id, category_id, question_id, answer, final_score)
VALUES (:user_id, :category_id, :question_id, :answer, :final_score);";

    $stmt = $pgsql->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    $stmt->bindParam(':question_id', $question_ids_pgsql, PDO::PARAM_STR);
    $stmt->bindParam(':answer', $answer_pgsql, PDO::PARAM_STR);
    $stmt->bindParam(':final_score', $score, PDO::PARAM_INT);


    // $stmt->bindParam(':question_ids', $question_ids, PDO::PARAM_STR);  // PostgreSQL understands arrays
    $stmt->execute();
    $redis->set('inserted_'.$user_id, 1);
    $lastInsertId = $pgsql->lastInsertId();

    $sql = "UPDATE users SET coins = coins + :coins WHERE id = :user_id";
    $stmt = $pgsql->prepare($sql);
    $stmt->bindParam(':coins', $coins, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
} else {

    $sql = "SELECT * FROM user_history ORDER BY id DESC LIMIT 1";
    $stmt = $pgsql->query($sql);
    $latestRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $lastInsertId = $latestRow['id'];
}








// Print the count of correct answers
// echo "Number of correct answers: " . $correct_count . "/10 <br>";


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quiz Result</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Danfo&family=Itim&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .profile {
            background-color: #6859de;
        }

        body {
            background-color: white;
            font-family: "Itim", serif;
            font-weight: 200;
            font-style: normal;
        }

        .result {
            background-color: #d6b4fc;
        }
    </style>
</head>

<body
    class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
    <div class="bg-gray-100 p-6 rounded-2xl shadow-lg text-center max-w-md w-full">
        <!-- Profile Section -->
        <div class="profile text-white p-4 pb-10 mb-0 rounded-t-2xl">
            <div class="flex flex-col items-center">
                <div class="bg-gray-300 rounded-full w-20 h-20 mb-2">
                    <img src="<?php echo htmlspecialchars($user['profile_picture']) ?>" alt="" />
                </div>
                <p class="text-lg sm:text-xl">Game Id: <?= $lastInsertId ?></p>
                <p class="text-2xl sm:text-3xl font-bold">SCORE: <?php echo $true_score ?></p>
                <p class="text-lg sm:text-xl">Difficulty: <?= $difficultyLevel ?></p>

            </div>
        </div>

        <!-- Action Buttons -->
        <button
            onclick="restartQuiz()"
            class="border-2 border-black absolute top-400 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-gray-200 text-black px-6 py-2 rounded-lg shadow hover:bg-gray-300 sm:w-auto">
            ⬆ START AGAIN?
        </button>

        <!-- Result Summary -->
        <div class="result p-6 pt-8  mt-0 rounded-b-xl">
            <div class="bg-white border-2 border-black p-4 mt-3 rounded-xl">
                <p
                    id="correctAnswers"
                    class="text-lg sm:text-xl font-medium text-gray-800">
                    No of correct answers: <span id="score"><?php echo $correct_count ?></span>
                </p>

                <p
                    id="pointsEarned"
                    class="text-lg sm:text-xl font-medium text-green-600">
                    Points Earned: <?php echo $score  ?>
                </p>
                <p
                    id="coinsEarned"
                    class="text-lg sm:text-xl font-medium text-yellow-500">
                    Coins Earned: <?php echo $correct_count * 10 / 2  ?>
                </p>
                <p id="resultMessage" class="text-md mt-2 text-gray-600"></p>
            </div>
            <button
                onclick="finishQuiz()"
                class="border-2 border-black bg-gray-200 text-black px-4 py-2 rounded-lg shadow hover:bg-gray-300 w-full sm:w-auto mt-3">
                FINISH
            </button>
            <button
                onclick="review()"
                class="border-2 border-black bg-gray-200 text-black px-4 py-2 rounded-lg shadow hover:bg-gray-300 w-full sm:w-auto mt-3">
                Review
            </button>
        </div>

        <!-- Finish Button -->
    </div>

    <script>
        //   const correctAnswers = 3; // Example value, replace with dynamic value as needed
        const pointsPerCorrectAnswer = 10;
        const coinsPerCorrectAnswer = 5;

        const totalPoints = correctAnswers * pointsPerCorrectAnswer;
        const totalCoins = correctAnswers * coinsPerCorrectAnswer;

        //   document.getElementById(
        //     "correctAnswers"
        //   ).textContent = `No of correct answers: ${correctAnswers}`;


        const resultMessage = document.getElementById("resultMessage");
        checkScore();

        function restartQuiz() {
            window.location.href = "choose_category.php"; // Change to your quiz start page
        }

        function finishQuiz() {
            window.location.href = "home.php";
        }

        function review() {
            window.location.href = "review.php"; // Change to your quiz start page

        }

        async function checkScore() {
            // Assume this is an asynchronous operation to fetch the score.
            const correctAnswers = await getCorrectAnswers(); // Simulate an async call

            // Now, check the value of correctAnswers
            const resultMessage = document.getElementById("resultMessage");

            if (correctAnswers === 10) {
                resultMessage.textContent = "Perfect score! Amazing job!";
            } else if (correctAnswers >= 7) {
                resultMessage.textContent = "Great work! You're a quiz master!";
            } else if (correctAnswers >= 4) {
                resultMessage.textContent = "Good effort! Keep practicing!";
            } else {
                resultMessage.textContent = "Better luck next time. You can do it!";
            }
        }

        // Simulate fetching the correct answer asynchronously (replace with your actual async code)
        async function getCorrectAnswers() {
            // Example of an async operation, like fetching from the server
            return new Promise(resolve => {
                setTimeout(() => {
                    resolve(8); // Simulate fetching the score (for example, from an API)
                }, 1000);
            });
        }

        // Call the async function
        checkScore();
    </script>
</body>

</html>
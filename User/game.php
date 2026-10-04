<?php include "../connect_pgsql.php"; ?>
<?php include "../connect_redis.php"; ?>

<?php
// $redis->del('')
session_start();
$user_id = $_SESSION['user_id'];
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
    }
    else{
        $incorrect_count++;
    }
}


$category = $redis->get('user_category_'.$user_id);
$redis->del($category."_".$user_id);
// echo $category;



$sql = "SELECT coins, score FROM users WHERE id = :user_id";
$stmt = $pgsql->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$scoreData = json_decode($user['score'], true); // Decode JSON to an associative array
$score = $scoreData[strtolower($category)] ?? 0; // Get history score (default to 0 if not found)

//category photo retrieve
$sql_photo = "SELECT category_photo FROM category WHERE category_name = :category";
$stmt_photo = $pgsql->prepare($sql_photo);
$stmt_photo->execute(['category' => $category]);
$categoryPhoto = $stmt_photo->fetch(PDO::FETCH_ASSOC);

$count = $redis->llen('match_1_'.$user_id);
// echo $count;
// print_r($redis->get('match_1'));
if ($count >= 10) {
    header("Location: endgame.php");
    $redis->del('question1_'.$user_id);
    $quiz_key = "quiz_timer_user_1_".$user_id;

    if ($redis->exists($quiz_key)) {
        $redis->del($quiz_key); // Delete timer key
        echo json_encode(["message" => "Timer stopped."]);
    } else {
        echo json_encode(["message" => "No active timer."]);
    }
    exit(); // Always use exit() after a header redirect

}

if ($redis->exists($category."_".$user_id)) {
    $start = microtime(true);
    $jsonQuestions = $redis->get($category."_".$user_id);
    $end = microtime(true);
    $execution_time = ($end - $start) * 1000;
    //echo "<script>alert('Cache Hit: ' + $execution_time)</script>";
    if ($jsonQuestions) {
        $questions = json_decode($jsonQuestions, true); // `true` returns as associative array
    }
} else {
    // Fetch category ID from category name
    $start = microtime(true);
    $sql = "SELECT id FROM category WHERE category_name = :category";
    $stmt = $pgsql->prepare($sql);
    $stmt->execute(['category' => $category]);
    $end = microtime(true);
    $execution_time = ($end - $start) * 1000;
   // echo "<script>alert('Cache Miss: ' + $execution_time)</script>";
    $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($score >= 4000) {
        $difficultyLevel  = "hard";
    } else if ($score >= 2000) {
        $difficultyLevel  = "medium";
    } else if ($score >= 0) {
        $difficultyLevel  = "easy";
    }
    if ($categoryData) {
        $categoryId = $categoryData['id'];

        // Fetch questions using the retrieved category_id
        $sql = "SELECT * FROM question WHERE category_id = :category_id AND difficulty_level = :difficulty_level";
        $stmt = $pgsql->prepare($sql);
        $stmt->execute([
            'category_id' => $categoryId,
            'difficulty_level' => $difficultyLevel
        ]);

        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $questions = []; // No questions if category is not found
    }

    // Now you can use $questions array

    $redis->setex($category."_".$user_id, 6000, json_encode($questions));
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Trivia Quiz UI</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Danfo&family=Itim&display=swap"
        rel="stylesheet" />
    <link
        href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css"
        rel="stylesheet" />

    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    <style>
        body {
            font-family: "Itim", serif;
            font-weight: 200;
            font-style: normal;
            background-color: rgba(104,
                    89,
                    222,
                    1);
            /* Semi-transparent background */
        }

        .countdown {
            font-size: 10rem;
            font-weight: bold;
            color: #ffffff;
        }

        #countdown {
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            height: 100vh;
            background-color: rgba(104,
                    89,
                    222,
                    0.8);
            /* Semi-transparent background */
        }

        #question-container {
            display: none;
            margin-top: 2rem;
        }

        .centerImg {
            max-width: fit-content;

            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body>
    <div id="countdown">
        <div id="timer" class="countdown">3</div>
    </div>

    <div id="question-container" class="w-full max-w-3xl mx-auto p-4 my-auto">
        <div class="centerImg">
        <img
                src="<?php echo '../images/categories/' . htmlspecialchars($categoryPhoto['category_photo']); ?>"
                width="100"
                alt="LambdaTest"
                class="rounded-md" />
        </div>
        <h2 class="text-4xl text-center font-bold text-white mb-3">
            <?php echo ucfirst($category) ?> Quiz
        </h2>

        <div class="flex justify-between">
            <div
                class=" text-lg font-semibold px-4 py-2 rounded-lg shadow-md mb-4 w-max" style="background-color: #D6B4FC; color:rgb(63, 61, 61)">
                Your Score: <span class="font-bold"><?= $correct_count * 10 - $incorrect_count * 10 ?></span>
            </div>
            <div>
                <p class="text-xl text-white mt-4">
                    Coins Available:
                    <span id="coinCount" class="font-semibold text-yellow-300"><?= $user['coins'] ?></span>
                </p>
            </div>
        </div>
        <!-- Question Container -->
        <div class="bg-gray-100 rounded-2xl p-6 relative shadow-xl">
            <!-- Timer and Progress -->
            <div
                id="countdownWrapper"
                class="absolute -top-8 left-1/2 transform -translate-x-1/2 w-20 h-20 border-4 border-indigo-500 rounded-full flex items-center justify-center bg-gray-100">
                <div class="text-center">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-clock-9">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 7.5 12" />
                    </svg>
                    <span
                        id="countdownDisplay"
                        class="text-sm font-semibold text-gray-800">10</span>
                </div>
            </div>

            <!-- Question Info -->
            <div class="text-center mb-6 mt-10">
                <div class="text-orange-500 text-xl font-bold">
                    Question <?= $count + 1 ?> <span class="font-regular">/ 10</span>
                </div>
                <h2 class="text-2xl md:text-3xl font-semibold mt-2">
                    <?php
                    $random = array_rand($questions);

                    if (!empty($questions)) {
                        // Check if question1 is stored in Redis
                        if ($redis->exists("question1_".$user_id)) {
                            // Retrieve stored question
                            $question = json_decode($redis->get("question1_".$user_id), true);

                            // Check if the question ID is already in "match_1"
                            $match_data = $redis->lrange("match_1", 0, -1);
                            $match_data = array_map('json_decode', $match_data, array_fill(0, count($match_data), true));

                            // Loop to pick another random question if the current one is already in match_1
                            while (in_array($question['id'], array_column($match_data, 'question_id'))) {
                                $random = array_rand($questions); // Get a new random index
                                $ques = $questions[$random];

                                // Check again for uniqueness in match_1
                                $question = $ques;
                            }

                            echo $question['question']; // Display the question

                        } else {
                            // Pick a random question
                            $ques = $questions[$random];

                            // Check if the question ID is already in match_1
                            $match_data = $redis->lrange("match_1_".$user_id, 0, -1);
                            $match_data = array_map('json_decode', $match_data, array_fill(0, count($match_data), true));

                            // Loop to pick another random question if the current one is already in match_1
                            while (in_array($ques['id'], array_column($match_data, 'question_id'))) {
                                $random = array_rand($questions); // Get a new random index
                                $ques = $questions[$random];
                            }

                            // Store the question in Redis 
                            $redis->set("question1_".$user_id, json_encode($ques));
                            $question = $ques;
                            echo $question['question'];
                        }

                        
                        
                    } else {
                        echo "No questions available!";
                    }
                    ?>

                </h2>
            </div>
        </div>

        <!-- Answer Options Container -->
        <div class="grid grid-cols-1 md:grid-cols-1 gap-5 mt-5">
            <?php
            $questionKey = "question1_".$user_id;
            $_SESSION['user_id']; // Redis key for the current question
            $hiddenAnswerKey = "hidden_answer_" . $user_id; // Unique key for hidden answer per question

            // Fetch question from Redis or use a random one
            $question = $redis->exists($questionKey) ? json_decode($redis->get($questionKey), true) : $questions[$random];
            print_r($question['incorrect_answers']);
            // exit;
            // Convert incorrect answers from JSON-style string to an array
            $incorrect_answers = preg_split('/["|,]+/', trim($question["incorrect_answers"], '{}"'));

            // Fetch hidden answer from Redis (if any)
            $hiddenAnswer = $redis->exists($hiddenAnswerKey) ? json_decode($redis->get($hiddenAnswerKey), true) : "";
            // echo $hiddenAnswer;
            $all_answers = [];
            foreach ($incorrect_answers as $incorrect) {
                $isHidden = ($incorrect === $hiddenAnswer) ? "hidden" : ""; // Hide only if it's the stored answer
                $all_answers[] = ['text' => $incorrect, 'is_correct' => false, 'hidden' => $isHidden];
            }
            $all_answers[] = ['text' => $question["correct_answer"], 'is_correct' => true, 'hidden' => ""];

            // Shuffle the answers
            shuffle($all_answers);
            ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5 ">
                <?php foreach ($all_answers as $answer): ?>
                    <button id="<?= htmlspecialchars($answer["text"]) ?>"
                        class="ans-btn border-4 border-gray-300 text-lg md:text-2xl hover:bg-gray-200 font-semibold text-gray-800 py-3 rounded-lg shadow-md bg-gray-100 <?= $answer["is_correct"] ? "correct-answer" : "incorrect-answer" ?> <?= $answer["hidden"] ?>">
                        <?= htmlspecialchars($answer["text"]) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Disable Answer Button -->


        </div>



        <div class="flex justify-center mt-3">

            <?php if (!$redis->exists($hiddenAnswerKey)) { ?>
                <button id="disable-btn"
                    <?= $hiddenAnswer || $user['coins'] < 30 ? "disabled" : "" ?>
                    onclick="disableAnswer()"
                    data-tooltip-target="tooltip-default"
                    class="border-2 border-gray-300 bg-gray-100 py-2 mx-2 rounded-lg shadow-md px-1 
               text-orange-400 disabled:bg-gray-400 disabled:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-shield-question">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                        <path d="M9.1 9a3 3 0 0 1 5.82 1c0 2-3 3-3 3" />
                        <path d="M12 17h.01" />
                    </svg>
                </button>

                <div
                    id="tooltip-default"
                    role="tooltip"
                    class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-orange-400 transition-opacity duration-300 bg-white rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                    Disable 1 false answer -10 coins
                    <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
            <?php } ?>
            <button
                <?= $user['coins'] < 100 ? "disabled" : "" ?>
                data-tooltip-target="tooltip-default1" onclick="skipQuestion()"
                class="border-2 border-gray-300 bg-gray-100 py-2 mx-2 rounded-lg shadow-md px-1 text-blue-400 disabled:bg-gray-400 disabled:text-gray-600">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="lucide lucide-skip-forward">
                    <polygon points="5 4 15 12 5 20 5 4" />
                    <line x1="19" x2="19" y1="5" y2="19" />
                </svg>
            </button>
            <div
                id="tooltip-default1"
                role="tooltip"
                class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-blue-400 transition-opacity duration-300 bg-white rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                Skip Question -80 coins
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>


            <!-- <button data-tooltip-target="tooltip-default" type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Default tooltip</button> -->
        </div>
    </div>
    <div
        id="categoryModal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg w-96 text-center">
            <h3 id="categoryName" class="text-2xl font-bold mb-4 text-green-600">
                Correct Answer!
            </h3>
            <p class="text-gray-600 mb-2">
                Well done! You answered the question correctly.
            </p>
            <p class="text-gray-600 mb-4">
                Your current coins:
                <span id="modalCoinCount" class="font-semibold text-yellow-500"><?= $user['coins'] ?></span>
            </p>

            <div class="mt-4">
                <button
                    onclick="goToNextQuestion()"
                    class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-all">
                    Next Question
                </button>

            </div>
        </div>
    </div>

    <div
        id="incorrectModal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg w-96 text-center">
            <h3 class="text-2xl font-bold mb-4 text-red-600">Incorrect Answer!</h3>
            <p class="text-gray-600 mb-2">
                The correct answer was:
                <span
                    id="correctAnswerText"
                    class="font-semibold text-green-600"><?php echo $question['correct_answer'] ?></span>
            </p>
            <div class="mt-4">
                <button
                    onclick="goToNextQuestion()"
                    class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-all">
                    Next Question
                </button>

            </div>
        </div>
    </div>

    <!-- Time's Up Modal -->
    <div
        id="timesUpModal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg w-96 text-center">
            <h3 class="text-2xl font-bold mb-4 text-red-600">Time's Up!</h3>
            <p class="text-gray-600 mb-4">You ran out of time for this question.</p>

            <div class="mt-4">
                <button
                    onclick="goToNextQuestion()"
                    class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-all">
                    Next Question
                </button>

            </div>
        </div>
    </div>
    <script src="./scripts/game.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</body>

</html>
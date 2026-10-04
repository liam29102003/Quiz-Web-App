<?php include "../connect_pgsql.php"; ?>

<?php include "../connect_redis.php"; ?>

<?php
session_start();
$user_id = $_SESSION['user_id'];
$category = $redis->get('user_category_'.$_SESSION['user_id']);

$match_data = $redis->lrange("match_1_".$_SESSION['user_id'], 0, -1);
$match_data = array_map('json_decode', $match_data, array_fill(0, count($match_data), true));




// Display the result
// } else {

// Print all answers
// echo "<pre>";
// print_r($match_data);
// echo "</pre>";
// foreach($match_data as $data){



// }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quiz Game</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Danfo&family=Itim&display=swap"
    rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background-color: #6859de;

            font-family: "Itim", serif;
            font-weight: 200;
            font-style: normal;
        }

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
    </style>
</head>

<body class="">
    <div class="back-button" onclick="goBack()">
        <i data-lucide="arrow-left">Back</i>
    </div>
    <div class="max-w-4xl mx-auto p-8">
        <h1 class="text-4xl font-extrabold text-center text-white mb-10">
            Ultimate Quiz Challenge
        </h1>

        <div class="space-y-8">
            <!-- Question 1 -->
            <?php

            foreach ($match_data as $match) {


                $id = $match['question_id'];

                $jsonQuestions = $redis->get($category."_".$user_id);
                if ($jsonQuestions) {
                    $questions = json_decode($jsonQuestions, true); // `true` returns as associative array

                    $filtered = array_filter($questions, function ($q) use ($id) {
                        return $q['id'] == $id;
                    });

                    $question = reset($filtered);
                } else {
                    // Get the first matching question

                    // print_r($question);


                    $sql = "SELECT * FROM question WHERE id = :question_id";
                    $stmt = $pgsql->prepare($sql);
                    $stmt->execute(['question_id' => $id]);
                    $question = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $question = $question[0];
                }
                // print_r($question);
                $incorrect_answers = preg_split('/["|,]+/', trim($question["incorrect_answers"], '{}"'));


                // Combine incorrect answers with the correct one
                $all_answers = $incorrect_answers;
                $all_answers[] = $question["correct_answer"];

                // Shuffle the answers
                shuffle($all_answers);
                // print_r($all_answers);


                echo '
                    <div
                class="bg-gray-100 p-6 rounded-xl shadow-xl hover:shadow-2xl transition-shadow duration-300">
                <p class="text-2xl font-semibold mb-4 text-black">
                    ' . $question['question'] . '
                </p>
                <ul class="list-none space-y-3">
                ';
                $num = 1;
                foreach ($all_answers as $answer) {
                    if ($answer == $question['correct_answer']) {
                        echo '<li class="text-lg text-green-500">' . $num++ . ')' . $answer . '</li>';
                    } else {
                        echo '<li class="text-lg text-gray-700">' . $num++ . ')' . $answer . '</li>';
                    }
                }



                echo '
    </ul>
    <!-- Chosen Answer -->
    <div class="mt-4 text-lg text-indigo-600">
        <p>You chose: 
            <span class="bg-indigo-100 ' .
                    ($match['is_correct'] == 1 ? 'text-green-600' : 'text-red-500') .
                    ' text-lg font-semibold px-4 py-2 rounded-lg shadow-sm ">' .
                    $match['user_answer'] .
                    '</span> 
        </p>
    </div>
</div>';
            }
            ?>





        </div>
        <!-- <button
            onclick="back()"
            class="border-2 border-black bg-gray-200 text-black px-4 py-2 rounded-lg shadow hover:bg-gray-300 w-full sm:w-auto mt-3">
            Back
        </button> -->
    </div>
    <script>
        function back() {
            window.location.href = "endgame.html"; // Change to your quiz start page

        }

        function goBack() {
      window.history.back();
    }

    // Initialize Lucide icons
    lucide.createIcons();
    </script>
</body>

</html>
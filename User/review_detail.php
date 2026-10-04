<?php
session_start();
include "../connect_pgsql.php";
$record_id = $_GET['id'];

// Secure the SQL query with prepared statements
$sql_review = "SELECT * FROM user_history WHERE id = :record_id AND user_id = :user_id";
$stmt = $pgsql->prepare($sql_review);
$stmt->execute([
    'record_id' => $record_id,
    'user_id' => $_SESSION['user_id']
]);
$review = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch only one row

if ($review) {
    // Convert the `{1,2,3}` format into an array
    $questions = array_map('trim', explode(',', trim($review['question_id'], '{}')));
    $chosen_ans = array_map(function ($ans) {
        return trim($ans, '"'); // Removes double quotes
    }, explode(',', trim($review['answer'], '{}')));

    $incorrect_answers = [];
    $correct_answers = [];
    $question_texts = [];

    foreach ($questions as $ques) {
        $sql_ques = "SELECT question, incorrect_answers, correct_answer FROM question WHERE id = :ques";
        $stmt = $pgsql->prepare($sql_ques);
        $stmt->bindParam(':ques', $ques, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $question_texts[] = $row['question'];

            $incorrect_answers[] = array_map(function ($answer) {
                return trim($answer, '"'); // Remove surrounding double quotes
            }, explode(',', trim($row['incorrect_answers'], '{}')));

            $correct_answers[] = trim($row['correct_answer'], '"');
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quiz Game</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Danfo&family=Itim&display=swap"
      rel="stylesheet"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      body {
        background-color: #6859de;

        font-family: "Itim", serif;
        font-weight: 200;
        font-style: normal;
      }
      
    </style>
  </head>
  <body class="">
    <?php if($review) { ?>
    <div class="max-w-4xl mx-auto p-8">
      <h1 class="text-4xl font-extrabold text-center mb-10">
        <div class="text-3xl text-white"><?php echo "Match ID : " . $review['id']; ?></div>
        <div class="flex flex-row justify-center gap-3 mt-5 text-xl">
            
            <?php $date = new DateTime($review['date']); ?>
           
            
            <div class="text-md bg-[#E1DDF5] rounded px-4 py-2">
               <i class="fa-solid fa-calendar-days"></i>
                <?php echo htmlspecialchars(date_format($date, 'M j, Y')); ?>
            </div>
            <div class="text-md bg-[#E1DDF5] rounded px-4 py-2">
               <i class="fa-solid fa-clock"></i>
                <?php echo htmlspecialchars(date_format($date, 'h:i A')); ?>
            </div>
            <div class="bg-[#E1DDF5] rounded px-4 py-2">
            <i class="fa-solid fa-star"></i>
                <?php echo "Final Score : " . $review['final_score']; ?>
            </div>
        </div>
      
      </h1>

      <div class="space-y-8">
        <?php for($i=0; $i<10; $i++){ ?>
        <div class="bg-gray-100 p-6 rounded-xl shadow-xl hover:shadow-2xl transition-shadow duration-300">
            <p class="text-2xl font-semibold mb-4 text-black">
            <?php echo ($i + 1) . ". " . $question_texts[$i]; ?>
            </p>
            <ul class="list-none space-y-3">
                <?php $random = rand(0,4);
                    if($random == 1){
                        echo "<li class='text-lg font-bold text-green-600'>A) " . $correct_answers[$i] . "(Correct Answer)</li>
                            <li class='text-lg text-gray-700'>B) " . $incorrect_answers[$i][0] . "</li>
                            <li class='text-lg text-gray-700'>C) " . $incorrect_answers[$i][1] . "</li>
                            <li class='text-lg text-gray-700'>D) " . $incorrect_answers[$i][2] . "</li>";
                    }else if($random == 2) {
                        echo "<li class='text-lg text-gray-700'>A) " . $incorrect_answers[$i][0] . "</li>
                            <li class='text-lg font-bold text-green-600'>B) " . $correct_answers[$i] . "(Correct Answer)</li>
                            <li class='text-lg text-gray-700'>C) " . $incorrect_answers[$i][1] . "</li>
                            <li class='text-lg text-gray-700'>D) " . $incorrect_answers[$i][2] . "</li>";
                    }else if($random == 3){
                        echo "<li class='text-lg text-gray-700'>A) " . $incorrect_answers[$i][0] . "</li>
                            <li class='text-lg text-gray-700'>B) " . $incorrect_answers[$i][1] . "</li>
                            <li class='text-lg font-bold text-green-600'>C) " . $correct_answers[$i] . "(Correct Answer)</li>
                            <li class='text-lg text-gray-700'>D) " . $incorrect_answers[$i][2] . "</li>";
                    }else {
                        echo "<li class='text-lg text-gray-700'>A) " . $incorrect_answers[$i][0] . "</li>
                            <li class='text-lg text-gray-700'>B) " . $incorrect_answers[$i][1] . "</li>
                            <li class='text-lg text-gray-700'>C) " . $incorrect_answers[$i][2] . "</li>
                            <li class='text-lg font-bold text-green-600'>D) " . $correct_answers[$i] . "(Correct Answer)</li>";
                    }
                ?>
            </ul>
            <!-- Chosen Answer -->
            <div class="mt-4 text-lg text-indigo-600">
                <p>You chose: 
                    <?php 
                        if($chosen_ans[$i] == $correct_answers[$i]){
                            echo "<span class='bg-indigo-100 text-green-600 text-lg font-semibold px-4 py-2 rounded-lg shadow-sm'>" . $chosen_ans[$i] . " (Correct)</span>";
                        }else{
                            echo "<span class='bg-indigo-100 text-red-600 text-lg font-semibold px-4 py-2 rounded-lg shadow-sm'>" . $chosen_ans[$i] . " (Incorrect)</span>";
                        }
                    ?>
                </p>
            </div>
        </div>
        <?php } ?>
      </div>

        <button onclick="back()" class="border-2 border-black bg-gray-200 text-black px-4 py-2 rounded-lg shadow hover:bg-gray-300 w-full sm:w-auto mt-3">
            Back
        </button>
    </div>
    <?php } else { ?>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full text-center transition-all hover:scale-105">
            <div class="flex justify-center mb-4">
                <!-- Icon element -->
                <i data-lucide="alert-triangle" class="w-12 h-12 text-red-500"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-3">Oops! Match Not Found</h2>
            <p class="text-gray-600 mb-6">
                Either this match doesn't exist or you don't have permission<br>
                to view this game history.
            </p>
           <!-- <form action="category_detail.php?email=$_SESSION['back_email']&category_id=$_SESSION['category_id']" method="get"> -->
           <button onclick="window.history.go(-2)" 
        class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold
               hover:bg-indigo-700 transition-colors duration-200
               flex items-center justify-center gap-2 w-full">
    <i data-lucide="arrow-left" class="w-5 h-5"></i>
    Back 
</button>

           
            
        </div>
    </div>
<?php } ?>

    <script>
        function back(){
            //window.location.href = "window.history()";
            window.history.back();
          }

          document.addEventListener('DOMContentLoaded', function() {
                lucide.createIcons();
            });
    </script>
  </body>
</html>
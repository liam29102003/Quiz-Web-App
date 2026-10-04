<?php include "../../connect_redis.php"; ?>
<?php include "../../connect_pgsql.php"; ?>
<?php
session_start();
// echo $_SESSION['user_id'];
// require 'vendor/autoload.php'; // Load Redis library
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["answer"])) {
    echo json_encode(["error" => "No answer provided"]);
    exit;
}

// Retrieve stored question from Redis
$stored_question = json_decode($redis->get("question1_".$_SESSION['user_id']), true);

if (!$stored_question) {
    echo json_encode(["error" => "No active question"]);
    exit;
}

// Extract details from the question and user response
$question_id = $stored_question["id"];
$correct_answer = $stored_question["correct_answer"];
$user_answer = $data["answer"];
$is_correct = (strtolower(trim($user_answer)) === strtolower(trim($correct_answer)));

// Delete the "question1" key from Redis
$redis->del("question1_".$_SESSION['user_id']);

// Prepare the match data to store
$match_data = [
    "question_id" => $question_id,
    "user_answer" => $user_answer,
    "is_correct" => $is_correct ? 1 : 0
];

// Append the match data to the Redis list "match_1"
$redis->rpush("match_1_".$_SESSION['user_id'], json_encode($match_data));

// Get the current score from Redis
$current_score = $redis->get("score_".$_SESSION['user_id']);

// If the score does not exist, initialize it
if (!$current_score) {
    $current_score = 0; // Set the initial score if not found
    $redis->set("score_".$_SESSION['user_id'], $current_score);
}

$category = $redis->get(key: 'user_category_'.$_SESSION['user_id']);
$category = strtolower($category);
$user_id = $_SESSION['user_id'];
$stmt = "SELECT score FROM users WHERE id = :user_id";
$stmt = $pgsql->prepare($stmt);
$stmt->execute(['user_id' => $user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$score = $result['score'];
$score = json_decode($score, true);
$score = $score[strtolower($category)] ?? 0;
if ($is_correct) {
    // Redis update: Add 10 to the global score
    $redis->incrBy("score_".$_SESSION['user_id'], 10);
    // $score = $redis->get('score');
    // PostgreSQL raw SQL update: Add 10 to the user's score in the specific category
    $pgsql->exec("UPDATE users
                  SET score = CASE
                      WHEN score ? '" . strtolower($category) . "' THEN jsonb_set(score, '{" . $category . "}', to_jsonb(((score->>'" . $category . "')::int + 10)::text), true)
                      ELSE jsonb_set(score, '{" . strtolower($category) . "}', '0', true)
                  END
                  WHERE id = " . $user_id);
} else {
    if($score > 100){
    $redis->decrBy("score_".$_SESSION['user_id'], 10);

   


    // Raw SQL query without using prepare
    // Direct raw SQL query (using the working SQL you provided)
    $sql = "
        UPDATE users
        SET score = CASE
            WHEN score ? '".strtolower($category)."' THEN 
                jsonb_set(score, '{".strtolower($category)."}', to_jsonb(
                    GREATEST(((score->>'".strtolower($category)."')::int - 10), 0)
                ), true)
            ELSE 
               
                jsonb_set(score, '{".strtolower($category)."}', '0'::jsonb, true)
        END
        WHERE id = $user_id
    ";

    // Execute the raw SQL query directly
    $pgsql->exec($sql);
    }
}






// Return a success message as JSON
echo json_encode(["message" => "Answer stored", "is_correct" => $is_correct]);
?>
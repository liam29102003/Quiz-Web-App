<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../User/login.php');
    exit();
}

require '../User/connect.php';

// Fetch categories dynamically
function getCategories($conn)
{
    $query = "SELECT id, category_name FROM category ORDER BY category_name ASC";
    $result = pg_query($conn, $query);
    return pg_fetch_all($result) ?: [];
}

$categories = getCategories($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add a New Question</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Itim', cursive;
        }
    </style>
</head>

<body class="bg-gray-100 p-0">
    <div class="relative flex h-screen w-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="w-64 bg-[#6859DE] text-white p-3 fixed top-0 left-0 h-full flex-shrink-0 md:block">

            <h1 class="text-2xl font-bold m-3 text-center">🎯 Quizzie</h1>
            <ul class="mt-8">
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_dashboard.php" class="flex items-center"><span class="mr-2 text-lg">📊</span> Dashboard</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_leaderboard.php" class="flex items-center"><span class="mr-2 text-lg">🏆</span> Leaderboard</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_user.php" class="flex items-center"><span class="mr-2 text-lg">👥</span> Manage Players</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_category.php" class="flex items-center"><span class="mr-2 text-lg">📜</span> Manage Categories</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_question.php" class="flex items-center"><span class="mr-2 text-lg">❓</span> Manage Questions</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_account.php" class="flex items-center"><span class="mr-2 text-lg">⚙️</span> Admin Account</a></li>
            </ul>
            <a href="../User/logout.php" class="absolute bottom-0 left-0 p-4 flex items-center space-x-2 cursor-pointer">
                <i class="fas fa-sign-out-alt text-lg pl-4 py-2"></i>
                <span class="text-lg">Logout</span>
            </a>
        </div>
        <!-- Main Content -->
        <div class=" flex-1 h-screen bg-white pl-72 pr-4 pt-6 flex flex-col">
            <div class="flex justify-between mb-8">
                <h2 class="text-2xl font-bold">❓ Manage Questions</h2>
                <div class="flex items-center">
                    <span class="mr-2"><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : "admin"; ?></span>
                    <img src="uploads/<?php echo isset($_SESSION['admin_name']) ? $_SESSION['profile_picture']  : "" ?>" class="w-10 h-10 bg-pink-300 rounded-full" />
                </div>
            </div>

            <!-- Form Container -->
            <div class="flex flex-1 justify-center items-center p-6">
                <div class="w-full max-w-2xl bg-indigo-100 p-8 rounded-xl shadow-lg border border-gray-200">

                    <h2 class="text-2xl font-bold text-center">
                        📝Add a New Question
                    </h2>

                    <!-- Question Form -->
                    <form id="addQuestionForm" class="space-y-4">
                        <label class="block">
                            <span class="text-md font-semibold text-gray-700">Question</span>
                            <textarea id="question" placeholder="Enter your question here" rows="3"
                                class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 shadow-sm hover:shadow-md transition"></textarea>
                        </label>

                        <!-- Incorrect Answers -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label>
                                <span class="text-md font-semibold text-gray-700">Incorrect Answer 1</span>
                                <input type="text" id="incorrect1" placeholder="Answer 1"
                                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-red-500 shadow-sm hover:shadow-md transition">
                            </label>
                            <label>
                                <span class="text-md font-semibold text-gray-700">Incorrect Answer 2</span>
                                <input type="text" id="incorrect2" placeholder="Answer 2"
                                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-red-500 shadow-sm hover:shadow-md transition">
                            </label>
                            <label>
                                <span class="text-md font-semibold text-gray-700">Incorrect Answer 3</span>
                                <input type="text" id="incorrect3" placeholder="Answer 3"
                                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-red-500 shadow-sm hover:shadow-md transition">
                            </label>
                        </div>

                        <label class="block">
                            <span class="text-md font-semibold text-gray-700">Correct Answer</span>
                            <input type="text" id="correct_answer" placeholder="Correct Answer"
                                class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-green-500 shadow-sm hover:shadow-md transition">
                        </label>

                        <!-- Difficulty Level -->
                        <label class="block">
    <span class="text-md font-semibold text-gray-700">Difficulty Level</span>
    <select id="difficulty_level"
        class="w-full p-2 border rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 shadow-sm hover:shadow-md transition text-gray-400"
        onchange="this.classList.remove('text-gray-400')">
        <option value="" disabled selected hidden>Select Difficulty</option>
        <option value="easy" class="text-black">Easy</option>
        <option value="medium" class="text-black">Medium</option>
        <option value="hard" class="text-black">Hard</option>
    </select>
</label>


                        <!-- Category Selection -->
                        <label class="block">
    <span class="text-md font-semibold text-gray-700">Category</span>
    <select id="category_id" class="w-full p-2 border rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 shadow-sm hover:shadow-md transition text-gray-400"
    onchange="this.classList.remove('text-gray-400')">
    <option value="" disabled selected hidden>Select Category</option>
    <?php foreach ($categories as $category): ?>
        <option value="<?= htmlspecialchars($category['id']); ?>" class="text-black"><?= htmlspecialchars($category['category_name']); ?></option>
    <?php endforeach; ?>
</select>


                        </label>

                        <!-- Buttons -->
                        <div class="flex justify-between">
                            <button type="reset" id="resetBtn"
                                class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition transform hover:scale-105">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button id="submitBtn" type="submit"
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow-md hover:bg-blue-700 transition transform hover:scale-105">
                                <i class="fas fa-check"></i> Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    </div>



    <script>
       // Ensure JavaScript runs only after the DOM is fully loaded
document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM fully loaded and parsed");

    // Ensure all elements exist before referencing them
    const addQuestionForm = document.getElementById("addQuestionForm");
    const questionField = document.getElementById("question");
    const incorrect1 = document.getElementById("incorrect1");
    const incorrect2 = document.getElementById("incorrect2");
    const incorrect3 = document.getElementById("incorrect3");
    const correctAnswer = document.getElementById("correct_answer");
    const categoryDropdown = document.getElementById("category_id");
    const difficultyDropdown = document.getElementById("difficulty_level");

    if (!addQuestionForm || !questionField || !incorrect1 || !incorrect2 || !incorrect3 || !correctAnswer || !categoryDropdown || !difficultyDropdown) {
        console.error("One or more elements are missing. Check your HTML IDs.");
        return;
    }

    // ✅ Handle Form Submission
    addQuestionForm.addEventListener("submit", function (e) {
        e.preventDefault();

        let isValid = true;
        let errorMessage = "";
        const fields = [questionField, incorrect1, incorrect2, incorrect3, correctAnswer, categoryDropdown, difficultyDropdown];

        fields.forEach(field => {
            if (field.value.trim() === "") {
                field.classList.add("border-red-500");
                isValid = false;
                errorMessage = "All fields are required.";
            } else {
                field.classList.remove("border-red-500");
            }
        });

        if (!isValid) {
            Swal.fire({
                title: "Error",
                text: errorMessage,
                icon: "error",
                confirmButtonColor: "#d33"
            });
            return;
        }

        let questionData = {
            question: questionField.value.trim(),
            incorrect_answers: [incorrect1.value.trim(), incorrect2.value.trim(), incorrect3.value.trim()],
            correct_answer: correctAnswer.value.trim(),
            category_id: categoryDropdown.value,
            difficulty_level: difficultyDropdown.value
        };

        fetch("manual_insert_db.php", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(questionData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    Swal.fire({
                        title: "Error",
                        text: data.error,
                        icon: "error",
                        confirmButtonColor: "#d33"
                    });
                } else if (data.success) {
                    Swal.fire({
                        title: "Success",
                        text: data.success,
                        icon: "success",
                        confirmButtonColor: "#3085d6"
                    }).then(() => {
                        window.location.href = "view_question.php";
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: "Error",
                    text: "Failed to submit. Please try again.",
                    icon: "error",
                    confirmButtonColor: "#d33"
                });
            });
    });
});


    </script>

</body>

</html>
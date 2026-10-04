<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../User/login.php');
    exit();
}

require '../User/connect.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Fetch Questions</title>

    <!-- Tailwind CSS & FontAwesome -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Itim', cursive;
        }

        /* Custom Animations */
        .btn-hover:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease-in-out;
        }
    </style>
</head>

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
        <a href="logout.php" class="absolute bottom-0 left-0 p-4 flex items-center space-x-2 cursor-pointer">
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

        <div class="flex flex-1 justify-center items-center relative mt-[-100px] overflow-y-auto">
            <div class="w-full max-w-5xl bg-indigo-100 p-8 rounded-3xl shadow-2xl text-center border border-gray-200">
                <h2 class="text-3xl font-bold mb-6 text-blue-700 text-center">📥 Fetch Questions</h2>
                <!-- Fetch Options -->
                <div class="flex flex-wrap gap-4 mb-6 justify-center">
                    <select id="fetchCategory" class="w-full md:w-1/3 p-3 border rounded-lg bg-white shadow-sm focus:ring-2 focus:ring-blue-500 transition duration-300">
                        <option value="">📂 Select Category</option>
                    </select>

                    <input type="number" id="fetchAmount" min="1" max="20" placeholder="🔢 Number of Questions"
                        class="w-full md:w-1/3 p-3 border rounded-lg bg-white shadow-sm focus:ring-2 focus:ring-blue-500 transition duration-300 placeholder-gray-500 text-gray-800">

                    <select id="fetchDifficulty" placeholder="Select Difficulty" class="w-full md:w-1/3 p-3 border rounded-lg bg-white shadow-sm focus:ring-2 focus:ring-blue-500 transition duration-300">
                        <option value="">🎯 Any Difficulty</option>
                        <option value="easy" class="text-black">🟢 Easy</option>
                        <option value="medium" class="text-black">🟡 Medium</option>
                        <option value="hard" class="text-black">🔴 Hard</option>
                    </select>


                    <button id="fetchBtn" class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 
            text-white font-semibold px-6 py-3 rounded-full shadow-md transition duration-300 ease-in-out flex items-center gap-2 btn-hover">
                        <i class="fas fa-download text-lg"></i> Fetch Questions
                    </button>
                </div>

                <!-- Questions Table (Fixed Height & Scrollable) -->
                <div id="questionsContainer"
                    class="hidden max-h-[400px] overflow-y-auto w-full max-w-5xl bg-white p-6 rounded-3xl shadow-lg border border-gray-300">
                    <div class="overflow-y-auto max-h-[300px] border border-gray-300 rounded-lg shadow-md">
                        <table class="min-w-full bg-white border-collapse">
                            <!-- Fixed Header -->
                            <thead class="bg-gradient-to-r from-blue-600 to-blue-500 text-white sticky top-0 z-10">
                                <tr>
                                    <th class="py-3 px-4 border">Question</th>
                                    <th class="py-3 px-4 border">Incorrect Answers</th>
                                    <th class="py-3 px-4 border">Correct Answer</th>
                                    <th class="py-3 px-4 border">Category</th>
                                    <th class="py-3 px-4 border">Difficulty</th>
                                    <th class="py-3 px-4 border text-center w-40">Actions</th>
                                </tr>
                            </thead>
                            <!-- Scrollable Body -->
                            <tbody id="questionTableBody" class="text-gray-700 text-sm">
                                <tr id="noQuestionsRow">
                                    <td colspan="6" class="text-center text-gray-500 py-4">No questions fetched yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ✅ Edit Modal (Fix Layout Issues) -->
                <div id="editModal" class="hidden fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50">
                    <div class="bg-white p-6 rounded-xl shadow-lg w-11/12 md:w-1/3 transform transition-all scale-95">
                        <h2 class="text-2xl font-bold mb-4">✏️ Edit Question</h2>
                        <input type="hidden" id="editIndex">

                        <label class="block text-lg font-semibold text-gray-700 tracking-wide mb-2">Question</label>
                        <textarea id="editQuestion" row="5" class="w-full p-3 border rounded mb-3"></textarea>

                        <label class="block text-lg font-semibold text-gray-700 tracking-wide mb-2">Incorrect Answers (comma separated)</label>
                        <input type="text" id="editIncorrect" class="w-full p-3 border rounded mb-3">

                        <label class="block text-lg font-semibold text-gray-700 tracking-wide mb-2">Correct Answer</label>
                        <input type="text" id="editCorrect" class="w-full p-3 border rounded mb-3">

                        <label class="block text-lg font-semibold text-gray-700 tracking-wide mb-2">Difficulty Level</label>
                        <select id="editDifficulty" class="w-full p-3 border rounded bg-gray-50 mb-3">
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>

                        <div class="flex justify-end space-x-2">
                            <button id="saveEdit" class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow-md text-lg flex items-center gap-2">
                                <i class="fas fa-save"></i> Save
                            </button>
                            <button id="closeModal" class="bg-gray-500 text-white px-5 py-2 rounded-lg text-lg flex items-center gap-2">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        fetch("fetch_categories.php")
                            .then(response => response.json())
                            .then(data => {
                                let categorySelect = document.getElementById("fetchCategory");
                                categorySelect.innerHTML = '<option value="">📂 Select Category</option>'; // Reset dropdown

                                if (!data.categories || data.categories.length === 0) {
                                    Swal.fire("⚠️ No categories found. Please check API.", "warning");
                                    return;
                                }

                                data.categories.forEach(category => {
                                    let option = document.createElement("option");
                                    option.value = category.id; // Ensure this is the API ID
                                    option.textContent = category.name;
                                    categorySelect.appendChild(option);
                                });
                            })
                            .catch(error => console.error("Error fetching categories:", error));

                        // ✅ Fetch Questions from API
                        document.getElementById("fetchBtn").addEventListener("click", function() {
                            let categoryId = document.getElementById("fetchCategory").value;
                            let amount = document.getElementById("fetchAmount").value;
                            let difficulty = document.getElementById("fetchDifficulty").value;

                            if (!categoryId || !amount) {
                                Swal.fire({
                                    icon: "warning",
                                    html: '<span style="font-size: 16px;">⚠️ Please select both category & number of questions.</span>',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                return;
                            }

                            fetch(`fetch_questions.php?category_id=${categoryId}&amount=${amount}&difficulty=${difficulty}`)
                                .then(response => response.json())
                                .then(data => {
                                    let tbody = document.getElementById("questionTableBody");
                                    tbody.innerHTML = ""; // ✅ Clear previous content

                                    // ✅ Ensure the questions container remains visible
                                    let questionsContainer = document.getElementById("questionsContainer");

                                    if (!data.questions || data.questions.length === 0) {
                                        questionsContainer.classList.add("hidden"); // ✅ Hide the table if empty
                                        Swal.fire("No questions found for this category");
                                        return;
                                    }

                                    // ✅ Show the table only if questions exist
                                    questionsContainer.classList.remove("hidden");

                                    data.questions.forEach((q, index) => {
                                        let row = document.createElement("tr");
                                        row.setAttribute("data-index", index);
                                        row.setAttribute("data-category-id", categoryId);
                                        row.innerHTML = `
                        <td class="py-2 px-4 border">${q.question}</td>
                        <td class="py-2 px-4 border text-red-600">${q.incorrect_answers.join(", ")}</td>
                        <td class="py-2 px-4 border text-green-600 font-bold">${q.correct_answer}</td>
                        <td class="py-2 px-4 border">${q.category_name}</td>
                        <td class="py-2 px-4 border">${q.difficulty_level}</td>
                        <td class="py-2 px-4 border text-center">
                            <div class="flex justify-center space-x-2">
                                <button class="editBtn bg-blue-500 text-white px-3 py-2 rounded shadow-md hover:bg-blue-600 transition duration-200" data-index="${index}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="saveBtn bg-green-500 text-white px-3 py-2 rounded shadow-md hover:bg-green-600 transition duration-200" data-index="${index}">
                                    <i class="fas fa-save"></i>
                                </button>
                                <button class="rejectBtn bg-red-500 text-white px-3 py-2 rounded shadow-md hover:bg-red-600 transition duration-200" data-index="${index}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    `;
                                        tbody.appendChild(row);
                                    });

                                    // ✅ Reattach event listeners for new rows
                                    attachEventListeners();
                                })
                                .catch(error => console.error("Error fetching questions:", error));
                        });
                    });

                    // ✅ Function to Attach Event Listeners
                    function attachEventListeners() {
                        // ✅ Edit Button Functionality
                        document.querySelectorAll(".editBtn").forEach(btn => {
                            btn.addEventListener("click", function() {
                                let index = btn.getAttribute("data-index");
                                let row = document.querySelector(`tr[data-index="${index}"]`);

                                document.getElementById("editIndex").value = index;
                                document.getElementById("editQuestion").value = row.cells[0].innerText;
                                document.getElementById("editIncorrect").value = row.cells[1].innerText;
                                document.getElementById("editCorrect").value = row.cells[2].innerText;

                                // ✅ Handle Selected Difficulty Level
                                let difficultySelect = document.getElementById("editDifficulty");
                                let selectedDifficulty = row.cells[4].innerText.trim().toLowerCase();

                                // ✅ Find and set the correct option as selected
                                difficultySelect.value = selectedDifficulty;

                                // ✅ Show the edit modal
                                document.getElementById("editModal").classList.remove("hidden");
                            });
                        });

                        // ✅ Save Edit Changes
                        document.getElementById("saveEdit").addEventListener("click", function() {
                            let index = document.getElementById("editIndex").value;
                            let row = document.querySelector(`tr[data-index="${index}"]`);

                            row.cells[0].innerText = document.getElementById("editQuestion").value;
                            row.cells[1].innerText = document.getElementById("editIncorrect").value;
                            row.cells[2].innerText = document.getElementById("editCorrect").value;
                            row.cells[4].innerText = document.getElementById("editDifficulty").value;

                            document.getElementById("editModal").classList.add("hidden");
                        });

                        // ✅ Close Edit Modal
                        document.getElementById("closeModal").addEventListener("click", function() {
                            document.getElementById("editModal").classList.add("hidden");
                        });

                        // ✅ Save Question to Database
                        document.querySelectorAll(".saveBtn").forEach(btn => {
                            btn.addEventListener("click", function() {
                                let index = btn.getAttribute("data-index");
                                let row = document.querySelector(`tr[data-index="${index}"]`);

                                // ✅ Retrieve category ID from the row's data attribute
                                let selectedCategoryId = row.getAttribute("data-category-id");

                                // ✅ Validate category ID
                                if (!selectedCategoryId) {
                                    Swal.fire("⚠️ Please select a valid category before saving.", "error");
                                    return;
                                }

                                let questionData = {
                                    question: row.cells[0].innerText.trim(),
                                    incorrect_answers: row.cells[1].innerText.trim().split(", "),
                                    correct_answer: row.cells[2].innerText.trim(),
                                    difficulty_level: row.cells[4].innerText.trim(),
                                    category_id: selectedCategoryId,
                                };

                                fetch("api_insert_db.php", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json"
                                        },
                                        body: JSON.stringify(questionData)
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            row.remove();
                                            checkIfTableEmpty();
                                            Swal.fire({
                                                icon: "success",
                                                title: "Saved!",
                                                text: "Question added successfully.",
                                                timer: 1500,
                                                showConfirmButton: false
                                            });
                                        } else {
                                            Swal.fire("⚠️ Warning", data.error);
                                        }
                                    })
                                    .catch(error => console.error("Error saving question:", error));
                            });
                        });

                        // ✅ Reject Button Functionality (Removes row)
                        document.querySelectorAll(".rejectBtn").forEach(btn => {
                            btn.addEventListener("click", function() {
                                let row = btn.closest("tr");
                                row.remove();
                                checkIfTableEmpty();
                            });
                        });
                    }

                    // ✅ Hide Table If No Rows Left
                    function checkIfTableEmpty() {
                        let tbody = document.getElementById("questionTableBody");
                        if (tbody.childElementCount === 0) {
                            document.getElementById("questionsContainer").classList.add("hidden");
                        }
                    }
                </script>

                </body>

</html>
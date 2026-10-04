<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../User/login.php');
    exit();
}

require '../User/connect.php';

// Pagination settings
$limit = 100; // Number of questions per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // Ensure page is at least 1
$offset = ($page - 1) * $limit; // Calculate offset for SQL query

// Get total question count
$total_query = "SELECT COUNT(*) FROM question";
$total_result = pg_query($conn, $total_query);
$total_row = pg_fetch_row($total_result);
$total_questions = intval($total_row[0]);

// Fetch questions with category names (ASCENDING ORDER)
$query = "SELECT q.id, q.question, q.incorrect_answers, q.correct_answer, q.difficulty_level, c.category_name
          FROM question q 
          JOIN category c ON q.category_id = c.id 
          ORDER BY q.id ASC
          LIMIT $limit OFFSET $offset"; // ✅ Add LIMIT and OFFSET for pagination

$result = pg_query($conn, $query);
$questions = [];

while ($row = pg_fetch_assoc($result)) {
    $incorrect_answers = str_replace(['{', '}'], '', $row['incorrect_answers']);
    $incorrect_answers_array = array_map(fn($ans) => trim(str_replace('"', '', $ans)), explode(',', $incorrect_answers));
    $row['incorrect_answers'] = (!empty($incorrect_answers_array) && $incorrect_answers_array !== [""]) ? implode(", ", $incorrect_answers_array) : "N/A";
    $questions[] = $row;
}

// Calculate total pages
$total_pages = ceil($total_questions / $limit);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Questions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <!-- Custom CSS for Button Animation -->
    <style>
        body {
            font-family: 'Itim', cursive;
        }
        thead {
    background-color: #1E40AF; /* Original Blue */
    transition: background-color 0.3s ease-in-out, filter 0.3s ease-in-out;
}

/* When modal is open, dim only the table header */
.modal-active thead {
    background-color: #1E3A8A; /* Slightly Darker Blue */
    filter: brightness(70%);
}

        .animate-spin-on-hover:hover i {
            animation: spin 0.5s linear;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="bg-gray-100 p-0">
    <div class="relative flex h-screen w-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="w-64 bg-[#6859DE] text-white p-3 fixed top-0 left-0 h-full flex-shrink-0 md:block">

            <h1 class="text-2xl font-bold m-3 text-left">🎯 Quizzie</h1>
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
        <div class=" flex-1 h-screen bg-white pl-72 pr-4 pt-6 pb-6 flex flex-col">
            <div class="flex justify-between mb-8">
                <h2 class="text-2xl font-bold">❓ Manage Questions</h2>
                <div class="flex items-center">
                    <span class="mr-2"><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : "admin"; ?></span>
                    <img src="uploads/<?php echo isset($_SESSION['admin_name']) ? $_SESSION['profile_picture']  : "" ?>" class="w-10 h-10 bg-pink-300 rounded-full" />
                </div>
            </div>

            <!-- Search Bar & Add Question Button -->
            <div class="flex justify-between items-center mb-6">
                <!-- Add Question Button -->
                <button id="openAddModal"
                    class="bg-gradient-to-r from-blue-500 to-blue-700 text-white font-semibold px-5 py-4 rounded-lg shadow-md text-sm 
           hover:shadow-lg hover:from-blue-600 hover:to-blue-800 transition-all duration-300 ease-in-out transform hover:scale-105 
           flex items-center gap-2 whitespace-nowrap min-w-max">
                    <i class="fas fa-plus"></i> Add Question
                </button>

                <!-- Add Question Options Modal -->
                <div id="addQuestionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4">
                    <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl transform transition-transform scale-95">
                        <h2 class="text-xl font-bold mb-4 text-gray-700">Choose How to Add Questions</h2>
                        <p class="text-gray-600 mb-4">Would you like to add questions manually or fetch them automatically?</p>

                        <div class="flex flex-col space-y-4">
                            <!-- Manual Add -->
                            <button id="manualAddBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-pencil-alt"></i> Manually Add Questions
                            </button>

                            <!-- Fetch Automatically -->
                            <button id="fetchAutoBtn" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                <i class="fas fa-download"></i> Fetch Questions Automatically
                            </button>

                            <!-- Cancel Button -->
                            <button id="closeAddModal" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search Bar & Refresh Button Container (Right-Aligned) -->
                <div class="flex justify-end items-center space-x-3 w-full">
                    <!-- Search Bar (Wider & Aligned to the Right) -->
                    <div class="flex items-center bg-white/80 backdrop-blur-md px-3 py-2 rounded-full shadow-md w-64 md:w-80 lg:w-[320px] border border-gray-300 focus-within:ring-2 focus-within:ring-blue-500 transition">
                        <i class="fas fa-search text-gray-500"></i>
                        <input type="text" id="searchInput" placeholder="Search questions..."
                            class="w-full bg-transparent text-gray-700 placeholder-gray-400 focus:outline-none text-sm ml-3">
                    </div>


                    <!-- Refresh Button (Separate but Aligned) -->
                    <button id="refreshBtn" class="text-blue-600 hover:text-blue-800 transition duration-300 ease-in-out transform hover:scale-110">
                        <i class="fas fa-sync-alt animate-spin-on-hover text-xl"></i>
                    </button>
                </div>
            </div>


            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-300 rounded-lg shadow-md">
                <thead class="sticky top-0 bg-blue-600 text-white uppercase text-sm">
                        <tr class="bg-blue-600 text-white uppercase text-sm">
                            <th class="py-3 px-4 border text-center">ID</th>
                            <th class="py-3 px-4 border text-center">Question</th>
                            <th class="py-3 px-4 border text-center">Incorrect Answers</th>
                            <th class="py-3 px-4 border text-center">Correct Answer</th>
                            <th class="py-3 px-4 border text-center">Category</th>
                            <th class="py-3 px-4 border text-center">Difficulty</th>
                            <th class="py-3 px-4 border text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="questionTableBody" class="text-gray-700 text-sm">
                        <?php foreach ($questions as $q): ?>
                            <tr class="border-b hover:bg-gray-100">
                                <td class="py-2 px-4 border text-center"><?= $q['id'] ?></td>
                                <td class="py-2 px-4 border question"><?= htmlspecialchars($q['question']) ?></td>
                                <td class="py-2 px-4 border text-red-600"><?= htmlspecialchars($q['incorrect_answers']) ?></td>
                                <td class="py-2 px-6 border font-semibold text-green-600"><?= htmlspecialchars($q['correct_answer']) ?></td>
                                <td class="py-2 px-4 border"><?= htmlspecialchars($q['category_name']) ?></td>
                                <td class="py-2 px-4 border text-center"><?= htmlspecialchars($q['difficulty_level']) ?></td>
                                <td class="py-2 px-4 border text-center">
                                    <div class="flex justify-center space-x-4">
                                        <button class="edit-btn text-blue-600 hover:text-blue-800" data-id="<?= $q['id'] ?>">
                                            <i class="fas fa-edit text-xl"></i>
                                        </button>
                                        <button class="delete-btn text-red-600 hover:text-red-800" data-id="<?= $q['id'] ?>">
                                            <i class="fas fa-trash-alt text-xl"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
            <!-- Pagination -->
            <div class="mt-6 flex justify-center">
                <nav class="inline-flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>"
                            class="px-4 py-2 bg-gray-200 text-gray-700 hover:bg-gray-300 transition rounded-full shadow-md">
                            &larr; Prev
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>"
                            class="px-4 py-2 <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> 
                          transition rounded-full shadow-md">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>"
                            class="px-4 py-2 bg-gray-200 text-gray-700 hover:bg-gray-300 transition rounded-full shadow-md">
                            Next &rarr;
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>

    <script>
     document.addEventListener("DOMContentLoaded", () => {
    attachEventListeners(); // Attach initial event listeners

    // Refresh Button Functionality
    document.getElementById("refreshBtn").addEventListener("click", () => {
        document.getElementById("searchInput").value = ""; // Clear search input
        setTimeout(() => {
            location.reload(); // Reload page after clearing input
        }, 100); // Small delay for a smoother experience
    });

    // Handle Search Input with Debouncing
    let debounceTimeout;
    document.getElementById("searchInput").addEventListener("input", function() {
        const query = this.value.trim().toLowerCase();
        if (query === "") {
            return; // Don't trigger search if query is empty
        }

        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
            // Fetch the search results from the server
            fetchSearchResults(query, 1); // Default to page 1
        }, 500); // Debounce delay of 500ms
    });

    // Function to Fetch Search Results with Pagination
    function fetchSearchResults(query, page = 1) {
        fetch(`search_question.php?query=${encodeURIComponent(query)}&page=${page}`)
            .then(response => response.json())
            .then(data => {
                if (data.questions && data.questions.length > 0) {
                    updateTable(data.questions, query); // Pass query to updateTable for highlighting
                    setupPagination(data.total_pages, data.current_page, query);
                } else {
                    document.getElementById("questionTableBody").innerHTML = '<tr><td colspan="7" class="text-center py-4 text-gray-500">No matching questions found.</td></tr>';
                }
            })
            .catch(error => console.error("Error fetching search results:", error));
    }

    // Function to Update the Table with New Data
    function updateTable(questions, searchQuery) { // Pass searchQuery to this function
        const tableBody = document.getElementById("questionTableBody");
        tableBody.innerHTML = ""; // Clear current table rows

        questions.forEach(q => {
            const regex = new RegExp(`(${searchQuery})`, "gi"); // Use searchQuery to highlight matches
            const highlightedQuestion = q.question.replace(regex, `<span class="bg-yellow-300">$1</span>`);

            const row = `
                <tr class="border-b hover:bg-gray-100">
                    <td class="py-2 px-4 border text-center">${q.id}</td>
                    <td class="py-2 px-4 border">${highlightedQuestion}</td>
                    <td class="py-2 px-4 border text-red-600">${q.incorrect_answers}</td>
                    <td class="py-2 px-6 border font-semibold text-green-600">${q.correct_answer}</td>
                    <td class="py-2 px-4 border">${q.category_name}</td>
                    <td class="py-2 px-4 border text-center">${q.difficulty_level}</td>
                    <td class="py-2 px-4 border text-center">
                        <div class="flex justify-center space-x-4">
                            <button class="edit-btn text-blue-600 hover:text-blue-800" data-id="${q.id}">
                                <i class="fas fa-edit text-xl"></i>
                            </button>
                            <button class="delete-btn text-red-600 hover:text-red-800" data-id="${q.id}">
                                <i class="fas fa-trash-alt text-xl"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML("beforeend", row);
        });

        // Re-attach event listeners to Edit and Delete buttons
        attachEventListeners();
    }

    // Function to Set Up Pagination Buttons
    function setupPagination(totalPages, currentPage, query) {
        const paginationContainer = document.getElementById("pagination");
        paginationContainer.innerHTML = ""; // Clear existing pagination buttons

        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement("button");
            button.textContent = i;
            button.classList.add("page-button");
            if (i === currentPage) {
                button.classList.add("active");
            }
            button.addEventListener("click", () => fetchSearchResults(query, i)); // On click, load corresponding page
            paginationContainer.appendChild(button);
        }
    }

    // Function to Attach Event Listeners for Edit and Delete buttons
    function attachEventListeners() {
        document.querySelectorAll(".edit-btn").forEach(btn => {
            btn.removeEventListener("click", handleEditClick);
            btn.addEventListener("click", handleEditClick);
        });

        document.querySelectorAll(".delete-btn").forEach(button => {
            button.removeEventListener("click", handleDeleteClick);
            button.addEventListener("click", handleDeleteClick);
        });
    }

    // Handle Edit Button Click
    function handleEditClick() {
        let btn = this;
        let row = btn.closest("tr");
        let id = btn.dataset.id;
        let question = row.cells[1].innerText;
        let incorrect = row.cells[2].innerText === "N/A" ? "" : row.cells[2].innerText;
        let correct = row.cells[3].innerText;
        let difficulty = row.cells[5].innerText.toLowerCase();

        Swal.fire({
            title: "Edit Question",
            width: "70%",
            html: `
                <div class="text-left space-y-4">
                    <label class="block font-semibold">Question</label>
                    <textarea id="swal-question" class="w-full p-3 border rounded-lg shadow-sm">${question}</textarea>
                    <label class="block font-semibold">Incorrect Answers (comma separated)</label>
                    <textarea id="swal-incorrect" class="w-full p-3 border rounded-lg shadow-sm">${incorrect}</textarea>
                    <label class="block font-semibold">Correct Answer</label>
                    <input id="swal-correct" class="w-full p-3 border rounded-lg shadow-sm" value="${correct}">
                    <label class="block font-semibold">Difficulty Level</label>
                    <select id="swal-difficulty" class="w-full p-3 border rounded-lg shadow-sm">
                        <option value="easy" ${difficulty === 'easy' ? 'selected' : ''}>Easy</option>
                        <option value="medium" ${difficulty === 'medium' ? 'selected' : ''}>Medium</option>
                        <option value="hard" ${difficulty === 'hard' ? 'selected' : ''}>Hard</option>
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: "Save Changes",
            preConfirm: () => {
                return {
                    id: id,
                    question: document.getElementById("swal-question").value,
                    incorrect_answers: document.getElementById("swal-incorrect").value.split(",").map(a => a.trim()),
                    correct_answer: document.getElementById("swal-correct").value,
                    difficulty_level: document.getElementById("swal-difficulty").value
                };
            }
        }).then(result => {
            if (result.isConfirmed) {
                fetch("update_question.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(result.value)
                }).then(response => response.json())
                    .then(data => {
                        Swal.fire(data.success ? "Success" : "Error", data.success || data.error, data.success ? "success" : "error")
                            .then(() => location.reload());
                    });
            }
        });
    }

    // Handle Delete Button Click
    function handleDeleteClick() {
        let button = this;
        let id = button.dataset.id;

        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to recover this question!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "No, cancel!"
        }).then(result => {
            if (result.isConfirmed) {
                fetch("delete_question.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `delete_id=${id}`
                }).then(() => location.reload());
            }
        });
    }
    //  Add Question Options (Modal)
    const openAddModalBtn = document.getElementById("openAddModal");
            const addQuestionModal = document.getElementById("addQuestionModal");
            const closeAddModalBtn = document.getElementById("closeAddModal");
            const manualAddBtn = document.getElementById("manualAddBtn");
            const fetchAutoBtn = document.getElementById("fetchAutoBtn");

            openAddModalBtn.addEventListener("click", () => {
                addQuestionModal.classList.remove("hidden");
                document.body.classList.add("modal-active"); // Add class to trigger dimming
                addQuestionModal.classList.add("scale-100");
            });

            closeAddModalBtn.addEventListener("click", () => {
                addQuestionModal.classList.add("hidden");
                document.body.classList.remove("modal-active"); // Remove dim effect
                addQuestionModal.classList.remove("scale-100");
            });

            manualAddBtn.addEventListener("click", () => {
                window.location.href = "add_question.php";
            });

            fetchAutoBtn.addEventListener("click", () => {
                window.location.href = "fetch_question.php";
            });
});


    </script>

</body>

</html>
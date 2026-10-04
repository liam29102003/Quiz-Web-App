<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../User/login.php');
    exit();
}

require '../User/connect.php';  // PostgreSQL connection
require '../connect_redis.php'; //redis connection

// Load country.json file
$countryFilePath = '../User/country.json'; // Ensure correct path based on your file location
if (file_exists($countryFilePath)) {
    $countries_json = file_get_contents($countryFilePath);
    $countries = json_decode($countries_json, true);
} else {
    $countries = []; // Fallback if file is missing
}

// Pagination settings
$limit = 8;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Get total user count
$total_query = "SELECT COUNT(*) FROM users";
$total_result = pg_query($conn, $total_query);
$total_row = pg_fetch_row($total_result);
$total_users = intval($total_row[0]);

// Fetch paginated users
$query = "SELECT id, name, email, country, profile_picture, 
                 (SELECT SUM(value::INT) FROM jsonb_each_text(score)) AS total_score 
          FROM users 
          ORDER BY id ASC 
          LIMIT $limit OFFSET $offset";

$result = pg_query($conn, $query);

if (!$result) {
    die("Error fetching users: " . pg_last_error($conn));
}
$users = pg_fetch_all($result);

// Calculate total pages
$total_pages = ceil($total_users / $limit);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Players</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Itim', cursive;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;


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
        <div class=" flex-1 h-screen bg-white pl-72  pr-4 pt-6 flex flex-col pr-10 overflow-auto">

            <div class="flex justify-between mb-8">
                <h2 class="text-2xl font-bold m-0">👥 Manage Players</h2>
                <div class="flex items-center mr-5">
                    <span class="mr-2"><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : "admin"; ?></span>
                    <img src="uploads/<?php echo isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture']  : "" ?>" class="w-10 h-10 bg-pink-300 rounded-full" />
                </div>
            </div>
            <!-- Search Bar & Refresh Button Container (Aligned with Table) -->
            <div class="flex justify-end items-center space-x-2 mr-5">
                <!-- Search Bar (Moved Left Slightly) -->
                <div class="flex items-center bg-white/80 backdrop-blur-md px-2 py-2 rounded-full shadow-md w-42 md:w-64 lg:w-72 border border-gray-300 focus-within:ring-2 focus-within:ring-blue-500 transition -ml-16">
                    <i class="fas fa-search text-gray-500"></i>
                    <input type="text" id="searchInput" placeholder="Search players..."
                        class="w-full bg-transparent text-gray-700 placeholder-gray-400 focus:outline-none text-sm">
                </div>

                <!-- Refresh Button (Aligned with Table Border) -->
                <button id="refreshBtn" class="text-blue-600 hover:text-blue-800 transition duration-300 ease-in-out transform hover:scale-110">
                    <i class="fas fa-sync-alt animate-spin-on-hover text-xl"></i>
                </button>
            </div>

            <!-- Players Table -->
            <div class="mt-6 overflow-x-auto bg-white shadow-lg rounded-lg border mr-5">

                <table class="min-w-full bg-white rounded-lg">
                    <thead class="bg-blue-600 text-white uppercase text-sm">
                        <tr>
                            <th class="py-3 px-4 text-left">ID</th>
                            <th class="py-3 px-4 text-left">Name</th>
                            <th class="py-3 px-4 text-left">Photo</th>
                            <th class="py-3 px-4 text-left">Email</th>
                            <th class="py-3 px-4 text-center">Country</th>
                            <th class="py-3 px-4 text-left">Total Score</th>
                            <th class="py-3 px-4 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody" class="text-gray-700 text-sm">
                        <?php foreach ($users as $user): ?>
                            <tr class="border-b hover:bg-gray-100 transition">
                                <td class="py-2 px-4 text-left"><?php echo htmlspecialchars($user['id']); ?></td>
                                <td class="py-2 px-4 text-left"><?php echo htmlspecialchars($user['name']); ?></td>
                                <td class="py-2 px-4 text-left">
                                    <!--<img src="../images/users/<?php echo htmlspecialchars($user['profile_picture']); ?>" -->
                                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>"

                                        alt="Profile of <?php echo htmlspecialchars($user['name']); ?>" 
                                        class="w-10 h-10 rounded-full border border-gray-300 shadow-sm">
                                        
                                </td>

                                <td class="py-2 px-4 text-left"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="py-2 px-4 text-center">
                                    <?php
                                    // Initialize variables to avoid "Undefined variable" error
                                    $countryFlag = null;
                                    $countryName = null;

                                    // Check if the country exists in the country.json data
                                    foreach ($countries as $country) {
                                        if ($country['value'] === $user['country']) {
                                            $countryFlag = $country['data-image']; // Assign the flag URL
                                            $countryName = $country['value']; // Assign the country name
                                            break;
                                        }
                                    }
                                    ?>

                                    <div class="flex items-center gap-3 px-3 py-2 rounded-lg shadow-none w-48 mx-auto">
                                        <?php if ($countryFlag): ?>
                                            <img src="<?php echo htmlspecialchars($countryFlag); ?>"
                                                alt="Flag of <?php echo htmlspecialchars($countryName); ?>"
                                                class="w-8 h-5 rounded border border-gray-400 shadow-md">
                                            
                                        <?php else: ?>
                                            <span class="text-gray-400 italic">No Flag</span> <!-- Show if no flag available -->
                                        <?php endif; ?>
                                        <span class="font-medium text-gray-800 truncate block w-full text-sm"><?php echo htmlspecialchars($countryName); ?></span>
                                    </div>
                                </td>



                                <td class="py-2 px-4 text-left">
                                    <?php echo htmlspecialchars($user['total_score'] ?? '0'); ?>
                                </td>

                                <td class="py-2 px-4 text-left">
                                    <?php
                                    //$status = $redis->get('active_users:' . $user['id']);
                                    if ($redis->sismember('active_users', $user['id'])) {
                                        // If status exists, the user is active
                                        echo '<span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full">Active</span>';
                                    } else {
                                        // If status does not exist, the user is offline
                                        echo '<span class="px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full">Offline</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!--  Pagination -->
            <div class="mt-6 flex justify-center">
                <nav class="inline-flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-full">← Prev</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="px-4 py-2 <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-700' ?> rounded-full"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-full">Next →</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>


        <script>
            document.addEventListener("DOMContentLoaded", () => {
                attachUserEventListeners();

                // ✅ Refresh Button Functionality
                document.getElementById("refreshBtn").addEventListener("click", () => {
                    document.getElementById("searchInput").value = "";
                    setTimeout(() => location.reload(), 100);
                });

                // ✅ Live Search for Users (Keeping Table Structure Identical)
                document.getElementById("searchInput").addEventListener("input", function() {
                    const query = this.value.trim().toLowerCase();
                    const tableBody = document.getElementById("userTableBody");

                    if (query.length === 0) {
                        fetchAllUsers();
                        return;
                    }

                    fetch(`search_users.php?query=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(users => {
                            tableBody.innerHTML = "";

                            if (!users || users.length === 0) {
                                tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-gray-500">No matching users found.</td></tr>`;
                                return;
                            }

                            // Build a regex for highlighting
                            const safeQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                            const regex = new RegExp(`(${safeQuery})`, "gi");

                            users.forEach(user => {
                                let profilePicture = user.profile_picture && user.profile_picture !== "null"
        ? user.profile_picture
        : "../images/users/dfuser.png"; // Use a default image if the profile picture is missing
                                
                                
                                const flag = user.flag ? `<img src="${user.flag}" class="w-6 h-4 rounded border border-gray-300 shadow-sm">` : "-";

                                // Highlight search matches
                                const highlightedName = user.name.replace(regex, `<span class="bg-yellow-300">$1</span>`);
                                const highlightedEmail = user.email.replace(regex, `<span class="bg-yellow-300">$1</span>`);
                                const highlightedCountry = user.country.replace(regex, `<span class="bg-yellow-300">$1</span>`);

                                const row = `
                <tr class="border-b hover:bg-gray-100 transition">
                    <td class="py-2 px-4 text-center">${user.id}</td>
                    <td class="py-2 px-4">${highlightedName}</td>
                     <!-- Profile Picture Column -->
                    <td class="py-2 px-4 text-center">
        
                             <img src="${profilePicture}" 
                 alt="Profile of ${user.name}" 
                 class="w-10 h-10 rounded-full border border-gray-300 shadow-sm">
                    </td>

                    <td class="py-2 px-4">${highlightedEmail}</td>
                    <td class="py-2 px-4 text-left">
                         <div class="flex items-center gap-3 px-3 py-2 w-48 mx-auto">
                            
                            <img src="${user.country_flag}"
                                alt="Flag of"
                                class="w-8 h-5 rounded border border-gray-400 shadow-md">
                            <span class="font-medium text-gray-800">${highlightedCountry}</span>
                        </div>
                    </td>
                    <td class="py-2 px-4 text-center">${user.total_score ?? "0"}</td>
                    <td class="py-2 px-4 text-center">
                        ${user.status === "active" 
                            ? '<span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full">Active</span>' 
                            : '<span class="px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full">Offline</span>'}
                    </td>
                </tr>
                `;
                                tableBody.insertAdjacentHTML("beforeend", row);
                            });
                        })
                        .catch(error => console.error("Error fetching search results:", error));
                });

                // ✅ Function to Attach Event Listeners (for future buttons like edit/delete)
                function attachUserEventListeners() {
                    console.log("User event listeners attached.");
                }
            });
        </script>

</body>

</html>
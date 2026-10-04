<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../User/login.php');
    exit();
}

require '../User/connect.php';
// Pagination settings
$limit = 10; // Categories per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // Ensure page is at least 1
$offset = ($page - 1) * $limit;
// Get total category count
$total_query = "SELECT COUNT(*) FROM category";
$total_result = pg_query($conn, $total_query);
$total_row = pg_fetch_row($total_result);
$total_categories = intval($total_row[0]);
// Fetch paginated categories
$query = "SELECT * FROM category ORDER BY id ASC LIMIT $limit OFFSET $offset";
$result = pg_query($conn, $query);
if (!$result) {
    die("Error fetching categories: " . pg_last_error($conn));
}
$categories = pg_fetch_all($result);
// Calculate total pages
$total_pages = ceil($total_categories / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="view_category.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <script src="view_category.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <h1 class="text-2xl font-bold m-3 text-left">🎯 Quizzie</h1>
            <ul class="mt-8">
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_dashboard.php" class="flex items-center"><span class="mr-2 text-lg">📊</span> Dashboard</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_leaderboard.php" class="flex items-center"><span class="mr-2 text-lg">🏆</span> Leaderboard</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_user.php" class="flex items-center"><span class="mr-2 text-lg">👥</span> Manage Players</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_category.php" class="flex items-center"><span class="mr-2 text-lg">📜</span> Manage Categories</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_question.php" class="flex items-center"><span class="mr-2 text-lg">❓</span> Manage Questions</a></li>
                <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_account.php" class="flex items-center"><span class="mr-2 text-lg">⚙️</span> Admin Account</a></li>
            </ul>
            <a href="l../User/logout.php" class="absolute bottom-0 left-0 p-4 flex items-center space-x-2 cursor-pointer">
                <i class="fas fa-sign-out-alt text-lg pl-4 py-2"></i>
                <span class="text-lg">Logout</span>
            </a>
        </div>
        <!-- Main Content -->
        <div class=" flex-1 h-screen bg-white pl-72 pt-6 pr-4 flex flex-col">
            <div class="flex justify-between mb-8">
                <h2 class="text-2xl font-bold">📜 Manage Categories</h2>
                <div class="flex items-center">
                    <span class="mr-2"><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : "admin"; ?></span>
                    <img src="../images/categories/<?php echo isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture']  : "" ?>" class="w-10 h-10 bg-pink-300 rounded-full" />
                </div>
            </div>
            <!-- Add Category Button (Left) & Search Bar + Refresh (Right) -->
            <div class="mb-6 flex items-center justify-between w-full">
                <!-- Add Category Button (Left) -->
                <button id="openAddModal"
                    class="bg-gradient-to-r from-blue-500 to-blue-700 text-white font-semibold px-6 py-3 rounded-lg shadow-md 
               hover:shadow-lg hover:from-blue-600 hover:to-blue-800 transition-all duration-300 ease-in-out 
               transform hover:scale-105 flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Add Category</span>
                </button>
                <!-- Search Bar & Refresh Button (Right-Aligned) -->
                <div class="flex items-center space-x-3">
                    <!-- Search Bar (Right-Aligned & Longer) -->
                    <div class="flex items-center bg-white/80 backdrop-blur-md px-4 py-3 rounded-full shadow-md 
                    w-64 md:w-80 lg:w-[320px] border border-gray-300 focus-within:ring-2 
                    focus-within:ring-blue-500 transition">
                        <i class="fas fa-search text-gray-500"></i>
                        <input type="text" id="searchInput" placeholder="Search categories..."
                            class="w-full bg-transparent text-gray-700 placeholder-gray-400 focus:outline-none text-sm ml-3">
                    </div>
                    <!-- Refresh Button (Next to Search Bar) -->
                    <button id="refreshBtn" class="text-blue-600 hover:text-blue-800 transition duration-300 ease-in-out transform hover:scale-110">
                        <i class="fas fa-sync-alt animate-spin-on-hover text-xl"></i>
                    </button>
                </div>
            </div>
            <!-- Categories Table -->
            <div class="bg-white rounded-lg shadow-md overflow-auto">
            <table class="min-w-full border-collapse rounded-lg shadow-md overflow-auto">
            <thead class="pl-0 sticky top-0 bg-blue-600 text-white uppercase text-sm">
                        <tr>
                            <th class="px-3 py-3 text-sm font-semibold uppercase text-left">ID</th>
                            <th class="px-3 py-3 text-sm font-semibold uppercase text-left">Name</th>
                            <th class="px-3 py-3 text-sm font-semibold uppercase text-left">Photo</th>
                            <th class="px-2 py-3 text-sm font-semibold uppercase text-left">API Category</th>
                            <th class="px-3 py-3 text-sm font-semibold uppercase text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
    
                        <?php if ($categories): ?>
                            <?php foreach ($categories as $index => $category): ?>
                                <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-gray-100 transition">
                                    <td class="pl-4 px-3 py-3 text-sm text-gray-700"><?= htmlspecialchars($category['id']) ?></td>
                                    <td class="px-3 py-3 text-sm text-gray-700 font-medium max-w-xs truncate"><?= htmlspecialchars($category['category_name']) ?></td>
                                    <td class="px-3 py-3 text-sm text-gray-700">
                                        <?php if (!empty($category['category_photo']) && file_exists("../images/categories/" . $category['category_photo'])): ?>
                                            <img src="../images/categories/<?= htmlspecialchars($category['category_photo']) ?>"
                                                class="w-12 h-12 rounded-lg border border-gray-300 shadow-sm object-cover">
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-2 py-3 text-sm text-left">
                                        <?php
                                        // Ensure the key exists
                                        if (isset($category['is_api']) && ($category['is_api'] === true || $category['is_api'] === 'true' || $category['is_api'] === 't' || $category['is_api'] === 1)): ?>
                                            <i class="fas fa-check-circle text-green-500 text-lg ml-2"></i> <!-- ✅ True -->
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-red-500 text-lg ml-2"></i> <!-- ❌ False -->
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-3 text-left">
                                        <button class="edit-btn text-blue-500 hover:text-blue-700 transition transform hover:scale-110 mr-3"
                                            data-id="<?= htmlspecialchars($category['id']) ?>"
                                            data-name="<?= htmlspecialchars($category['category_name']) ?>"
                                            data-photo="<?= htmlspecialchars($category['category_photo'] ?? '') ?>"
                                            data-api="<?= isset($category['is_api']) && ($category['is_api'] === 'true' || $category['is_api'] === 't' || $category['is_api'] == 1) ? 'true' : 'false' ?>"> <!-- If no photo, it will be empty -->
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="delete-btn text-red-500 hover:text-red-700 transition transform hover:scale-110 ml-3"
                                            data-id="<?= htmlspecialchars($category['id']) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No categories found.</td>
                            </tr>
                        <?php endif; ?>
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
        <!-- Add Category Modal -->
        <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl transform transition-transform scale-95">
                <h2 class="text-xl font-bold mb-4 text-blue-700">Add New Category</h2>
                <form id="addForm" enctype="multipart/form-data">
                    <div class="space-y-4">
                        <!-- Category Name -->
                        <input type="text" name="category_name" id="categoryName" placeholder="Category Name"
                            class="w-full p-2 border rounded shadow-sm focus:ring-2 focus:ring-blue-400" required>
                        <!-- File Input for Image Upload -->
                        <input type="file" name="category_photo" id="categoryPhoto" accept="image/*"
                            class="w-full p-2 border rounded shadow-sm focus:ring-2 focus:ring-blue-400">
                    </div>
                    <!-- Buttons -->
                    <div class="mt-6 flex justify-end space-x-4">
                        <button type="button" id="closeAddModal"
                            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Cancel</button>
                        <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Add</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Edit Category Modal -->
        <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl transform transition-transform scale-95">
                <h2 class="text-xl font-bold mb-4 text-blue-700">Edit Category</h2>
                <form id="editForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="editId">
                    <!-- Category Name (Disabled for API Categories) -->
                    <label class="block text-sm font-medium text-gray-700">Category Name</label>
                    <input type="text" name="category_name" id="editName"
                        class="w-full p-2 border rounded shadow-sm focus:ring-2 focus:ring-blue-400 disabled:bg-gray-200 disabled:text-gray-500 disabled:cursor-not-allowed"
                        required>
                    <p id="apiMessage" class="text-sm text-red-500 mt-1 hidden">This category is fetched from the API and can't be edited.</p>
                    <!-- Current Image Preview (Only Show If Available) -->
                    <div class="mt-3" id="photoContainer" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700">Current Image</label>
                        <img id="currentPhoto" class="w-24 h-24 rounded-lg border mt-2">

                        <!--  New: Remove Photo Checkbox -->
                        <div class="mt-2 flex items-center">
                            <input type="checkbox" id="removePhotoCheckbox" name="remove_photo" value="1" class="mr-2">
                            <label for="removePhotoCheckbox" class="text-sm text-gray-600">Remove Current Photo</label>
                        </div>
                        <!-- Upload New Image -->
                        <label class="block mt-4 text-sm font-medium text-gray-700">Upload New Image</label>
                        <input type="file" name="category_photo" id="editPhoto" accept="image/*"
                            class="w-full p-2 border rounded shadow-sm focus:ring-2 focus:ring-blue-400">
                        <!-- <p id="" class="text-gray-500 text-sm mt-2">No file selected</p> ✅ Add this -->

                        <!-- Buttons -->
                        <div class="mt-6 flex justify-end space-x-4">
                            <button type="button" id="closeEditModal" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Cancel</button>
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Save Changes</button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
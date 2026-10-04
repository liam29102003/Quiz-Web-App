<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../User/login.php');
    exit();
}

require '../User/connect.php';   // postgresql connection
require '../connect_redis.php'; //redis connection

require '../User/home_required.php'; 

?>
<?php
include "../connect_pgsql.php";

//line graph query graph
$sql = "WITH months AS (
                -- Generate the last 6 months excluding the current month
                SELECT generate_series(
                    date_trunc('month', NOW()) - INTERVAL '6 months',
                    date_trunc('month', NOW()) - INTERVAL '1 month',
                    INTERVAL '1 month'
                ) AS month
            ),
            category_total AS (
                -- Calculate total play count per category in the last 6 months
                SELECT 
                    category_id, 
                    SUM(play_count) AS total_play_count
                FROM (
                    SELECT 
                        category_id, 
                        date_trunc('month', date) AS month,  
                        COUNT(*) AS play_count
                    FROM user_history
                    WHERE date >= date_trunc('month', NOW()) - INTERVAL '6 months'
                    AND date < date_trunc('month', NOW())  
                    GROUP BY category_id, month
                ) sub
                GROUP BY category_id
                ORDER BY total_play_count DESC
                LIMIT 5 -- Get the top 5 categories
            ),
            category_months AS (
                -- Create all possible (top 5 category, month) combinations
                SELECT ct.category_id, c.category_name, m.month
                FROM category_total ct
                JOIN category c ON ct.category_id = c.id
                CROSS JOIN months m
            ),
            filtered_history AS (
                -- Aggregate actual play count for the selected categories
                SELECT 
                    category_id, 
                    date_trunc('month', date) AS month,  
                    COUNT(*) AS play_count
                FROM user_history
                WHERE date >= date_trunc('month', NOW()) - INTERVAL '6 months'
                AND date < date_trunc('month', NOW())  
                GROUP BY category_id, month
            )
            -- Join (category, month) pairs with actual play counts
            SELECT 
                cm.category_name, 
                TO_CHAR(cm.month, 'YYYY Mon') AS month,  
                COALESCE(fh.play_count, 0) AS play_count -- Assign 0 if no data
            FROM category_months cm
            LEFT JOIN filtered_history fh 
            ON cm.category_id = fh.category_id AND cm.month = fh.month
            ORDER BY cm.category_name, cm.month ASC;
        ";

$stmt = $pgsql->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
// echo "<pre>";
// print_r($data);
// echo "</pre>";

function linearRegression($x, $y) {
    $n = count($x);
    $sumX = array_sum($x);
    $sumY = array_sum($y);
    $sumXY = 0;
    $sumX2 = 0;
    
    foreach ($x as $i => $xi) {
        $sumXY += $xi * $y[$i];
        $sumX2 += $xi * $xi;
    }
    
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;
    return ['slope' => $slope, 'intercept' => $intercept];
}

$start_month = $data[0]["month"];
$start_date = DateTime::createFromFormat("Y M", $start_month);
$labels = [];
for ($i = 0; $i < 12; $i++) {
    $labels[] = $start_date->format("Y M"); // Add current month
    $start_date->modify("+1 month"); // Move to next month
}
// echo "<pre>";
// print_r($labels);
// echo "</pre>";

$playedData = [];
foreach ($data as $record) {
    $category = $record["category_name"];
    $playCount = $record["play_count"];

    // Initialize array for category if not exists
    if (!isset($playedData[$category])) {
        $playedData[$category] = [];
    }

    // Append play count
    $playedData[$category][] = $playCount;
}

// echo "<pre>";
// print_r($playedData);
// echo "</pre>";

$colors = [];
$colorList = ["blue", "red", "green", "purple", "orange"];
$dataLength = count($data);
for($i=0; $i<$dataLength; $i=$i+6){
    $category_name = $data[$i]["category_name"];
    $colors[$category_name] = $colorList[$i/6];
}
// echo "<pre>";
// print_r($colors);
// echo "</pre>";

$datasets = [];
$monthsIndex = [1, 2, 3, 4, 5, 6];
$futureIndexes = [7, 8, 9, 10, 11, 12];

foreach ($playedData as $category => $actualData) {
    $regression = linearRegression($monthsIndex, $actualData);
    $futureSales = [
        $actualData[5],
        ...array_map(function($i) use ($regression) {
            return $regression['slope'] * $i + $regression['intercept'];
        }, $futureIndexes)
    ];
    
    $datasets[] = [
        'label' => $category,
        'data' => array_merge($actualData, array_slice($futureSales, 1)),
        'borderColor' => $colors[$category],
        'borderWidth' => 1.5,
        'pointRadius' => 3,
        'pointBackgroundColor' => $colors[$category],
        'pointHoverRadius' => 6,
        'fill' => false
    ];
}

//pie chart query part
$pie_sql = "WITH months AS (
    SELECT generate_series(
        date_trunc('month', NOW()) - INTERVAL '6 months',
        date_trunc('month', NOW()) - INTERVAL '1 month',
        INTERVAL '1 month'
    ) AS month
)
SELECT 
    c.category_name, 
    COUNT(uh.id) AS matches
FROM category c
LEFT JOIN user_history uh 
    ON c.id = uh.category_id 
    AND uh.date >= date_trunc('month', NOW()) - INTERVAL '6 months'
    AND uh.date < date_trunc('month', NOW())
GROUP BY c.category_name
ORDER BY matches DESC;
";

$pie_stmt = $pgsql->query($pie_sql);
$pie_raw_data = $pie_stmt->fetchAll(PDO::FETCH_ASSOC);
// echo "<pre>";
// print_r($pie_raw_data);
// echo "</pre>";

$pie_data = [];
foreach ($pie_raw_data as $item) {
$pie_data[$item["category_name"]] = $item["matches"];
}
// echo "<pre>";
// print_r($pie_data);
// echo "</pre>";

$total = array_sum($pie_data); // Calculate total value
$filtered = []; // Categories >= 5%
$others = []; // Categories < 5%
$otherTotal = 0; // Total value of "Others"

foreach ($pie_data as $label => $value) {
$percentage = ($value / $total) * 100;
if ($percentage >= 5) {
$filtered[$label] = $value;
} else {
$others[$label] = $value;
$otherTotal += $value;
}
}

// Add "Others" if there are categories < 5%
if ($otherTotal > 0) {
$filtered['Others'] = $otherTotal;
}

// Prepare data for JavaScript
$chartLabels = [];
$chartData = [];
$othersDetails = [];

foreach ($filtered as $label => $value) {
if ($label === 'Others') {
$chartLabels[] = "Others (" . round(($otherTotal / $total) * 100, 1) . "%) " . $otherTotal;
foreach ($others as $name => $val) {
$othersDetails[] = "$name (" . round(($val / $total) * 100, 1) . "%) " . $val . " matches";
}
} else {
$chartLabels[] = "$label (" . round(($value / $total) * 100, 1) . "%) " . $value . "matches";
}
$chartData[] = $value;
}
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
    let eventSource;
    let reconnectDelay = 1000; // Start with 1 second reconnect delay

    function startSSE() {
        eventSource = new EventSource("../User/active_user_sse.php");

        eventSource.onopen = function () {
            console.log("✅ SSE Connection Established");
            reconnectDelay = 1000; // Reset delay on successful connection
        };

        eventSource.onmessage = function (event) {
            try {
                const data = JSON.parse(event.data);
                const activeUsers = data.active_users ?? 0;
                updateActiveUsers(activeUsers);
            } catch (error) {
                console.error("❌ JSON Parsing Error:", error);
            }
        };

        eventSource.onerror = function () {
            console.error("⚠️ SSE Connection Error. Reconnecting...");
            eventSource.close(); // Close old connection
            setTimeout(startSSE, reconnectDelay); // Reconnect after delay
            reconnectDelay = Math.min(reconnectDelay * 2, 30000); // Exponential backoff, max 30 sec
        };
    }

    function updateActiveUsers(activeUsers) {
        const activeUsersElement = document.getElementById("activeUsers");

        if (activeUsersElement) {
            if (activeUsersElement.innerText !== String(activeUsers)) {
                activeUsersElement.innerText = activeUsers;
            }
        } else {
            console.error('⚠️ Element with id "activeUsers" not found.');
        }
    }

    startSSE();

    // Close SSE connection when user leaves the page
    window.addEventListener("beforeunload", function () {
        if (eventSource) {
            eventSource.close();
        }
    });
});

</script>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
	<title>🎯Quizzie</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
               extend:{
                screens: {
                'sm': '300px',   
                'md': '876px' 
                },
                fontFamily: {
                    inter: ['Inter', 'sans-serif'],
                },
                
               }
          }
              
            
        }
        

    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 	
    <style>
        body {
           font-family: 'Itim', cursive;
       }
       
       /* Hide rows beyond the 10th one */
       tr:nth-child(n+11) {
            display: none;
        }

        .container { display: block; }
        .container div { margin: 10px 0; }
        #myChart1Div { 
            
            display: flex;
            flex-direction:row;
            justify-content: start;
            
        } 
        #myChart1 {
            padding: 10px;
            max-width: 600px;
            min-height: 300px;
            max-height: 300px;
            background: white;
            border: 1px solid #ddd;
            margin-top: 10px;
        }

        .chart-container {
            width: auto;
            height: auto;
            max-width: 300px;
            min-height: 580px;
            padding: 8px;
        }

        /* pie chart */
        #pieChart {
            width: 100% !important;
            height: auto !important;
        }

        .legend-container {
            margin-top: 20px;
            text-align: left;
        }

        .legend-item {
            display: block;
            margin: 5px 0;
            font-size: 12px;
            white-space: nowrap;
        }
      
    </style>
   </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" /> 	
     <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

</head>

<body>
    <div class="w-full flex h-screen bg-gray-100 overflow-x-auto">
        <!-- Sidebar -->
        <div class="w-64 bg-[#6859DE] text-white p-3 sticky top-0 h-screen md:block sm:hidden" >
            <h1 class="text-2xl font-bold m-3">🎯 Quizzie</h1>
          <ul class="mt-8">
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_dashboard.php" class="flex items-center"><span class="mr-2 text-lg">📊</span> Dashboard</a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_leaderboard.php" class="flex items-center"><span class="mr-2 text-lg">🏆</span> Leaderboard</a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_user.php" class="flex items-center"><span class="mr-2 text-lg">👥</span> Manage Players</a></li>            
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_category.php" class="flex items-center"><span class="mr-2 text-lg">📜</span> Manage Categories</a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_question.php" class="flex items-center"><span class="mr-2 text-lg">❓</span> Manage Questions</a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_account.php" class="flex items-center"><span class="mr-2 text-lg">⚙️</span> Admin account</a></li>    
          </ul>
           <a href="../User/logout.php" class="absolute bottom-0 left-0 p-4 flex items-center space-x-2 cursor-pointer" >
                <i class="fas fa-sign-out-alt text-lg pl-4 py-2"></i>
                <span class="text-lg">Logout</span>
            </a>
        </div>

        <div class="w-fit bg-[#6859DE] text-white p-3 sticky top-0 h-screen md:hidden sm:block" >
            <h1 class="text-lg font-bold py-2 w-10 flex flex-wrap">🎯Quizzie</h1>
          <ul class="mt-8 text-lg">
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_dashboard.php" class="flex items-center"><span class="mr-2 text-lg"> 📊</span></a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_leaderboard.php" class="flex items-center"><span class="mr-2 text-lg"> 🏆</span></a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_user.php" class="flex items-center"><span class="mr-2 text-lg"> 👥</span></a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_category.php" class="flex items-center"><span class="mr-2 text-lg"> 📜</span></a></li>
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="view_question.php" class="flex items-center"><span class="mr-2 text-lg">❓</span> </a></li>            
            <li class="mb-4 px-4 py-2 hover:bg-purple-500 rounded"><a href="admin_account.php" class="flex items-center"><span class="mr-2 text-lg"> ⚙️</span></a></li>            
          </ul>
          <a href="../User/logout.php" class="absolute bottom-0 left-0 p-4 flex items-center space-x-2 cursor-pointer " id="logout">
                <i class="fas fa-sign-out-alt text-lg px-4 py-2"></i>                
          </a>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
          <div class="flex justify-between mb-8">
            <h2 class="text-2xl font-bold">📊 Dashboard</h2>
            <div class="flex items-center">
                    <span class="mr-2"><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : "admin"; ?></span>
                    <img src="uploads/<?php echo isset($_SESSION['admin_name']) ? $_SESSION['profile_picture']  : "" ?>" class="w-10 h-10 bg-pink-300 rounded-full" />
                </div>
          </div>
      
          <div class="flex flex-row flex-wrap justify-between md:gap-4 sm:gap-8 basis-1/4 mb-8">
            <div class="bg-[#E1DDF5] p-4 rounded shadow flex-1 flex flex-row justify-between">
              <div>
                <p class="font-semibold">Registered Players</p>
                <p class="text-blue-500 font-bold"><?php echo $total_users; ?></p>
              </div>
              <img src="../images/icons/players.png" class="w-12 h-12 md:block sm:hidden">
            </div>

            <div class="bg-[#E1DDF5] p-4 rounded shadow flex-1 flex flex-row justify-between">
              <div>
                <p class="font-semibold">Active Players</p>
                <p class="text-blue-500 font-bold"  id="activeUsers">0</p>
              </div>
              <img src="../images/icons/active_player.png" class="w-12 h-12 md:block sm:hidden">
            </div>

            <div class="bg-[#E1DDF5] p-4 rounded shadow flex-1 flex flex-row justify-between">
              <div>
                <p class="font-semibold">Categories</p>
                <p class="text-blue-500 font-bold"><?php  echo $total_categories; ?></p>
              </div>
              <img src="../images/icons/category.png" class="w-12 h-12 md:block sm:hidden">
            </div>

            <div class="bg-[#E1DDF5] p-4 rounded shadow flex-1 flex flex-row justify-between">
              <div>
                <p class="font-semibold">Questions</p>
                <p class="text-blue-500 font-bold"><?php  echo $total_questions; ?></p>
              </div>
              <img src="../images/icons/question.png" class="w-12 h-12 md:block sm:hidden">
            </div>
          </div>
      
          <div class="mb-5 flex flex-row flex-wrap gap-5 justify-start sm:justify-center align-items-center md:w-full sm:w-[500px]">
            <div class="chart-container bg-white rounded shadow mb-3">
                <canvas id="pieChart"></canvas>
            </div>

            <div id="myChart1Div" class="sm:w-[450px] md:w-[600px]">
              <canvas id="myChart1"></canvas>
            </div>
          </div>
  
        </div>
      </div>

     
</body>
<script>
        // PHP-generated data
        const labels = <?= json_encode($labels) ?>;
        const datasets = <?= json_encode($datasets, JSON_NUMERIC_CHECK) ?>;

        // Add segment configuration for dashed lines
        datasets.forEach(dataset => {
            dataset.segment = {
                borderDash: ctx => ctx.p0DataIndex >= 5 ? [5, 5] : []
            };
        });

        // Create chart
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('myChart1').getContext('2d'), {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'nearest', intersect: true },
                    scales: { y: { beginAtZero: true } },
                    plugins: {
                        tooltip: {
                            bodyFont: { size: 14, weight: 'bold' },
                            titleFont: { size: 16, weight: 'bold' }
                        }
                    }
                }
            });
        });
    </script>


    <!--pie chart -->
    <script>
        // Data received from PHP
        const chartData = {
            labels: <?= json_encode($chartLabels) ?>, // Original Labels from PHP
            data: <?= json_encode($chartData) ?>,     // Category Values
            othersDetails: <?= json_encode($othersDetails) ?>, // Details of "Others"
            colors: [ // Color Palette
                '#3498db', '#2ecc71', '#9b59b6', '#f39c12', '#e74c3c',
                '#95a5a6', '#1abc9c', '#34495e', '#f1c40f', '#7f8c8d'
            ]
        };

        const totalMatches = chartData.data.reduce((sum, val) => sum + val, 0);

        // ✅ Format Labels as "Category (50%) 1000 matches"
        const formattedLabels = chartData.labels.map((label, i) => {
            let matches = chartData.data[i];
            let percentage = ((matches / totalMatches) * 100).toFixed(1);
            return `${label.split(" ")[0]} (${percentage}%) ${matches} match`;
        });

        const ctx = document.getElementById('pieChart').getContext('2d');

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: formattedLabels,
                datasets: [{
                    data: chartData.data,
                    backgroundColor: chartData.colors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let index = context.dataIndex;
                                let category = chartData.labels[index].split(" ")[0]; // Extract category name
                                let matches = chartData.data[index];
                                let percentage = ((matches / totalMatches) * 100).toFixed(1);
                                return null;
                            },
                            afterLabel: function(context) {
                                if (context.label.includes('Others')) {
                                    return chartData.othersDetails.join('\n');
                                }
                            }
                        }
                    },
                    legend: {
                        position: 'bottom', // ✅ Legend at Bottom
                        align: 'start',  // ✅ Left-Aligned Text
                        labels: {
                            padding: 20,
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                return data.labels.map((label, i) => ({
                                    text: label,
                                    fillStyle: data.datasets[0].backgroundColor[i], // Color of the legend box
                                    fontColor: '#6c757d', // Muted text color
                                    hidden: false,
                                    lineWidth: 0,
                                    strokeStyle: 'rgba(0,0,0,0)'
                                }));
                            }
                        }
                    }
                }
            }
        });
    </script>

</html>
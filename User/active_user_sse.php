<?php
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");
header("Access-Control-Allow-Origin: *"); // Allow CORS for debugging
header("X-Accel-Buffering: no"); // Prevent buffering in Nginx

// Include Redis
require '../predis/autoload.php';
Predis\Autoloader::register();

try {
    $redis = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => '127.0.0.1',
        'port'   => 6379
    ]);
} catch (Exception $e) {
    echo "data: " . json_encode(["error" => "Redis connection failed: " . $e->getMessage()]) . "\n\n";
    flush();
    exit();
}

set_time_limit(0); // Remove execution limit

$startTime = time(); // Track script start time
$maxDuration = 110; // Stop before hitting 120s limit

while (true) {
    try {
        // Get the number of active users, default to 0 if null
        $totalActiveUsers = $redis->scard("active_users") ?? 0;

        // Ensure it sends 0 if there are no users
        $totalActiveUsers = $totalActiveUsers ?: 0;

        // Send event data to client
        echo "data: " . json_encode(["active_users" => $totalActiveUsers]) . "\n\n";
        ob_flush();
        flush();
        
        // Check if client closed connection
        if (connection_aborted()) {
            break;
        }

    } catch (Exception $e) {
        echo "data: " . json_encode(["error" => "Error fetching active users: " . $e->getMessage()]) . "\n\n";
        flush();
        break;
    }

    sleep(5); // Update every 5 seconds

    // Stop execution before hitting 120s limit
    if (time() - $startTime > $maxDuration) {
        break;
    }
}

?>


<?php include "../connect_redis.php"; ?>

<?php
$match_data = $redis->lrange("match_1", 0, -1);
$match_data = array_map('json_decode', $match_data, array_fill(0, count($match_data), true));

// Print all answers
echo "<pre>";
print_r($match_data);
echo $redis->get("score");
// $redis->del(keyOrKeys: "match_1");
// $redis->del("score");


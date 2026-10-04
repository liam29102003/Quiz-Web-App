<?php  
require 'predis/autoload.php';

try{
    Predis\Autoloader::register();
    $redis = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => '127.0.0.1',  // Change if your Redis is on another server
        'port'   => 6379
    ]);
    $redis->select(2);
    
}
catch (Exception $e)
{
    die($e->getMessage());
} 




?>

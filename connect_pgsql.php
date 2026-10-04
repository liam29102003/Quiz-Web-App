<?php
$host = "localhost";
$dbname = "QuizDB";
$user = "postgres";
$password = "kymt@311498";

try{

    $pgsql = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pgsql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

}catch (PDOException $e) {

    echo "Connection failed: " . $e->getMessage();

}
?>
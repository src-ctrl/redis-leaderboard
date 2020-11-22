<?php
echo 'redis-leaderboard';

$dsn = "mysql:host=db;dbname=redis-leaderboard";
$user = "redis-leaderboard-user";
$passwd = "password";

$pdo = new PDO($dsn, $user, $passwd);
$stm = $pdo->query("SELECT * FROM places");
$rows = $stm->fetchAll(PDO::FETCH_NUM);

print_r($rows);
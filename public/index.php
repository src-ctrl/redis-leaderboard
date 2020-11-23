<?php
require_once '../vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load('../.env');

$client = new Predis\Client('tcp://redis:6379');
$client->set('foo', 'bar');
$value = $client->get('foo');
echo $value;

try{
    $dbh = new pdo('mysql:host=' . $_ENV['DB_HOST']
                 . ':' . $_ENV['DB_PORT'] . ';'
                 . 'dbname=' . $_ENV['DB_DATABASE'],
                    $_ENV['DB_USERNAME'],
                    $_ENV['DB_PASSWORD'],
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
}
catch(PDOException $ex){
    die(json_encode(array('outcome' => 'false', 'message' => 'Unable to connect to DB.')));
}

$stm = $dbh->query("SELECT * FROM places");
$rows = $stm->fetchAll(PDO::FETCH_NUM);

echo json_encode($rows);
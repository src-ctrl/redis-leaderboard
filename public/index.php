<?php
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Predis\Client;

require __DIR__ . '/../vendor/autoload.php';

// Create Container using PHP-DI
$container = new Container();
$container->set('redis', function () {
    return new Client('tcp://redis:6379');
});
$container->set('faker', function () {
    return Faker\Factory::create();
});

// Set container to create App with on AppFactory
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {

    $topPlayers = $this->get('redis')
        ->zRevRange('leaderboard', 0, 99, ['withscores' => true]);

    foreach($topPlayers as $id => $score) {
        $players[] = [
            'id' => $id,
            'details' => $this->get('redis')
                ->hgetall('player:' . $id),
            'score' => $score,
        ];
    }

    $response->getBody()->write(json_encode($players));
    return $response
          ->withHeader('Content-Type', 'application/json');
});

$app->post('/leaderboard/score-all-players', function (Request $request, Response $response, $args) {
    for($i=1; $i <= 100000; $i++) {
        $this->get('redis')
            ->zAdd('leaderboard', $this->get('faker')->numberBetween(0, 1000000), $i);
    }
    return $response;
});

$app->post('/player/make', function (Request $request, Response $response, $args) {
    for($i=1; $i <= 100000; $i++) {
        $this->get('redis')->hmset('player:' . $i, [
            'username' => $this->get('faker')->userName,
            'color' => $this->get('faker')->hexcolor,
            'lastSeen' => $this->get('faker')
                ->dateTimeThisMonth()
                ->format('Y-m-d H:i:s'),
        ]);
    }
    return $response;
});

$app->get('/player/{id}', function (Request $request, Response $response, $args) {
    $data = $this->get('redis')
        ->hgetall('player:' . $args['id']);

    $data['leaderboard'] = [
        'total' => $this->get('redis')->zCard('leaderboard'),
        'score' => $this->get('redis')->zScore('leaderboard', $args['id']),
        'rank' => $this->get('redis')->zRevRank('leaderboard', $args['id']) + 1,
    ];

    $response->getBody()->write(json_encode($data));
    return $response
          ->withHeader('Content-Type', 'application/json');
});

$app->run();
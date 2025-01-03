<?php


/* SÓ CONSEGUI FAZER ISSO, O DESAFIO ESTAVA ACIMA DO MEU NÍVEL DE CONHECIMENTO 

é assim que se pesquisa uma palavra: http://localhost/dictionary-challenge/public/index.php?word=test 
*/

require_once '../src/Database.php';
require_once '../src/UserController.php';
require_once '../src/WordController.php';
require_once '../src/AuthMiddleware.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$data = json_decode(file_get_contents('php://input'), true);

$db = (new Database())->getConnection();

// If there is no specific API request, show the form
if ($uri === '/' && $method === 'GET') {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Search</title>
</head>
<body>
    <h1>Search for a Word</h1>
    <form method="GET" action="index.php">
        <input type="text" name="word" placeholder="Enter a word" required>
        <button type="submit">Search</button>
    </form>
    <div id="result"></div>
</body>
</html>';
    exit;
}

// Handle API requests
if (isset($_GET['word'])) {
    $word = $_GET['word'];
    $controller = new WordController($db);
    $controller->getWord($word);
} elseif (preg_match('/\/login$/', $uri) && $method === 'POST') {
    $controller = new UserController($db);
    $controller->login($data);
} elseif (preg_match('/\/register$/', $uri) && $method === 'POST') {
    $controller = new UserController($db);
    $controller->register($data);
} elseif (preg_match('/\/word\/(\w+)$/', $uri, $matches)) {
    $userId = AuthMiddleware::authenticate($db);
    $controller = new WordController($db);

    if ($method === 'GET') {
        $controller->getWord($matches[1]);
    } elseif ($method === 'POST') {
        $controller->addToHistory($userId, $matches[1]);
    }
} elseif (preg_match('/\/history$/', $uri) && $method === 'GET') {
    $userId = AuthMiddleware::authenticate($db);
    $controller = new WordController($db);
    $controller->getHistory($userId);
} elseif (preg_match('/\/favorites$/', $uri)) {
    $userId = AuthMiddleware::authenticate($db);
    $controller = new WordController($db);

    if ($method === 'POST') {
        $controller->manageFavorites($userId, $data['word'], 'add');
    } elseif ($method === 'DELETE') {
        $controller->manageFavorites($userId, $data['word'], 'remove');
    } elseif ($method === 'GET') {
        $controller->getFavorites($userId);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
}

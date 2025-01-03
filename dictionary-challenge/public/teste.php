<?php
// Conexão com o banco de dados
$host = 'localhost';
$db = 'dictionary_app';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Funções auxiliares
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function authenticate($pdo) {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        jsonResponse(['error' => 'Token não fornecido.'], 401);
    }

    $token = $headers['Authorization'];
    $stmt = $pdo->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(['error' => 'Token inválido.'], 401);
    }

    return $user['id'];
}

// Rotas
$requestMethod = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

switch ($endpoint) {
    case 'register':
        if ($requestMethod === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            if (!$username || !$password) {
                jsonResponse(['error' => 'Usuário e senha são obrigatórios.'], 400);
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashedPassword]);

            jsonResponse(['message' => 'Usuário registrado com sucesso.']);
        }
        break;

    case 'login':
        if ($requestMethod === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            if (!$username || !$password) {
                jsonResponse(['error' => 'Usuário e senha são obrigatórios.'], 400);
            }

            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                jsonResponse(['error' => 'Usuário ou senha inválidos.'], 401);
            }

            $token = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("UPDATE users SET token = ? WHERE id = ?");
            $stmt->execute([$token, $user['id']]);

            jsonResponse(['token' => $token]);
        }
        break;

    case 'dictionary':
        if ($requestMethod === 'GET') {
            $userId = authenticate($pdo); // Verifica o token

            $word = $_GET['word'] ?? '';
            if (!$word) {
                jsonResponse(['error' => 'A palavra é obrigatória.'], 400);
            }

            $apiUrl = "https://api.dictionaryapi.dev/api/v2/entries/en/$word";
            $response = file_get_contents($apiUrl);

            if ($response === false) {
                jsonResponse(['error' => 'Erro ao acessar a API do dicionário.'], 500);
            }

            $data = json_decode($response, true);

            // Salvar no histórico
            $stmt = $pdo->prepare("INSERT INTO history (user_id, word) VALUES (?, ?)");
            $stmt->execute([$userId, $word]);

            jsonResponse($data);
        }
        break;

    case 'history':
        if ($requestMethod === 'GET') {
            $userId = authenticate($pdo);

            $stmt = $pdo->prepare("SELECT word, created_at FROM history WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse($history);
        }
        break;

    case 'favorites':
        $userId = authenticate($pdo);

        if ($requestMethod === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $word = $data['word'] ?? '';

            if (!$word) {
                jsonResponse(['error' => 'A palavra é obrigatória.'], 400);
            }

            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, word) VALUES (?, ?)");
            $stmt->execute([$userId, $word]);

            jsonResponse(['message' => 'Palavra adicionada aos favoritos.']);
        } elseif ($requestMethod === 'DELETE') {
            $word = $_GET['word'] ?? '';

            if (!$word) {
                jsonResponse(['error' => 'A palavra é obrigatória.'], 400);
            }

            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND word = ?");
            $stmt->execute([$userId, $word]);

            jsonResponse(['message' => 'Palavra removida dos favoritos.']);
        } elseif ($requestMethod === 'GET') {
            $stmt = $pdo->prepare("SELECT word FROM favorites WHERE user_id = ?");
            $stmt->execute([$userId]);
            $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse($favorites);
        }
        break;

    default:
        jsonResponse(['error' => 'Endpoint não encontrado.'], 404);
}

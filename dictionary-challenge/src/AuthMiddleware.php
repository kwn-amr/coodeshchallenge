<?php

class AuthMiddleware {
    public static function authenticate($conn) {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authorization header not found']);
            exit;
        }

        $token = $headers['Authorization'];
        $stmt = $conn->prepare("SELECT id FROM users WHERE MD5(CONCAT(username, password)) = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            exit;
        }

        return $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    }
}

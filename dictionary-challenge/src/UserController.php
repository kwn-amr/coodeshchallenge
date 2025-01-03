<?php

class UserController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($data) {
        $username = $data['username'];
        $password = $data['password'];

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $token = md5($username . $user['password']);
                echo json_encode(['token' => $token]);
                return;
            }
        }

        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
    }

    public function register($data) {
        $username = $data['username'];
        $password = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = $this->conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);

        if ($stmt->execute()) {
            echo json_encode(['message' => 'User registered successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to register user']);
        }
    }
}

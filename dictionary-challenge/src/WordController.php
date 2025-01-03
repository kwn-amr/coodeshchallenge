<?php

class WordController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getWord($word) {
        $apiUrl = "https://api.dictionaryapi.dev/api/v2/entries/en/{$word}";
        $response = file_get_contents($apiUrl);
        echo $response;
    }

    public function addToHistory($userId, $word) {
        $stmt = $this->conn->prepare("INSERT INTO word_history (user_id, word) VALUES (:user_id, :word)");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':word', $word);

        if ($stmt->execute()) {
            echo json_encode(['message' => 'Word added to history']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to add to history']);
        }
    }

    public function getHistory($userId) {
        $stmt = $this->conn->prepare("SELECT word, viewed_at FROM word_history WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function manageFavorites($userId, $word, $action) {
        if ($action === 'add') {
            $stmt = $this->conn->prepare("INSERT INTO favorites (user_id, word) VALUES (:user_id, :word)");
        } else {
            $stmt = $this->conn->prepare("DELETE FROM favorites WHERE user_id = :user_id AND word = :word");
        }

        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':word', $word);

        if ($stmt->execute()) {
            echo json_encode(['message' => 'Action completed']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to complete action']);
        }
    }

    public function getFavorites($userId) {
        $stmt = $this->conn->prepare("SELECT word, added_at FROM favorites WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

<?php
require_once 'database.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS meeting_feedbacks (
        id SERIAL PRIMARY KEY,
        meeting_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        rating INTEGER,
        feedback_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE(meeting_id, user_id)
    )");
    echo "Table meeting_feedbacks created successfully.";
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}
?>

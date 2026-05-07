<?php
// setup_db.php
require_once 'database.php';

try {
    // Drop existing tables with CASCADE for PostgreSQL
    $pdo->exec("DROP TABLE IF EXISTS attendances CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS meeting_participants CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS meetings CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS users CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS rooms CASCADE");

    // 1. Create Users Table
    $pdo->exec("CREATE TABLE users (
        id SERIAL PRIMARY KEY,
        name TEXT NOT NULL,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user'
    )");

    // 2. Create Meetings Table
    $pdo->exec("CREATE TABLE meetings (
        id SERIAL PRIMARY KEY,
        title TEXT NOT NULL,
        room TEXT NOT NULL,
        scheduled_time TIMESTAMP NOT NULL,
        end_time TIMESTAMP NOT NULL,
        late_tolerance INTEGER NOT NULL DEFAULT 15,
        token TEXT NOT NULL UNIQUE,
        created_by INTEGER NOT NULL,
        FOREIGN KEY(created_by) REFERENCES users(id)
    )");

    // 3. Create Meeting Participants
    $pdo->exec("CREATE TABLE meeting_participants (
        meeting_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        PRIMARY KEY (meeting_id, user_id),
        FOREIGN KEY(meeting_id) REFERENCES meetings(id),
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    // 4. Create Attendances Table
    $pdo->exec("CREATE TABLE attendances (
        id SERIAL PRIMARY KEY,
        meeting_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        check_in_time TIMESTAMP NOT NULL,
        status TEXT NOT NULL,
        FOREIGN KEY(meeting_id) REFERENCES meetings(id),
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    // 5. Create rooms table
    $pdo->exec("CREATE TABLE rooms (
        id SERIAL PRIMARY KEY,
        name TEXT NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert Dummy Users (without specific IDs to avoid sequence issues, or just use them)
    $pass1 = password_hash('admin', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (name, username, password, role) VALUES ('Information Technology', 'admin', '$pass1', 'admin')");
    
    $pass2 = password_hash('password123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (name, username, password, role) VALUES ('John Doe', 'johndoe', '$pass2', 'user')");
    $pdo->exec("INSERT INTO users (name, username, password, role) VALUES ('Jane Smith', 'janesmith', '$pass2', 'user')");

    // Insert default rooms
    $default_rooms = ["Ruang Meeting Lt 7 besar", "Ruang Meeting Lt 7 Kecil", "Ruang Mezanine"];
    $stmt_room = $pdo->prepare("INSERT INTO rooms (name) VALUES (?) ON CONFLICT (name) DO NOTHING");
    foreach ($default_rooms as $rname) {
        $stmt_room->execute([$rname]);
    }

    echo "Database setup successfully for PostgreSQL!";
} catch (PDOException $e) {
    die("Setup Database Gagal: " . $e->getMessage());
}
?>

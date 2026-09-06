<?php

$host = "localhost";
$user = "root";
$password = "fycs";
$database = "skillbridge";

// Connect to MySQL server with password 'fycs'
$conn = @new mysqli($host, $user, $password, $database);

// If access denied (e.g. running on default Laragon with blank password), fallback to ""
if ($conn->connect_errno === 1045) {
    $password = "";
    $conn = @new mysqli($host, $user, $password, $database);
}

// If database does not exist (1049), create it automatically
if ($conn->connect_errno === 1049) {
    $initConn = @new mysqli($host, $user, $password);
    if (!$initConn->connect_error) {
        $initConn->query("CREATE DATABASE IF NOT EXISTS `$database`");
        $initConn->close();
        $conn = @new mysqli($host, $user, $password, $database);
    }
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Ensure users table exists with proper schema
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('student','industry','academician','institution') NOT NULL DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
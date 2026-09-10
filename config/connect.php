<?php

// Set timezone
date_default_timezone_set("Asia/Manila");

// Define database connection variables
$dbhost = "localhost";
$dbname = "kaban_helpdesk_db";
$dbusername = "root";
$dbpassword = "";


try {
    // Initialize PDO with error handling
    $pdo = new PDO(
        "mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4",
        $dbusername,
        $dbpassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable exceptions for errors
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle connection error
    error_log("Database connection failed: " . $e->getMessage());
    die("Could not connect to the database");
}

// Functions to be used for database

// manage function for INSERT, UPDATE, DELETE
// Usage:
// manage("INSERT INTO table_name (field1, field2) VALUES (?, ?)", array(value1, value2)); 
// manage("UPDATE table_name SET field1 = ? WHERE field2 = ?", array(value1, value2)); 
// manage("DELETE FROM table_name WHERE field1 = ?", array(value1)); 
function manage($statement, $values){
    global $pdo;

    try {
        $stmt = $pdo->prepare($statement);
        $stmt->execute($values);
        return $stmt->rowCount(); // Return the number of affected rows
    } catch (PDOException $e) {
        // Handle SQL errors
        error_log("DB Error in manage(): ". $e->getMessage());
        return false;
    }
}

// retrieve function for SELECT queries
// Usage:
// retrieve("SELECT * FROM table_name WHERE field1 = ?", array(value1));
// retrieve("SELECT COUNT(*) AS count FROM table_name WHERE field1 = ?", array(value1));
function retrieve($statement, $values){
    global $pdo;

    try {
        $stmt = $pdo->prepare($statement);
        $stmt->execute($values);
        return $stmt->fetchAll(); // Return all results as an associative array
    } catch (PDOException $e) {
        // Handle SQL errors
        error_log("DB Error in retrieve(): ". $e->getMessage());
        return false;
    }
}

?>

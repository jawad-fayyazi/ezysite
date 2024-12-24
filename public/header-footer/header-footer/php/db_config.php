<?php
// Database connection information
$host = "localhost"; // Database host (usually localhost)
$dbname = "web-builder_db"; // The name of your database
$username = "root"; // Your database username
$password = ""; // Your database password




// Create a connection to the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connected successfully"; // Uncomment to test the connection
} catch (PDOException $e) {
    // If connection fails, show an error message
    echo "Connection failed: " . $e->getMessage();
}
?>
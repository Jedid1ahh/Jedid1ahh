<?php
// Connect to the database
$host = 'localhost';
$user = 'root';
$password = ''; // Update with your database password
$database = 'telegram';

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
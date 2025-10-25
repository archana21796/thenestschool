<?php
$servername = "localhost";
$username   = "theneo1n_smmuser";
$password   = "Nest@2025";
$database   = "theneo1n_testnestdb";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
  die("Database Connection failed: " . $conn->connect_error);
}

echo "Connected successfully!";
?>

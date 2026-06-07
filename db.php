<?php
// this file connects to the database

$host = "sql103.infinityfree.com";
$dbname = "if0_42090426_towntrade_db";
$username = "if0_42090426";
$password = "fRESwWATuA1fp"; 

// try connect to the database
$conn = new mysqli($host, $username, $password, $dbname);

// if connection fails, stop everything and show the error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully!";
}
?>

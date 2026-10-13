<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$username = "root";
$password = "";
$database = "todo_db_mini";

$conn = new mysqli($host, $username, $password, $database);
$conn->set_charset("utf8mb4");
?>
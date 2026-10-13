<?php
session_start();

require_once "db.php";

/*
    For now, we use user ID 1 if no login session exists.
    Later we can connect this with your registration/login system.
*/

$user_id = $_SESSION['user_id'] ?? 1;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $task = trim($_POST['task'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? '';

    if ($task == '') {
        die("Task name is required.");
    }

    /*
        Insert user_id along with the task.
    */

    $sql = "INSERT INTO tasks 
            (user_id, task, description, due_date, status)
            VALUES (?, ?, ?, ?, 'Pending')";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "isss",
        $user_id,
        $task,
        $description,
        $due_date
    );

    if ($stmt->execute()) {

        header("Location: index.php");
        exit();

    } else {

        die("Error adding task: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
}
?>
```php
<?php
session_start();

require_once "db.php";

// Check whether the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

// Validate task ID
$taskId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$taskId || $taskId < 1) {
    header("Location: index.php");
    exit;
}

try {
    // Find the task belonging to the logged-in user
    $stmt = $conn->prepare(
        "SELECT status FROM tasks WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $taskId, $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    $stmt->close();

    if (!$task) {
        header("Location: index.php");
        exit;
    }

    // Toggle between Pending and Completed
    $newStatus = ($task["status"] === "Completed")
        ? "Pending"
        : "Completed";

    $stmt = $conn->prepare(
        "UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("sii", $newStatus, $taskId, $userId);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php?updated=1");
    exit;

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo "Could not update the task. Please check the database configuration.";
    exit;
}
?>
```
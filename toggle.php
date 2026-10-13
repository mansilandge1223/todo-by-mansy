
<?php
session_start();
require_once __DIR__ . "/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$taskId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$taskId || $taskId < 1) {
    header("Location: index.php");
    exit;
}

try {
    $stmt = $conn->prepare(
        "SELECT status FROM tasks WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $taskId, $userId);
    $stmt->execute();

    $task = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$task) {
        header("Location: index.php");
        exit;
    }

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

} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo "Could not update the task. Check the PHP error log.";
    exit;
}

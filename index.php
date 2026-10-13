
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$userName = $_SESSION["name"] ?? "User";
$error = "";

// Add task
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $taskName = trim($_POST["task"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $dueDate = trim($_POST["due_date"] ?? "");
    $priority = $_POST["priority"] ?? "Medium";
    $categoryId = filter_var(
        $_POST["category_id"] ?? "",
        FILTER_VALIDATE_INT
    );

    if ($taskName === "") {
        $error = "Please enter a task name.";
    } elseif (!in_array($priority, ["Low", "Medium", "High"], true)) {
        $error = "Please select a valid priority.";
    } elseif (
        $dueDate !== "" &&
        (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) ||
         !checkdate(
             (int) substr($dueDate, 5, 2),
             (int) substr($dueDate, 8, 2),
             (int) substr($dueDate, 0, 4)
         ))
    ) {
        $error = "Please select a valid due date.";
    } else {
        try {
            if ($categoryId === false || $categoryId === null) {
                $categoryId = null;
            }

            if ($categoryId !== null) {
                $check = $conn->prepare(
                    "SELECT category_id FROM categories WHERE category_id = ?"
                );
                $check->bind_param("i", $categoryId);
                $check->execute();
                $validCategory = $check->get_result()->num_rows > 0;
                $check->close();

                if (!$validCategory) {
                    $categoryId = null;
                }
            }

            $stmt = $conn->prepare(
                "INSERT INTO tasks
                 (user_id, category_id, task, description, due_date, priority, status)
                 VALUES (?, ?, ?, ?, NULLIF(?, ''), ?, 'Pending')"
            );

            $stmt->bind_param(
                "iissss",
                $userId,
                $categoryId,
                $taskName,
                $description,
                $dueDate,
                $priority
            );

            $stmt->execute();
            $stmt->close();

            header("Location: index.php?added=1");
            exit;
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
            $error = "Could not save the task. Please try again.";
        }
    }
}

// Get categories
$categories = [];
$categoryResult = $conn->query(
    "SELECT category_id, category_name FROM categories ORDER BY category_name"
);

while ($category = $categoryResult->fetch_assoc()) {
    $categories[] = $category;
}

// Get this user's tasks
$stmt = $conn->prepare(
    "SELECT t.id, t.task, t.description, t.due_date,
            t.priority, t.status, t.category_id,
            c.category_name, t.created_at
     FROM tasks t
     LEFT JOIN categories c ON t.category_id = c.category_id
     WHERE t.user_id = ?
     ORDER BY
        CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END,
        CASE WHEN t.due_date IS NULL THEN 1 ELSE 0 END,
        t.due_date ASC,
        t.id DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}
$stmt->close();

$today = date("Y-m-d");
$total = count($tasks);
$completed = 0;
$pending = 0;
$overdue = 0;
$highPriority = 0;
$weeklyCompleted = array_fill(0, 7, 0);
$weeklyLabels = [];

for ($i = 6; $i >= 0; $i--) {
    $day = date("Y-m-d", strtotime("-$i days"));
    $weeklyLabels[] = date("D", strtotime($day));
}

foreach ($tasks as $t) {
    if ($t["status"] === "Completed") {
        $completed++;
        $created = $t["created_at"] ?? "";
        if ($created && strtotime($created)) {
            $createdDate = date("Y-m-d", strtotime($created));
            $daysAgo = (int) ((strtotime($today) - strtotime($createdDate)) / 86400);
            if ($daysAgo >= 0 && $daysAgo < 7) {
                $weeklyCompleted[6 - $daysAgo]++;
            }
        }
    } else {
        $pending++;

        if (!empty($t["due_date"]) && $t["due_date"] < $today) {
            $overdue++;
        }

        if ($t["priority"] === "High") {
            $highPriority++;
        }
    }
}

$completionRate = $total > 0
    ? (int) round(($completed / $total) * 100)
    : 0;

$maxWeekly = max(1, max($weeklyCompleted));

function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | askFlow</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-page">

<div class="app-layout">

    <aside class="sidebar">
        <a href="index.php" class="brand">
            <span class="brand-icon">✦</span>
            <span>askFlow</span>
        </a>

        <p class="nav-label">WORKSPACE</p>
        <nav class="side-nav">
            <a class="nav-item active" href="index.php">
                <span>▦</span> Dashboard
            </a>
            <a class="nav-item" href="#tasks">
                <span>☷</span> My Tasks
                <span class="nav-count"><?= $pending ?></span>
            </a>
            <a class="nav-item" href="#add-task">
                <span>＋</span> Add New Task
            </a>
            <a class="nav-item" href="#analytics">
                <span>◷</span> Analytics
            </a>
            <a class="nav-item" href="#categories">
                <span>◇</span> Categories
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-tip">
                <span class="tip-icon">✧</span>
                <h3>Small steps matter.</h3>
                <p>Every completed task brings you closer to your goals.</p>
            </div>

            <a class="nav-item" href="profile.php">
                <span>◎</span> My Profile
            </a>
            <a class="nav-item logout-link" href="logout.php">
                <span>↪</span> Sign Out
            </a>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div class="search-box">
                <span>⌕</span>
                <input id="taskSearch" type="search"
                       placeholder="Search your tasks..."
                       aria-label="Search tasks">
                <kbd>SEARCH</kbd>
            </div>

            <div class="top-user">
                <div class="user-avatar">
                    <?= h(strtoupper(substr($userName, 0, 1))) ?>
                </div>
                <div class="user-details">
                    <strong><?= h($userName) ?></strong>
                    <span>Personal workspace</span>
                </div>
                <a href="profile.php" class="user-menu" aria-label="View profile">⌄</a>
            </div>
        </header>

        <section class="welcome-row">
            <div>
                <p class="eyebrow">YOUR PRODUCTIVITY SPACE</p>
                <h1>Good to see you, <?= h($userName) ?> <span>✦</span></h1>
                <p class="muted">A clear mind starts with an organized day.</p>
            </div>

            <div class="today-pill">
                <span>◷</span>
                <div>
                    <small>TODAY</small>
                    <strong><?= date("d M Y") ?></strong>
                </div>
            </div>
        </section>

        <?php if ($error !== ""): ?>
            <div class="notice error-notice"><?= h($error) ?></div>
        <?php elseif (isset($_GET["added"])): ?>
            <div class="notice success-notice">✓ Task added successfully.</div>
        <?php endif; ?>

        <section class="stats-grid">
            <article class="glass-panel stat-card">
                <div class="stat-top">
                    <span>Total tasks</span>
                    <span class="stat-symbol violet">▤</span>
                </div>
                <strong class="stat-number"><?= $total ?></strong>
                <span class="stat-foot">All your tasks</span>
                <div class="stat-line violet-line"></div>
            </article>

            <article class="glass-panel stat-card">
                <div class="stat-top">
                    <span>Pending</span>
                    <span class="stat-symbol orange">◷</span>
                </div>
                <strong class="stat-number"><?= $pending ?></strong>
                <span class="stat-foot">Still to do</span>
                <div class="stat-line orange-line"></div>
            </article>

            <article class="glass-panel stat-card">
                <div class="stat-top">
                    <span>Completed</span>
                    <span class="stat-symbol green">✓</span>
                </div>
                <strong class="stat-number"><?= $completed ?></strong>
                <span class="stat-foot"><?= $completionRate ?>% completion rate</span>
                <div class="stat-line green-line"></div>
            </article>

            <article class="glass-panel stat-card">
                <div class="stat-top">
                    <span>Overdue</span>
                    <span class="stat-symbol pink">!</span>
                </div>
                <strong class="stat-number"><?= $overdue ?></strong>
                <span class="stat-foot">Need your attention</span>
                <div class="stat-line pink-line"></div>
            </article>
        </section>

        <section class="analytics-grid" id="analytics">

            <article class="glass-panel chart-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">YOUR ACTIVITY</p>
                        <h2>Task overview</h2>
                    </div>
                    <span class="live-pill"><span></span> Live data</span>
                </div>

                <div class="chart-legend">
                    <span><i class="legend-dot violet-dot"></i> Completed</span>
                    <span><i class="legend-dot grey-dot"></i> Daily activity</span>
                </div>

                <div class="bar-chart">
                    <?php foreach ($weeklyCompleted as $i => $count): ?>
                        <div class="bar-column">
                            <span class="bar-value"><?= $count ?></span>
                            <div class="bar-track">
                                <div class="bar-fill"
                                     style="--bar-height: <?= max(5, ($count / $maxWeekly) * 100) ?>%">
                                </div>
                            </div>
                            <span class="bar-label"><?= h($weeklyLabels[$i]) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="chart-caption">Completed tasks created in the last 7 days</p>
            </article>

            <article class="glass-panel breakdown-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">YOUR PROGRESS</p>
                        <h2>Task status</h2>
                    </div>
                    <span class="mini-icon">◉</span>
                </div>

                <div class="donut-wrap">
                    <div class="donut-chart"
                         style="--completed-angle: <?= $completionRate * 3.6 ?>deg">
                        <div class="donut-center">
                            <strong><?= $completionRate ?>%</strong>
                            <span>Complete</span>
                        </div>
                    </div>
                </div>

                <div class="status-legend">
                    <div><span><i class="legend-dot violet-dot"></i> Completed</span><strong><?= $completed ?></strong></div>
                    <div><span><i class="legend-dot orange-dot"></i> Pending</span><strong><?= $pending ?></strong></div>
                    <div><span><i class="legend-dot pink-dot"></i> Overdue</span><strong><?= $overdue ?></strong></div>
                </div>
            </article>
        </section>

        <section class="lower-grid">

            <article class="glass-panel tasks-panel" id="tasks">
                <div class="panel-heading task-heading">
                    <div>
                        <p class="eyebrow">STAY ON TRACK</p>
                        <h2>Your tasks <span class="heading-count"><?= $total ?></span></h2>
                    </div>
                    <a href="#add-task" class="small-add">＋ Add task</a>
                </div>

                <div class="filter-row">
                    <button class="filter-chip selected" data-filter="all">All</button>
                    <button class="filter-chip" data-filter="Pending">Pending</button>
                    <button class="filter-chip" data-filter="Completed">Completed</button>
                    <button class="filter-chip" data-filter="overdue">Overdue</button>
                </div>

                <div class="task-list" id="taskList">
                    <?php if (!$tasks): ?>
                        <div class="empty-state">
                            <span>✧</span>
                            <h3>Your task list is empty</h3>
                            <p>Add your first task to get started.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($tasks as $t):
                        $isOverdue = $t["status"] !== "Completed"
                            && !empty($t["due_date"])
                            && $t["due_date"] < $today;

                        $searchText = strtolower(
                            $t["task"] . " " .
                            $t["description"] . " " .
                            ($t["category_name"] ?? "")
                        );
                    ?>
                        <article class="task-row"
                                 data-status="<?= h($t["status"]) ?>"
                                 data-overdue="<?= $isOverdue ? "yes" : "no" ?>"
                                 data-search="<?= h($searchText) ?>">

                            <div class="task-check <?= $t["status"] === "Completed" ? "is-done" : "" ?>">
                                <?= $t["status"] === "Completed" ? "✓" : "·" ?>
                            </div>

                            <div class="task-main">
                                <h3 class="<?= $t["status"] === "Completed" ? "task-done" : "" ?>">
                                    <?= h($t["task"]) ?>
                                </h3>
                                <?php if (!empty($t["description"])): ?>
                                    <p><?= h($t["description"]) ?></p>
                                <?php endif; ?>
                                <div class="task-meta">
                                    <span class="category-tag">
                                        ◇ <?= h($t["category_name"] ?? "Uncategorized") ?>
                                    </span>
                                    <?php if (!empty($t["due_date"])): ?>
                                        <span class="<?= $isOverdue ? "due-overdue" : "due-date" ?>">
                                            ◷ <?= h(date("d M Y", strtotime($t["due_date"]))) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <span class="priority-tag priority-<?= strtolower(h($t["priority"])) ?>">
                                <?= h($t["priority"]) ?>
                            </span>

                            <div class="task-actions-menu">
                                <?php if ($t["status"] !== "Completed"): ?>
                                    <a title="Mark completed"
                                       href="toggle.php?id=<?= (int) $t["id"] ?>">✓</a>
                                <?php else: ?>
                                    <a title="Mark pending"
                                       href="toggle.php?id=<?= (int) $t["id"] ?>">↶</a>
                                <?php endif; ?>
                                <a title="Edit task"
                                   href="edit_task.php?id=<?= (int) $t["id"] ?>">✎</a>
                                <a title="Delete task"
                                   class="delete-action"
                                   href="delete_task.php?id=<?= (int) $t["id"] ?>"
                                   onclick="return confirm('Delete this task?')">×</a>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <div id="noSearchResults" class="empty-state hidden">
                        <h3>No matching tasks</h3>
                        <p>Try another search or filter.</p>
                    </div>
                </div>
            </article>

            <aside class="glass-panel add-panel" id="add-task">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">MAKE IT HAPPEN</p>
                        <h2>Create a task</h2>
                    </div>
                    <span class="add-icon">＋</span>
                </div>

                <p class="muted add-intro">Write it down. Make it happen.</p>

                <form method="POST" action="index.php" class="add-form">
                    <label for="task">Task name</label>
                    <input type="text" id="task" name="task"
                           placeholder="What needs to be done?" maxlength="255" required>

                    <label for="description">Description</label>
                    <textarea id="description" name="description"
                              rows="3" placeholder="Add some details..."></textarea>

                    <label for="due_date">Due date</label>
                    <input type="date" id="due_date" name="due_date">

                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="Low">Low priority</option>
                        <option value="Medium" selected>Medium priority</option>
                        <option value="High">High priority</option>
                    </select>

                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">No category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat["category_id"] ?>">
                                <?= h($cat["category_name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="create-button">
                        <span>＋</span> Create task <span class="button-arrow">→</span>
                    </button>
                </form>

                <div class="focus-tip">
                    <span>✦</span>
                    <div>
                        <strong>Focus on progress</strong>
                        <p>One small task at a time is enough.</p>
                    </div>
                </div>
            </aside>

        </section>

        <footer class="dashboard-footer">
            <span>✦ askFlow</span>
            <span>Stay organized. Keep moving forward.</span>
        </footer>

    </main>
</div>

<script>
const searchInput = document.getElementById("taskSearch");
const taskRows = [...document.querySelectorAll(".task-row")];
const filterButtons = [...document.querySelectorAll(".filter-chip")];
const noResults = document.getElementById("noSearchResults");

let currentFilter = "all";

function filterTasks() {
    const query = searchInput.value.trim().toLowerCase();
    let visible = 0;

    taskRows.forEach(row => {
        const status = row.dataset.status;
        const overdue = row.dataset.overdue === "yes";
        const text = row.dataset.search || "";

        const matchesSearch = text.includes(query);
        let matchesFilter = true;

        if (currentFilter === "Pending" || currentFilter === "Completed") {
            matchesFilter = status === currentFilter;
        } else if (currentFilter === "overdue") {
            matchesFilter = overdue;
        }

        const show = matchesSearch && matchesFilter;
        row.classList.toggle("hidden", !show);

        if (show) visible++;
    });

    noResults.classList.toggle("hidden", visible !== 0);
}

searchInput.addEventListener("input", filterTasks);

filterButtons.forEach(button => {
    button.addEventListener("click", () => {
        currentFilter = button.dataset.filter;

        filterButtons.forEach(item => item.classList.remove("selected"));
        button.classList.add("selected");

        filterTasks();
    });
});
</script>

</body>
</html>
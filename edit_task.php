<?php

session_start();

require_once "db.php";


/* =========================
   CHECK LOGIN
========================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit();

}

$user_id = $_SESSION['user_id'];


/* =========================
   CHECK TASK ID
========================= */

if (!isset($_GET['id'])) {

    header("Location: index.php");
    exit();

}

$id = intval($_GET['id']);


/* =========================
   GET TASK
========================= */

$stmt = $conn->prepare(
    "SELECT *
     FROM tasks
     WHERE id = ? AND user_id = ?"
);

$stmt->bind_param(
    "ii",
    $id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows == 0) {

    $stmt->close();

    header("Location: index.php");

    exit();

}


$task = $result->fetch_assoc();

$stmt->close();


/* =========================
   UPDATE TASK
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $taskName = trim($_POST['task'] ?? '');

    $description = trim(
        $_POST['description'] ?? ''
    );

    $due_date = $_POST['due_date'] ?? '';


    /* Check task name */

    if ($taskName == "") {

        $error = "Task name cannot be empty.";

    } else {


        /* Update only user's task */

        $update = $conn->prepare(
            "UPDATE tasks
             SET task = ?,
                 description = ?,
                 due_date = ?
             WHERE id = ? AND user_id = ?"
        );


        $update->bind_param(
            "sssii",
            $taskName,
            $description,
            $due_date,
            $id,
            $user_id
        );


        if ($update->execute()) {

            $update->close();

            $conn->close();

            header("Location: index.php");

            exit();

        } else {

            $error = "Unable to update task.";

            $update->close();

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Task - Todo by Mansy</title>


    <style>

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

            font-family: Arial, sans-serif;

        }


        body {

            min-height: 100vh;

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 25px;

            background:

                radial-gradient(
                    circle at 10% 10%,
                    rgba(255,180,220,0.7),
                    transparent 30%
                ),

                radial-gradient(
                    circle at 90% 20%,
                    rgba(180,190,255,0.7),
                    transparent 30%
                ),

                linear-gradient(
                    135deg,
                    #f8dcec,
                    #e5e0ff
                );

            color: #40374f;

        }


        .container {

            width: 100%;

            max-width: 700px;

        }


        /* NAVBAR */

        nav {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 18px 25px;

            margin-bottom: 25px;

            background:
                rgba(255,255,255,0.55);

            backdrop-filter: blur(20px);

            border-radius: 20px;

            border:
                1px solid
                rgba(255,255,255,0.8);

        }


        .logo {

            font-size: 22px;

            font-weight: 800;

            color: #6745c7;

        }


        .nav-links {

            display: flex;

            gap: 20px;

        }


        .nav-links a {

            text-decoration: none;

            color: #6e6579;

            font-size: 13px;

            font-weight: 600;

        }


        .profile {

            width: 42px;

            height: 42px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            background:
                linear-gradient(
                    135deg,
                    #9d6bea,
                    #db72b7
                );

            color: white;

            font-size: 20px;

        }


        /* CARD */

        .edit-card {

            padding: 35px;

            background:
                rgba(255,255,255,0.55);

            backdrop-filter: blur(20px);

            border-radius: 28px;

            border:
                1px solid
                rgba(255,255,255,0.8);

            box-shadow:
                0 20px 60px
                rgba(100,70,150,0.15);

        }


        .section-title {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

        }


        .small-text {

            color: #9a67d7;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 1px;

        }


        h2 {

            margin-top: 5px;

            color: #3e354b;

        }


        .plus {

            width: 50px;

            height: 50px;

            border-radius: 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eee5ff;

            font-size: 22px;

        }


        /* FORM */

        label {

            display: block;

            margin: 17px 0 8px;

            font-size: 13px;

            font-weight: 700;

            color: #5c5267;

        }


        input,
        textarea {

            width: 100%;

            padding: 14px;

            border-radius: 13px;

            border:
                1px solid
                #ddd2e8;

            background:
                rgba(255,255,255,0.7);

            outline: none;

            font-size: 14px;

        }


        textarea {

            min-height: 120px;

            resize: vertical;

        }


        input:focus,
        textarea:focus {

            border-color: #9564dc;

            box-shadow:
                0 0 0 3px
                rgba(149,100,220,0.1);

        }


        /* UPDATE BUTTON */

        button {

            width: 100%;

            padding: 15px;

            margin-top: 25px;

            border: none;

            border-radius: 13px;

            background:
                linear-gradient(
                    90deg,
                    #9360dc,
                    #d56ab5
                );

            color: white;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

        }


        button:hover {

            transform: translateY(-1px);

        }


        /* CANCEL */

        .cancel-btn {

            display: block;

            width: 100%;

            margin-top: 12px;

            padding: 14px;

            border-radius: 13px;

            text-align: center;

            text-decoration: none;

            font-weight: 700;

            color: #7650c8;

            background: #eee5ff;

        }


        /* ERROR */

        .error {

            padding: 12px;

            margin-bottom: 15px;

            border-radius: 10px;

            text-align: center;

            background: #ffe4eb;

            color: #c34868;

            font-size: 14px;

        }


        footer {

            text-align: center;

            padding: 25px;

            color: #8b8293;

            font-size: 13px;

        }


        /* MOBILE */

        @media(max-width:600px) {

            .nav-links {

                display: none;

            }

            .edit-card {

                padding: 25px 20px;

            }

        }

    </style>

</head>


<body>


<div class="container">


    <!-- NAVBAR -->

    <nav>


        <div class="logo">

            ✨ Todo by Mansy

        </div>


        <div class="nav-links">

            <a href="index.php">
                Home
            </a>

            <a href="index.php#tasks">
                Tasks
            </a>

            <a href="index.php#tasks">
                Completed
            </a>

        </div>


        <a
            href="profile.php"
            class="profile"
            title="My Profile"
        >

            👤

        </a>


    </nav>


    <!-- EDIT CARD -->

    <div class="edit-card">


        <div class="section-title">


            <div>

                <p class="small-text">

                    UPDATE TASK

                </p>


                <h2>

                    Edit Task ✏️

                </h2>

            </div>


            <span class="plus">

                ✏️

            </span>


        </div>


        <!-- ERROR -->

        <?php if (isset($error)): ?>

            <div class="error">

                <?php

                echo htmlspecialchars($error);

                ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <form method="POST">


            <!-- TASK NAME -->

            <label>

                Task Name

            </label>


            <input

                type="text"

                name="task"

                value="<?php

                    echo htmlspecialchars(
                        $task['task']
                    );

                ?>"

                placeholder="What do you need to do?"

                required

            >


            <!-- DESCRIPTION -->

            <label>

                Description

            </label>


            <textarea

                name="description"

                placeholder="Add some details..."

            ><?php

                echo htmlspecialchars(
                    $task['description'] ?? ''
                );

            ?></textarea>


            <!-- DUE DATE -->

            <label>

                Due Date

            </label>


            <input

                type="date"

                name="due_date"

                value="<?php

                    echo htmlspecialchars(
                        $task['due_date'] ?? ''
                    );

                ?>"

            >


            <!-- UPDATE -->

            <button type="submit">

                ✓ Update Task

            </button>


            <!-- CANCEL -->

            <a

                href="index.php"

                class="cancel-btn"

            >

                ← Cancel

            </a>


        </form>


    </div>


    <!-- FOOTER -->

    <footer>

        ✨ Stay organized. Stay productive.

        <br><br>

        Todo by Mansy

    </footer>


</div>


</body>

</html>
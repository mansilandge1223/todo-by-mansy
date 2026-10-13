
<?php
session_start();
require_once "db.php";

// Check whether the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$error = "";

// Fetch the current user's profile
$stmt = $conn->prepare(
    "SELECT user_id, name, email, created_at
     FROM users
     WHERE user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

// Format the account creation date
$createdAt = "Not available";

if (!empty($user["created_at"])) {
    $timestamp = strtotime($user["created_at"]);

    if ($timestamp !== false) {
        $createdAt = date("d M Y", $timestamp);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | askFlow</title>

    <link rel="stylesheet" href="style.css">

    <style>
        body.profile-page {
            min-height: 100vh;
            padding: 30px 18px;
            color: #fff;
            background:
                radial-gradient(circle at top left, #39256b, transparent 40%),
                linear-gradient(135deg, #100b27, #211443, #171033);
        }

        .profile-container {
            width: 100%;
            max-width: 760px;
            margin: 0 auto;
        }

        .profile-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 35px;
        }

        .profile-brand {
            color: #fff;
            font-size: 25px;
            font-weight: 800;
            text-decoration: none;
        }

        .profile-nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .profile-nav-links a {
            padding: 10px 14px;
            border: 1px solid #ffffff20;
            border-radius: 10px;
            color: #e9e2ff;
            font-size: 13px;
            text-decoration: none;
            background: #ffffff09;
        }

        .profile-nav-links a:hover {
            background: #ffffff18;
        }

        .profile-heading {
            margin-bottom: 25px;
            text-align: center;
        }

        .profile-heading h1 {
            margin: 0 0 8px;
            font-size: 32px;
        }

        .profile-heading p {
            margin: 0;
            color: #c9c1e4;
            font-size: 14px;
        }

        .profile-card {
            padding: 35px;
            border: 1px solid #ffffff25;
            border-radius: 24px;
            background: #ffffff0d;
            backdrop-filter: blur(18px);
            box-shadow: 0 25px 65px #0003;
        }

        .profile-avatar {
            width: 94px;
            height: 94px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #c4b5fd, #818cf8);
            color: #211344;
            font-size: 35px;
            font-weight: 800;
            box-shadow: 0 12px 30px #8b5cf633;
        }

        .profile-name {
            margin: 0;
            text-align: center;
            font-size: 25px;
            overflow-wrap: anywhere;
        }

        .profile-email {
            margin: 8px 0 28px;
            text-align: center;
            color: #c9c1e4;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .profile-section-title {
            margin: 0 0 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #ffffff18;
            font-size: 16px;
        }

        .profile-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .profile-detail {
            min-width: 0;
            padding: 17px;
            border: 1px solid #ffffff12;
            border-radius: 14px;
            background: #100a2b55;
        }

        .profile-detail span {
            display: block;
            margin-bottom: 9px;
            color: #b7acd8;
            font-size: 12px;
        }

        .profile-detail strong {
            display: block;
            color: #fff;
            font-size: 15px;
            overflow-wrap: anywhere;
        }

        .profile-actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .profile-button {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            transition: transform .2s, opacity .2s;
        }

        .profile-button:hover {
            transform: translateY(-2px);
            opacity: .9;
        }

        .profile-button-primary {
            background: linear-gradient(100deg, #c4b5fd, #818cf8);
            color: #211344;
        }

        .profile-button-secondary {
            border: 1px solid #ffffff25;
            color: #eee8ff;
            background: #ffffff0b;
        }

        .profile-footer {
            margin-top: 22px;
            color: #a99fc9;
            text-align: center;
            font-size: 12px;
        }

        @media (max-width: 520px) {
            body.profile-page {
                padding: 22px 14px;
            }

            .profile-nav {
                align-items: flex-start;
                flex-direction: column;
            }

            .profile-heading h1 {
                font-size: 27px;
            }

            .profile-card {
                padding: 25px 18px;
            }

            .profile-details {
                grid-template-columns: 1fr;
            }

            .profile-actions {
                flex-direction: column;
            }

            .profile-button {
                width: 100%;
            }
        }
    </style>
</head>

<body class="profile-page">

    <main class="profile-container">

        <nav class="profile-nav">
            <a class="profile-brand" href="index.php">✓ askFlow</a>

            <div class="profile-nav-links">
                <a href="index.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>

        <header class="profile-heading">
            <h1>My Profile</h1>
            <p>View your account information and details.</p>
        </header>

        <section class="profile-card">

            <div class="profile-avatar">
                <?php
                $initial = function_exists("mb_substr")
                    ? mb_substr(trim($user["name"]), 0, 1, "UTF-8")
                    : substr(trim($user["name"]), 0, 1);

                echo htmlspecialchars(
                    strtoupper($initial),
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </div>

            <h2 class="profile-name">
                <?= htmlspecialchars($user["name"], ENT_QUOTES, "UTF-8") ?>
            </h2>

            <p class="profile-email">
                <?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?>
            </p>

            <h3 class="profile-section-title">Account Information</h3>

            <div class="profile-details">

                <div class="profile-detail">
                    <span>User ID</span>
                    <strong>
                        #<?= (int) $user["user_id"] ?>
                    </strong>
                </div>

                <div class="profile-detail">
                    <span>Full Name</span>
                    <strong>
                        <?= htmlspecialchars($user["name"], ENT_QUOTES, "UTF-8") ?>
                    </strong>
                </div>

                <div class="profile-detail">
                    <span>Email Address</span>
                    <strong>
                        <?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?>
                    </strong>
                </div>

                <div class="profile-detail">
                    <span>Member Since</span>
                    <strong>
                        <?= htmlspecialchars($createdAt, ENT_QUOTES, "UTF-8") ?>
                    </strong>
                </div>

            </div>

            <div class="profile-actions">
                <a href="index.php" class="profile-button profile-button-primary">
                    Go to Dashboard →
                </a>

                <a href="logout.php" class="profile-button profile-button-secondary">
                    Logout
                </a>
            </div>

        </section>

        <footer class="profile-footer">
            Your tasks, your goals, your progress.
        </footer>

    </main>

</body>
</html>
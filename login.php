
<?php
session_start();
require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, name, password_hash
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user["password_hash"])) {
            session_regenerate_id(true);

            $_SESSION["user_id"] = (int) $user["user_id"];
            $_SESSION["name"] = $user["name"];

            header("Location: index.php");
            exit;
        }

        $error = "Invalid email or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | askFlow</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<div class="auth-orb auth-orb-one"></div>
<div class="auth-orb auth-orb-two"></div>
<div class="auth-orb auth-orb-three"></div>

<main class="auth-wrapper">

    <header class="auth-brand">
        <div class="auth-logo"><span>✓</span></div>
        <h1>askFlow</h1>
        <p>Your personal productivity space.</p>
    </header>

    <section class="auth-card">

        <div class="auth-card-top">
            <span class="auth-eyebrow">YOUR SPACE AWAITS</span>
            <h2>Welcome back <span class="wave">✦</span></h2>
            <p class="auth-subtitle">
                Sign in to organize your tasks and stay on track.
            </p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="auth-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form">

            <div class="auth-field">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    required
                >
            </div>

            <div class="auth-field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="auth-button">
                <span>Sign In</span>
                <span class="button-arrow">→</span>
            </button>

        </form>

        <div class="auth-divider"><span>NEW TO AS KFLOW?</span></div>

        <p class="auth-footer">
            Don't have an account?
            <a href="register.php">Create one <span>↗</span></a>
        </p>

    </section>

    <footer class="auth-note">
        <span class="note-star">✧</span>
        Your tasks, your goals, your progress.
    </footer>

</main>
</body>
</html>
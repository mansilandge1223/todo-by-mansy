
<?php
session_start();
require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (
        $name === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {
        $error = "Enter a valid name, email, and password of at least 8 characters.";
    } else {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password_hash)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param("sss", $name, $email, $passwordHash);
            $stmt->execute();

            session_regenerate_id(true);
            $_SESSION["user_id"] = (int) $conn->insert_id;
            $_SESSION["name"] = $name;

            header("Location: index.php");
            exit;

        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $error = "This email is already registered. Please sign in.";
            } else {
                error_log($e->getMessage());
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | askFlow</title>
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
            <span class="auth-eyebrow">START SOMETHING GREAT</span>
            <h2>Create your account <span class="wave">✦</span></h2>
            <p class="auth-subtitle">
                Start organizing your day, one task at a time.
            </p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="auth-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="auth-form">

            <div class="auth-field">
                <label for="name">Full name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your full name"
                    maxlength="100"
                    autocomplete="name"
                    value="<?= htmlspecialchars($_POST["name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    required
                >
            </div>

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
                    placeholder="At least 8 characters"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
                <small>Use at least 8 characters.</small>
            </div>

            <button type="submit" class="auth-button">
                <span>Create Account</span>
                <span class="button-arrow">→</span>
            </button>

        </form>

        <div class="auth-divider"><span>ALREADY A MEMBER?</span></div>

        <p class="auth-footer">
            Already have an account?
            <a href="login.php">Sign in <span>↗</span></a>
        </p>

    </section>

    <footer class="auth-note">
        <span class="note-star">✧</span>
        A fresh start begins with one small step.
    </footer>

</main>
</body>
</html>
<?php

$basePath = "";

require_once "database/config.php";        // loads harden.php → session started
require_once "security/shield.php";
require_once "security/authorize.php";


// Already logged in? Send them to their dashboard.
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/admin-dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}


$errors = [];
$email  = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ---- CSRF ----
    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token. Please try again.";
    }

    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    // ---- Field validation ----
    if (empty($errors)) {
        if ($email === "" || $password === "") {
            $errors[] = "Please enter both email and password.";
        }
    }

    // ---- Rate limit check (only for real attempts) ----
    if (empty($errors)) {
        if (rateLimitExceeded('login', 5, 300)) {
            $errors[] = "Too many failed attempts. Please wait 5 minutes and try again.";
            securityLog('login.rate_limited', ['email' => $email]);
        }
    }

    // ---- Credential check ----
    if (empty($errors)) {

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindValue(":email", $email);
        $stmt->execute();

        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user["password"])) {

    $errors[] = "Invalid email or password.";
    securityLog('login.failed', ['email' => $email]);

    } elseif (!empty($user["deleted_at"])) {

    $errors[] = "This account has been deactivated. Please contact the gallery.";
    securityLog('login.deactivated', ['email' => $email]);

    } else {

            // ---- Success ----
            rateLimitReset('login');

            session_regenerate_id(true);   // kill fixation

            $_SESSION["user_id"]    = $user["id"];
            $_SESSION["first_name"] = $user["first_name"];
            $_SESSION["last_name"]  = $user["last_name"];
            $_SESSION["email"]      = $user["email"];
            $_SESSION["role"]       = $user["role"];

            securityLog('login.success', ['user_id' => $user["id"]]);

            if ($user["role"] === "admin") {
                header("Location: admin/admin-dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="login.css">

</head>
<body>

<?php require_once "includes/header.php"; ?>

<main class="auth-page">

    <div class="auth-card">

        <p class="auth-eyebrow">EDL GALLERY</p>
        <h1>Log In</h1>

        <?php if (!empty($errors)): ?>
            <div class="form-errors">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label for="email">EMAIL</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">PASSWORD</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >
            </div>

            <button type="submit" class="auth-button">LOG IN</button>

        </form>

        <p class="auth-footer">
            Don't have an account?
            <a href="register.php">Register</a>
        </p>

    </div>

</main>

</body>
</html>
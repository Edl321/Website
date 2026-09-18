<?php

$basePath = "";

require_once "database/config.php";
require_once "security/authorize.php";
require_once "security/shield.php";

if (!isUser()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];

$errors   = [];
$feedback = "";

// Load current user data
$stmt = $pdo->prepare("
    SELECT id, first_name, last_name, email, created_at
    FROM users
    WHERE id = :id
    LIMIT 1
");
$stmt->bindValue(":id", $userId, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit;
}

// Form field defaults
$first_name = $user["first_name"];
$last_name  = $user["last_name"];
$email      = $user["email"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token. Please try again.";
    } else {

        $action = $_POST["action"] ?? "";

        // ---------------- UPDATE PROFILE ----------------
        if ($action === "update_profile") {

            $first_name = trim($_POST["first_name"] ?? "");
            $last_name  = trim($_POST["last_name"] ?? "");
            $email      = trim($_POST["email"] ?? "");

            if ($first_name === "") {
                $errors[] = "First name is required.";
            } elseif (mb_strlen($first_name) > 100) {
                $errors[] = "First name is too long.";
            }

            if ($last_name === "") {
                $errors[] = "Last name is required.";
            } elseif (mb_strlen($last_name) > 100) {
                $errors[] = "Last name is too long.";
            }

            if ($email === "") {
                $errors[] = "Email is required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please enter a valid email address.";
            } else {
                // Uniqueness check
                $check = $pdo->prepare("
                    SELECT id FROM users
                    WHERE email = :email AND id != :id
                    LIMIT 1
                ");
                $check->bindValue(":email", $email);
                $check->bindValue(":id", $userId, PDO::PARAM_INT);
                $check->execute();

                if ($check->fetch()) {
                    $errors[] = "That email is already registered to another account.";
                }
            }

            if (empty($errors)) {

                $update = $pdo->prepare("
                    UPDATE users
                    SET first_name = :first_name,
                        last_name  = :last_name,
                        email      = :email
                    WHERE id = :id
                ");
                $update->bindValue(":first_name", $first_name);
                $update->bindValue(":last_name", $last_name);
                $update->bindValue(":email", $email);
                $update->bindValue(":id", $userId, PDO::PARAM_INT);
                $update->execute();

                // Update session
                $_SESSION["first_name"] = $first_name;
                $_SESSION["last_name"]  = $last_name;
                $_SESSION["email"]      = $email;

                // Refresh local copy
                $user["first_name"] = $first_name;
                $user["last_name"]  = $last_name;
                $user["email"]      = $email;

                $feedback = "Your profile has been updated.";
            }
        }

        // ---------------- CHANGE PASSWORD ----------------
        elseif ($action === "change_password") {

            $currentPassword = $_POST["current_password"] ?? "";
            $newPassword     = $_POST["new_password"] ?? "";
            $confirmPassword = $_POST["confirm_password"] ?? "";

            if ($currentPassword === "") {
                $errors[] = "Current password is required.";
            }

            if ($newPassword === "") {
                $errors[] = "New password is required.";
            } elseif (strlen($newPassword) < 8) {
                $errors[] = "New password must be at least 8 characters.";
            } elseif ($newPassword === $currentPassword) {
                $errors[] = "New password must be different from your current password.";
            }

            if ($confirmPassword === "") {
                $errors[] = "Please confirm your new password.";
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = "New passwords do not match.";
            }

            // Verify current password
            if (empty($errors)) {
                $pwStmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
                $pwStmt->bindValue(":id", $userId, PDO::PARAM_INT);
                $pwStmt->execute();
                $row = $pwStmt->fetch();

                if (!$row || !password_verify($currentPassword, $row["password"])) {
                    $errors[] = "Your current password is incorrect.";
                }
            }

            if (empty($errors)) {

                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

                $update = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $update->bindValue(":password", $newHash);
                $update->bindValue(":id", $userId, PDO::PARAM_INT);
                $update->execute();

                // Regenerate session ID after password change
                session_regenerate_id(true);

                $feedback = "Your password has been changed.";
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
    <title>My Profile | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="user-dashboard.css">
</head>
<body>

<?php require_once "includes/header.php"; ?>

<main class="user-dashboard">

    <section class="user-dashboard-hero">
        <div>
            <p class="user-dashboard-label">EDL GALLERY / ACCOUNT</p>
            <h1>My <span>Profile</span></h1>
            <p class="user-dashboard-welcome">
                Manage your personal information and password.
            </p>
        </div>

        <div class="user-dashboard-date">
            <strong><?php echo date("F j, Y"); ?></strong>
            <?php echo date("l"); ?>
        </div>
    </section>

    <a href="dashboard.php" class="back-dashboard-link">
        &larr; Back to Dashboard
    </a>

    <?php if (!empty($feedback)): ?>
        <div class="inquiry-success-note" style="margin-bottom:25px;">
            <p><?php echo htmlspecialchars($feedback); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="form-errors" style="margin-bottom:25px;">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>


    <!-- PROFILE INFORMATION -->
    <div class="admin-text-block">

        <h4>PROFILE INFORMATION</h4>

        <form method="POST" action="profile.php" class="profile-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="form-row">

                <div class="form-group">
                    <label for="first_name">
                        FIRST NAME <span class="required-mark">*</span>
                    </label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="<?php echo htmlspecialchars($first_name); ?>"
                        placeholder="Enter your first name"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="last_name">
                        LAST NAME <span class="required-mark">*</span>
                    </label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="<?php echo htmlspecialchars($last_name); ?>"
                        placeholder="Enter your last name"
                        maxlength="100"
                        required
                    >
                </div>

            </div>

            <div class="form-group">
                <label for="email">
                    EMAIL <span class="required-mark">*</span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    placeholder="you@example.com"
                    maxlength="255"
                    required
                >
                <small class="field-help">
                    Must be unique — no two accounts can share the same email.
                </small>
            </div>

            <button type="submit" class="submit-button">
                SAVE CHANGES
            </button>

        </form>

    </div>


    <!-- CHANGE PASSWORD -->
    <div class="admin-text-block">

        <h4>CHANGE PASSWORD</h4>

        <p class="profile-block-note">
            You must enter your current password to set a new one.
        </p>

        <form
            method="POST"
            action="profile.php"
            id="passwordForm"
            class="profile-form"
        >
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label for="current_password">
                    CURRENT PASSWORD <span class="required-mark">*</span>
                </label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    placeholder="Enter your current password"
                    required
                >
                <small class="field-help">
                    Required to verify it's really you.
                </small>
            </div>

            <div class="form-row">

                <div class="form-group">
                    <label for="new_password">
                        NEW PASSWORD <span class="required-mark">*</span>
                    </label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="At least 8 characters"
                        minlength="8"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        CONFIRM NEW PASSWORD <span class="required-mark">*</span>
                    </label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-enter your new password"
                        minlength="8"
                        required
                    >
                </div>

            </div>

            <small class="field-help">
                Minimum 8 characters. Must be different from your current password.
            </small>

            <button type="submit" class="submit-button">
                UPDATE PASSWORD
            </button>

        </form>

    </div>

</main>

<?php require_once "includes/footer.php"; ?>

<script src="javascript.js"></script>

</body>
</html>
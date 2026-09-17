<?php

$basePath = "../";

define('EDL_ADMIN', true);

require_once "../database/config.php";
require_once "../security/authorize.php";
require_once "../security/shield.php";
require_once "admin-includes/helpers.php";


if (!isLoggedIn() || !isAdmin()) {

    header("Location: ../login.php");
    exit;

}


$adminId = $_SESSION["user_id"];

$errors   = [];
$feedback = "";


// ---- HANDLE SOFT DELETE / RESTORE ----

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {

        $errors[] = "Invalid security token. Please try again.";

    } else {

        $action = $_POST["action"] ?? "";
        $targetId = $_POST["user_id"] ?? "";

        if (!ctype_digit((string)$targetId)) {

            $errors[] = "Invalid user reference.";

        } elseif ((int)$targetId === (int)$adminId) {

            $errors[] = "You cannot deactivate your own account.";

        } else {

            $targetId = (int)$targetId;

            // Make sure the user exists
            $checkStmt = $pdo->prepare("SELECT id, deleted_at FROM users WHERE id = :id LIMIT 1");
            $checkStmt->bindValue(":id", $targetId, PDO::PARAM_INT);
            $checkStmt->execute();
            $target = $checkStmt->fetch();

            if (!$target) {

                $errors[] = "That user no longer exists.";

            } elseif ($action === "deactivate_user") {

                if (!empty($target["deleted_at"])) {
                    $errors[] = "That user is already deactivated.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :id");
                    $stmt->bindValue(":id", $targetId, PDO::PARAM_INT);
                    $stmt->execute();
                    $feedback = "User deactivated. They can no longer log in.";
                }

            } elseif ($action === "reactivate_user") {

                if (empty($target["deleted_at"])) {
                    $errors[] = "That user is already active.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET deleted_at = NULL WHERE id = :id");
                    $stmt->bindValue(":id", $targetId, PDO::PARAM_INT);
                    $stmt->execute();
                    $feedback = "User reactivated. They can log in again.";
                }

            } else {

                $errors[] = "Unknown action.";

            }

        }

    }

}


// ---- FETCH ALL USERS (including deactivated) ----

$users = $pdo->query(
    "SELECT id, first_name, last_name, email, role, created_at, deleted_at
    FROM users
    ORDER BY
        CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END,
        created_at DESC"
)->fetchAll();

$pageTitle  = "Users";
$activePage = "users";
require_once "admin-head.php";
?>

<section class="admin-page">

    <div class="admin-header">

        <p class="admin-label">ADMIN</p>

        <h1>Users</h1>

        <p class="admin-subtext">
            Everyone registered on EDL Gallery (<?php echo count($users); ?> total).
            Deactivated users are kept in the database but cannot log in.
        </p>

    </div>


    <?php if (!empty($feedback)): ?>
        <div class="inquiry-success-note">
            <p><?php echo htmlspecialchars($feedback); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="form-errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>


    <?php if (empty($users)): ?>

        <div class="empty-dashboard">
            <h3>No Users Yet</h3>
            <p>There are no registered users on EDL Gallery.</p>
        </div>

    <?php else: ?>

        <?php foreach ($users as $user): ?>

            <?php $isDeactivated = !empty($user["deleted_at"]); ?>

            <div class="admin-list-row<?php echo $isDeactivated ? ' admin-list-row--deactivated' : ''; ?>">

                <div class="admin-list-row-main">

                    <h3>
                        <?php echo htmlspecialchars($user["first_name"] . " " . $user["last_name"]); ?>

                        <?php if ($isDeactivated): ?>
                            <span class="user-deactivated-tag">DEACTIVATED</span>
                        <?php endif; ?>
                    </h3>

                    <p class="inquiry-meta">
                        <?php echo htmlspecialchars($user["email"]); ?>
                        &middot;
                        Joined <?php echo date("F j, Y", strtotime($user["created_at"])); ?>

                        <?php if ($isDeactivated): ?>
                            &middot;
                            Deactivated <?php echo date("F j, Y", strtotime($user["deleted_at"])); ?>
                        <?php endif; ?>
                    </p>

                </div>

                <div class="admin-list-row-actions">

                    <strong class="status-<?php echo $user['role'] === 'admin' ? 'published' : 'draft'; ?>">
                        <?php echo strtoupper($user["role"]); ?>
                    </strong>

                    <?php if ((int)$user["id"] === (int)$adminId): ?>

                        <span class="inquiry-meta">(this is you)</span>

                    <?php elseif ($isDeactivated): ?>

                        <form
                            method="POST"
                            action="users.php"
                            onsubmit="return confirm('Reactivate this user? They will be able to log in again.');"
                            style="display:inline;"
                        >
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="reactivate_user">
                            <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                            <button type="submit" class="approve-button">REACTIVATE</button>
                        </form>

                    <?php else: ?>

                        <form
                            method="POST"
                            action="users.php"
                            onsubmit="return confirm('Deactivate this user? They will no longer be able to log in.');"
                            style="display:inline;"
                        >
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="deactivate_user">
                            <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                            <button type="submit" class="reject-button">DEACTIVATE</button>
                        </form>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</section>

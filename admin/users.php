<?php

session_start();
$basePath = "../";

require_once "../database/config.php";
require_once "../security/authorize.php";
require_once "../security/shield.php";


if (!isLoggedIn() || !isAdmin()) {

    header("Location: ../login.php");
    exit;

}


$adminId = $_SESSION["user_id"];

$errors   = [];
$feedback = "";


// ---- HANDLE ROLE CHANGE ----

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {

        $errors[] = "Invalid security token. Please try again.";

    } else {

        $targetId  = $_POST["user_id"] ?? "";
        $newRole   = $_POST["new_role"] ?? "";

        if (
            !ctype_digit((string)$targetId) ||
            !in_array($newRole, ["user", "admin"], true)
        ) {

            $errors[] = "Invalid request.";

        } elseif ((int)$targetId === (int)$adminId) {

            // An admin should never be able to demote/change their own
            // role from this page - that could lock them out.
            $errors[] = "You cannot change your own role here.";

        } else {

            $updateStmt = $pdo->prepare(
                "UPDATE users SET role = :role WHERE id = :id"
            );
            $updateStmt->bindValue(":role", $newRole);
            $updateStmt->bindValue(":id", (int)$targetId, PDO::PARAM_INT);
            $updateStmt->execute();

            $feedback = "User role updated.";

        }

    }

}


// ---- FETCH ALL USERS ----

$users = $pdo->query(
    "SELECT id, first_name, last_name, email, role, created_at
    FROM users
    ORDER BY created_at DESC"
)->fetchAll();

$pageTitle  = "Users";
$activePage = "users";
require_once "admin-head.php";
?>
<!DOCTYPE html>
<html lang="en">
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="admin-layout.css">

<section class="admin-page">

    <div class="admin-header">

        <p class="admin-label">ADMIN</p>

        <h1>Users</h1>

        <p class="admin-subtext">
            Everyone registered on EDL Gallery (<?php echo count($users); ?> total).
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


    <?php foreach ($users as $user): ?>

        <div class="admin-list-row">

            <div class="admin-list-row-main">

                <h3>
                    <?php echo htmlspecialchars($user["first_name"] . " " . $user["last_name"]); ?>
                </h3>

                <p class="inquiry-meta">
                    <?php echo htmlspecialchars($user["email"]); ?>
                    &middot;
                    Joined <?php echo date("F j, Y", strtotime($user["created_at"])); ?>
                </p>

            </div>

            <div class="admin-list-row-actions">

                <strong class="status-<?php echo $user['role'] === 'admin' ? 'published' : 'draft'; ?>">
                    <?php echo strtoupper($user["role"]); ?>
                </strong>

                <?php if ((int)$user["id"] !== (int)$adminId): ?>

                    <form
                        method="POST"
                        action="users.php"
                        onsubmit="return confirm('Change this user\'s role?');"
                        style="display:inline;"
                    >
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">

                        <?php if ($user["role"] === "user"): ?>

                            <input type="hidden" name="new_role" value="admin">
                            <button type="submit" class="admin-view-button">MAKE ADMIN</button>

                        <?php else: ?>

                            <input type="hidden" name="new_role" value="user">
                            <button type="submit" class="admin-view-button">MAKE USER</button>

                        <?php endif; ?>

                    </form>

                <?php else: ?>

                    <span class="inquiry-meta">(this is you)</span>

                <?php endif; ?>

            </div>

        </div>

    <?php endforeach; ?>

</section>
</html>

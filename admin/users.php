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

<section class="admin-page">

    <div class="admin-header">

        <p class="admin-label">ADMIN</p>

        <h1>Users</h1>

        <p class="admin-subtext">
            Everyone registered on EDL Gallery (<?php echo count($users); ?> total).
            Roles are managed at the database level by super admins.
        </p>

    </div>


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

                <?php if ((int)$user["id"] === (int)$adminId): ?>
                    <span class="inquiry-meta">(this is you)</span>
                <?php endif; ?>

            </div>

        </div>

    <?php endforeach; ?>

</section>

<?php require_once "admin-foot.php"; ?>
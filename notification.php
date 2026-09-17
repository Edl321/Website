<?php


$basePath = "";

require_once "database/config.php";
require_once "security/shield.php";
require_once "security/authorize.php";


if (!isUser()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];

$stmt = $pdo->prepare("
    SELECT id, title, message, is_read, created_at
    FROM notifications
    WHERE user_id = :user_id
    ORDER BY created_at DESC
");

$stmt->execute([
    ":user_id" => $userId
]);

$notifications = $stmt->fetchAll();

$unreadStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = :user_id
    AND is_read = 0
");

$unreadStmt->execute([
    ":user_id" => $userId
]);

$unreadCount = $unreadStmt->fetchColumn();

?>


<?php require_once "includes/header.php"; ?>

<link rel="stylesheet" href="style.css">
<main class="notification-page">

<section class="notification-hero">

    <div class="notification-heading">

        <p class="notification-label">
            UPDATES
        </p>

        <h1>
            Notifications
        </h1>

        <div class="notification-heading-meta">

            <a href="dashboard.php" class="back-dashboard"> ← BACK TO DASHBOARD </a>

                    <?php if ($unreadCount > 0): ?>

            <span class="notification-count">

                    <?php echo (int)$unreadCount; ?> UNREAD </span>

                    <?php else: ?>

            <span class="notification-count no-unread"> ALL READ </span>

                    <?php endif; ?>

        </div>

    </div>

</section>



<section class="notification-list-section">

        <?php if (empty($notifications)): ?>

    <div class="no-notifications">

        <p class="notification-label">UPDATES</p>

            <h2>No Notifications</h2>

        <p>You currently have no notifications.</p>

    </div>

        <?php else: ?>


    <div class="notification-list">

                <?php foreach ($notifications as $notification): ?>

            <article class="notification-card<?php echo $notification["is_read"] ? "read" : "unread"; ?>">

        <div class="notification-card-content">

            <div class="notification-card-top">

                <h2>
                <?php echo htmlspecialchars($notification["title"]);?>
                </h2>

                    <?php if (!$notification["is_read"]): ?>
                        <span class="notification-status">NEW</span>
                    <?php endif; ?>

            </div>

                <p class="notification-message">
                <?php echo nl2br(htmlspecialchars($notification["message"]));?>
                </p>

                <p class="notification-date">
                <?php echo date("F j, Y • g:i A",strtotime($notification["created_at"]));?>
                </p>

            <?php if (!$notification["is_read"]): ?>

                <form
                    action="notification-read.php"
                    method="POST"
                    class="notification-read-form"
                >

        <?php echo csrf_field(); ?>

                <input
                    type="hidden"
                    name="notification_id"
                    value="<?php echo (int)$notification["id"]; ?>"
                >

                <button type="submit">MARK AS READ</button>

                </form>

        <?php endif; ?>

        </div>

            </article>

        <?php endforeach; ?>

</div>

        <?php endif; ?>

</section>


</main>


<?php require_once "includes/footer.php"; ?>
<?php

session_start();

require_once "database/config.php";
require_once "security/authorize.php";

if (!isUser()) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

$notificationId = filter_input(
    INPUT_POST,
    "notification_id",
    FILTER_VALIDATE_INT
);

if (!$notificationId) {
    header("Location: dashboard.php");
    exit;
}

/*
 * Update ONLY the notification belonging
 * to the currently logged-in user.
 */
$stmt = $pdo->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE id = :notification_id
    AND user_id = :user_id
");

$stmt->execute([
    ":notification_id" => $notificationId,
    ":user_id" => $_SESSION["user_id"]
]);

header("Location: dashboard.php");
exit;
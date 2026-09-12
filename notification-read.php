<?php

session_start();

require_once "database/config.php";
require_once "security/shield.php";
require_once "security/authorize.php";


/* Only logged-in users can access this page */

if (!isUser()) {
    header("Location: login.php");
    exit;
}


/* Only allow POST requests */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: notification.php");
    exit;
}


/* Check CSRF token */

if (!verify_csrf_token()) {
    die("Invalid CSRF token.");
}


/* Get notification ID */

$notificationId = filter_input(
    INPUT_POST,
    "notification_id",
    FILTER_VALIDATE_INT
);


/* Validate notification ID */

if (!$notificationId) {
    die("Invalid Notification ID.");
}


/* Mark notification as read */

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


/* Return to notification page */

header("Location: notification.php");
exit;

?>
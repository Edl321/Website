<?php

$basePath = "";

require_once "database/config.php";
require_once "security/shield.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contact.php");
    exit;
}

if (!verify_csrf_token()) {
    header("Location: contact.php?error=csrf");
    exit;
}


$name    = trim($_POST["name"] ?? "");
$email   = trim($_POST["email"] ?? "");
$subject = trim($_POST["subject"] ?? "");
$message = trim($_POST["message"] ?? "");


$errors = [];

if ($name === "" || mb_strlen($name) > 150) {
    $errors[] = "invalid_name";
}

if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
    $errors[] = "invalid_email";
}

if (mb_strlen($subject) > 255) {
    $errors[] = "invalid_subject";
}

if ($message === "" || mb_strlen($message) > 5000) {
    $errors[] = "invalid_message";
}

if (!empty($errors)) {
    header("Location: contact.php?error=validation");
    exit;
}


try {

    $insertStmt = $pdo->prepare("
        INSERT INTO contact_messages
            (name, email, subject, message, is_read, created_at)
        VALUES
            (:name, :email, :subject, :message, 0, NOW())
    ");

    $insertStmt->bindValue(":name", $name);
    $insertStmt->bindValue(":email", $email);
    $insertStmt->bindValue(":subject", $subject !== "" ? $subject : null);
    $insertStmt->bindValue(":message", $message);

    $insertStmt->execute();

} catch (PDOException $e) {

    $logDirectory = __DIR__ . "/logs";

    if (!is_dir($logDirectory)) {
        @mkdir($logDirectory, 0755, true);
    }

    $logFile = $logDirectory . "/contact-messages.log";

    $logLine = sprintf(
        "[%s] From: %s <%s> | Subject: %s\n%s\n%s\n",
        date("Y-m-d H:i:s"),
        $name,
        $email,
        $subject !== "" ? $subject : "(no subject)",
        $message,
        str_repeat("-", 60)
    );

    @file_put_contents($logFile, $logLine, FILE_APPEND);

    // If even the log write failed, surface a real error.
    if (!is_file($logFile)) {
        header("Location: contact.php?error=save");
        exit;
    }

}

header("Location: contact.php?success=1");
exit;
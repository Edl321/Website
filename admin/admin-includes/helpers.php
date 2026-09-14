<?php

// Shared helper functions for the EDL Gallery admin portal.
//
// This file must NEVER be reached directly in the browser.
// The guard below ensures it only runs when included from an
// admin page that has already defined EDL_ADMIN.

if (!defined('EDL_ADMIN')) {
    http_response_code(403);
    exit;
}

function formatStatusLabel(string $status): string
{
    $labels = [
        "draft"              => "DRAFT",
        "artwork_submission" => "ARTWORK SUBMISSION",
        "ready_to_publish"   => "READY TO PUBLISH",
        "published"          => "PUBLISHED",
        "completed"          => "COMPLETED",
        "cancelled"          => "CANCELLED",
    ];

    return $labels[$status] ?? strtoupper($status);
}

function getArtistById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM artists WHERE id = :id");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch();

    return $row ?: null;
}

function deleteManagedImage(string $storedPath, string $expectedPrefix): void
{
    if ($storedPath === "") {
        return;
    }

    if (strpos($storedPath, $expectedPrefix) !== 0) {
        return;
    }

    if (strpos($storedPath, "..") !== false) {
        return;
    }

    $fullPath = "../Images/" . $storedPath;

    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function handleImageUpload(
    array $file,
    string $subdir,
    string $filenamePrefix,
    array $allowedTypes,
    int $maxBytes,
    array &$errors
): ?string {

    if (empty($file["name"])) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $errors[] = "There was a problem uploading the image. Please try again.";
        return null;
    }

    if ($file["size"] > $maxBytes) {
        $errors[] = "Image must be smaller than " . round($maxBytes / 1024 / 1024) . "MB.";
        return null;
    }

    if (!is_uploaded_file($file["tmp_name"])) {
        $errors[] = "There was a problem uploading the image. Please try again.";
        return null;
    }

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $realType = $finfo ? finfo_file($finfo, $file["tmp_name"]) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $imageInfo = @getimagesize($file["tmp_name"]);

    if (
        $realType === false ||
        !isset($allowedTypes[$realType]) ||
        $imageInfo === false
    ) {
        $errors[] = "Image must be a valid JPG, PNG, or WEBP file.";
        return null;
    }

    $extension   = $allowedTypes[$realType];
    $newFileName = $filenamePrefix . bin2hex(random_bytes(8)) . "." . $extension;

    $uploadDir = "../Images/" . $subdir;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        $errors[] = "There was a problem saving the uploaded image.";
        return null;
    }

    return $subdir . $newFileName;
}
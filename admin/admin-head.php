<?php


header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$pageTitle   = $pageTitle ?? "Admin";
$activePage  = $activePage ?? "";
$extraStyles = $extraStyles ?? [];

?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?> | EDL Gallery Admin</title>
    <link rel="icon" type="image/x-icon" href="../Images/logo.png">

    <link rel="stylesheet" href="admin-layout.css">
    <link rel="stylesheet" href="admin.css">

    <?php foreach ($extraStyles as $extraStyle): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($extraStyle); ?>">
    <?php endforeach; ?>

</head>

<body class="admin-body">

<div class="admin-shell">

    <?php require __DIR__ . "/admin-sidebar.php"; ?>

    <main class="admin-main">

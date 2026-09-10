<?php

session_start();
$basePath = "";

require_once "database/config.php";
require_once "security/authorize.php";


// Only logged-in users may view this page.
if (!isLoggedIn()) {

    header("Location: login.php");
    exit;

}


// Always use the user_id from the session -
// never trust a value from the URL or form.
$userId = $_SESSION["user_id"];


// Get only inquiries that belong to this user.
$sql = "SELECT *
        FROM exhibition_inquiries
        WHERE user_id = :user_id
        ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$stmt->execute();

$inquiries = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Inquiries | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php require_once "includes/header.php"; ?>


<!-- =========================================
     PAGE HERO
========================================= -->

<section class="dashboard-hero" style="background-image:
    linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)),
    url('image/background-4.jpg');">

    <div class="dashboard-hero-content">

        <p class="dashboard-label">EDL GALLERY</p>

        <h1>
            My <span>Exhibition Inquiries</span>
        </h1>

        <p class="dashboard-description">
            Track the status of every exhibition inquiry you have
            submitted to EDL Gallery.
        </p>

        <a href="rent-space.php" class="dashboard-main-button">
            SUBMIT A NEW INQUIRY
        </a>

    </div>

</section>


<!-- =========================================
     INQUIRIES LIST
========================================= -->

<section class="dashboard-content">

    <div class="dashboard-heading">
        <p>YOUR REQUESTS</p>
        <h2>Exhibition Inquiries</h2>
    </div>


    <?php if (empty($inquiries)): ?>

        <!-- EMPTY STATE -->

        <div class="empty-dashboard">

            <h3>No Exhibition Inquiries Yet</h3>

            <p>
                You haven't submitted an exhibition inquiry yet.
                Start one to bring your exhibition to EDL Gallery.
            </p>

            <a href="rent-space.php" class="dashboard-outline-button">
                RENT OUR SPACE
            </a>

        </div>

    <?php else: ?>

        <?php foreach ($inquiries as $inquiry): ?>

            <div class="inquiry-card">

                <div class="inquiry-card-row">

                    <div class="inquiry-main">

                        <p class="inquiry-type">
                            <?php echo htmlspecialchars($inquiry["exhibition_type"]); ?>
                        </p>

                        <h3>
                            <?php echo htmlspecialchars($inquiry["exhibition_title"]); ?>
                        </h3>

                        <p class="inquiry-meta">
                            Proposed dates:
                            <?php
                            echo date("F j, Y", strtotime($inquiry["proposed_start_date"]));
                            echo " &ndash; ";
                            echo date("F j, Y", strtotime($inquiry["proposed_end_date"]));
                            ?>
                        </p>

                        <p class="inquiry-meta">
                            Submitted on:
                            <?php echo date("F j, Y", strtotime($inquiry["created_at"])); ?>
                        </p>

                    </div>


                    <div class="inquiry-status">

                        <span class="status-label">STATUS</span>

                        <?php if ($inquiry["status"] === "pending"): ?>

                            <strong class="status-pending">PENDING</strong>

                        <?php elseif ($inquiry["status"] === "approved"): ?>

                            <strong class="status-approved">APPROVED</strong>

                        <?php elseif ($inquiry["status"] === "rejected"): ?>

                            <strong class="status-rejected">REJECTED</strong>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- APPROVED MESSAGE -->

                <?php if ($inquiry["status"] === "approved"): ?>

                    <div class="inquiry-success-note">

                        <p>
                            <strong>Your inquiry has been approved!</strong>
                            You may now proceed to complete your exhibition
                            details and submit your artists and artworks.
                        </p>

                    </div>

                <?php endif; ?>


                <!-- REJECTED MESSAGE + ADMIN NOTE -->

                <?php if ($inquiry["status"] === "rejected"): ?>

                    <div class="inquiry-note">

                        <p>
                            <strong>This inquiry was not approved.</strong>

                            <?php if (!empty($inquiry["admin_note"])): ?>

                                <br>
                                Admin note:
                                <?php echo nl2br(htmlspecialchars($inquiry["admin_note"])); ?>

                            <?php else: ?>

                                <br>
                                No additional note was provided by the admin.

                            <?php endif; ?>

                        </p>

                    </div>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</section>


<?php require_once "includes/footer.php"; ?>

</body>
</html>

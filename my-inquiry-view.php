<?php

session_start();

require_once "database/config.php";
require_once "security/authorize.php";
require_once "security/shield.php";


// Only logged-in USERS may view this page.
if (!isUser()) {

    header("Location: login.php");
    exit;

}


// The user's identity always comes from the session.
$userId = $_SESSION["user_id"];


// ---- GET THE INQUIRY ID FROM THE URL ----

$inquiryId = $_GET["id"] ?? "";

if (!ctype_digit((string)$inquiryId)) {

    header("Location: my-inquiries.php");
    exit;

}

$inquiryId = (int)$inquiryId;


// ---- FETCH THE INQUIRY, BUT ONLY IF IT BELONGS TO THIS USER ----

$sql = "SELECT *
        FROM exhibition_inquiries
        WHERE id = :id
          AND user_id = :user_id
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $inquiryId, PDO::PARAM_INT);
$stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$stmt->execute();

$inquiry = $stmt->fetch();

if (!$inquiry) {
    header("Location: my-inquiries.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Inquiry Details | EDL Gallery</title>

    <link
        rel="icon"
        type="image/x-icon"
        href="Images/logo.png"
    >

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<?php require_once "includes/header.php"; ?>


<section class="dashboard-content" style="padding-top: 60px;">

    <a href="my-inquiries.php" class="back-dashboard-link">
        &larr; Back to My Inquiries
    </a>

    <div class="dashboard-heading">

        <p>YOUR INQUIRY</p>

        <h2>
            <?php echo htmlspecialchars($inquiry["exhibition_title"]); ?>
        </h2>

    </div>


    <div class="account-information">

        <div class="account-row">
            <span>STATUS</span>
            <strong>
                <?php if ($inquiry["status"] === "pending"): ?>

                    <span class="status-pending">PENDING</span>

                <?php elseif ($inquiry["status"] === "approved"): ?>

                    <span class="status-approved">APPROVED</span>

                <?php elseif ($inquiry["status"] === "rejected"): ?>

                    <span class="status-rejected">REJECTED</span>

                <?php endif; ?>
            </strong>
        </div>

        <div class="account-row">
            <span>EXHIBITION TYPE</span>
            <strong>
                <?php echo htmlspecialchars($inquiry["exhibition_type"]); ?>
            </strong>
        </div>

        <div class="account-row">
            <span>ORGANIZATION / ARTIST</span>
            <strong>
                <?php echo htmlspecialchars($inquiry["organization_name"]); ?>
            </strong>
        </div>

        <div class="account-row">
            <span>PHONE</span>
            <strong>
                <?php echo htmlspecialchars($inquiry["phone"]); ?>
            </strong>
        </div>

        <div class="account-row">
            <span>PROPOSED DATES</span>
            <strong>
                <?php
                echo date("F j, Y", strtotime($inquiry["proposed_start_date"]));
                echo " &ndash; ";
                echo date("F j, Y", strtotime($inquiry["proposed_end_date"]));
                ?>
            </strong>
        </div>

        <div class="account-row">
            <span>NUMBER OF ARTISTS</span>
            <strong><?php echo (int)$inquiry["artist_count"]; ?></strong>
        </div>

        <div class="account-row">
            <span>EXPECTED ARTWORKS</span>
            <strong><?php echo (int)$inquiry["artwork_count"]; ?></strong>
        </div>

        <div class="account-row">
            <span>EXPECTED VISITORS</span>
            <strong><?php echo (int)$inquiry["expected_visitors"]; ?></strong>
        </div>

        <div class="account-row">
            <span>SUBMITTED ON</span>
            <strong>
                <?php echo date("F j, Y g:i A", strtotime($inquiry["created_at"])); ?>
            </strong>
        </div>

        <?php if (!empty($inquiry["reviewed_at"])): ?>

            <div class="account-row">
                <span>REVIEWED ON</span>
                <strong>
                    <?php echo date("F j, Y g:i A", strtotime($inquiry["reviewed_at"])); ?>
                </strong>
            </div>

        <?php endif; ?>

    </div>


    <div class="admin-text-block">
        <h4>DESCRIPTION</h4>
        <p><?php echo nl2br(htmlspecialchars($inquiry["description"])); ?></p>
    </div>


    <?php if (!empty($inquiry["special_requirements"])): ?>

        <div class="admin-text-block">
            <h4>SPECIAL REQUIREMENTS</h4>
            <p><?php echo nl2br(htmlspecialchars($inquiry["special_requirements"])); ?></p>
        </div>

    <?php endif; ?>


    <?php if (!empty($inquiry["additional_notes"])): ?>

        <div class="admin-text-block">
            <h4>ADDITIONAL NOTES</h4>
            <p><?php echo nl2br(htmlspecialchars($inquiry["additional_notes"])); ?></p>
        </div>

    <?php endif; ?>


    <?php if (!empty($inquiry["admin_note"])): ?>

        <div class="admin-text-block">
            <h4>ADMIN NOTE</h4>
            <p><?php echo nl2br(htmlspecialchars($inquiry["admin_note"])); ?></p>
        </div>

    <?php endif; ?>


    <?php if ($inquiry["status"] === "approved"): ?>

        <div class="inquiry-success-note">

            <p>
                <strong>Your inquiry has been approved.</strong>
                You can now proceed to manage your exhibition and
                submit artists and artworks.
            </p>

            <p style="margin-top:12px;">
                <a
                    href="my-exhibitions.php"
                    class="dashboard-outline-button"
                >
                    GO TO MY EXHIBITIONS
                </a>
            </p>

        </div>

    <?php endif; ?>


    <?php if ($inquiry["status"] === "rejected"): ?>

        <div class="inquiry-note">

            <p>
                <strong>This inquiry was not approved.</strong>

                <?php if (!empty($inquiry["admin_note"])): ?>

                    <br>
                    Reason:
                    <?php echo nl2br(htmlspecialchars($inquiry["admin_note"])); ?>

                <?php endif; ?>
            </p>

        </div>

    <?php endif; ?>

</section>


<?php require_once "includes/footer.php"; ?>

</body>

</html>
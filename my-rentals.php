<?php

$basePath = "";

require_once "database/config.php";
require_once "security/authorize.php";

if (!isUser()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];

$stmt = $pdo->prepare("
    SELECT
        ra.id,
        ra.exhibition_title,
        ra.preferred_start_date,
        ra.preferred_end_date,
        ra.status,
        ra.created_at,
        rp.amount,
        rp.due_date,
        rp.status AS payment_status
    FROM rental_applications ra
    LEFT JOIN rental_payments rp ON rp.rental_application_id = ra.id
    WHERE ra.user_id = :user_id
    ORDER BY ra.created_at DESC
");

$stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$stmt->execute();

$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rentals | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php require_once "includes/header.php"; ?>

<section class="dashboard-hero dashboard-hero--inquiries">
    <div class="dashboard-hero-content">
        <p class="dashboard-label">EDL GALLERY</p>
        <h1>My <span>Rental Applications</span></h1>
        <p class="dashboard-description">
            Track the status of every space rental request you've submitted.
        </p>
        <a href="apply-for-rental.php" class="dashboard-main-button">
            SUBMIT A NEW APPLICATION
        </a>
    </div>
</section>

<section class="dashboard-content">

    <a href="dashboard.php" class="back-dashboard-link">
        &larr; Back to Dashboard
    </a>

    <div class="dashboard-heading">
        <p>YOUR BOOKINGS</p>
        <h2>Rental Applications</h2>
    </div>

    <?php if (empty($applications)): ?>

        <div class="empty-dashboard">
            <h3>No Rental Applications Yet</h3>
            <p>You haven't submitted a rental application yet.</p>
            <a href="apply-for-rental.php" class="dashboard-outline-button">
                APPLY FOR RENTAL
            </a>
        </div>

    <?php else: ?>

        <?php foreach ($applications as $app): ?>

            <div class="inquiry-card">
                <div class="inquiry-card-row">

                    <div class="inquiry-main">
                        <p class="inquiry-type">RENTAL APPLICATION</p>

                        <h3><?php echo htmlspecialchars($app["exhibition_title"]); ?></h3>

                        <p class="inquiry-meta">
                            Dates:
                            <?php echo date("M j, Y", strtotime($app["preferred_start_date"])); ?>
                            &ndash;
                            <?php echo date("M j, Y", strtotime($app["preferred_end_date"])); ?>
                        </p>

                        <p class="inquiry-meta">
                            Submitted on <?php echo date("F j, Y", strtotime($app["created_at"])); ?>
                        </p>

                        <?php if ($app["amount"] !== null): ?>
                            <p class="inquiry-meta">
                                <strong>Rental Fee:</strong>
                                ₱<?php echo number_format((float)$app["amount"], 2); ?>
                                <?php if ($app["due_date"]): ?>
                                    &middot; Due <?php echo date("M j, Y", strtotime($app["due_date"])); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="inquiry-status">

                        <span class="status-label">APPLICATION</span>
                        <strong class="status-<?php echo htmlspecialchars($app["status"]); ?>">
                            <?php echo strtoupper($app["status"]); ?>
                        </strong>

                        <?php if ($app["payment_status"]): ?>
                            <span class="status-label" style="margin-top:12px;">PAYMENT</span>
                            <strong class="status-<?php echo $app["payment_status"] === "paid" ? "approved" : ($app["payment_status"] === "overdue" ? "rejected" : "pending"); ?>">
                                <?php echo strtoupper($app["payment_status"]); ?>
                            </strong>
                        <?php endif; ?>

                        <a href="my-rental-view.php?id=<?php echo (int)$app["id"]; ?>" class="admin-view-button">
                            VIEW
                        </a>
                    </div>

                </div>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</section>

<?php require_once "includes/footer.php"; ?>

</body>
</html>
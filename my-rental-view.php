<?php

$basePath = "";

require_once "database/config.php";
require_once "security/authorize.php";

if (!isUser()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];
$rentalId = $_GET["id"] ?? "";

if (!ctype_digit((string)$rentalId)) {
    header("Location: my-rentals.php");
    exit;
}

$rentalId = (int)$rentalId;

$stmt = $pdo->prepare("
    SELECT
        ra.*,
        rp.amount, rp.due_date, rp.status AS payment_status,
        rp.method, rp.reference, rp.notes AS payment_notes, rp.paid_at
    FROM rental_applications ra
    LEFT JOIN rental_payments rp ON rp.rental_application_id = ra.id
    WHERE ra.id = :id AND ra.user_id = :user_id
    LIMIT 1
");
$stmt->bindValue(":id", $rentalId, PDO::PARAM_INT);
$stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$stmt->execute();

$app = $stmt->fetch();

if (!$app) {
    header("Location: my-rentals.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Details | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php require_once "includes/header.php"; ?>

<section class="dashboard-content rental-details-page">

    <a href="my-rentals.php" class="back-dashboard-link">
        &larr; Back to My Rentals
    </a>

    <div class="dashboard-heading">
        <p>RENTAL APPLICATION</p>
        <h2><?php echo htmlspecialchars($app["exhibition_title"]); ?></h2>
    </div>

    <div class="account-information">

        <div class="account-row">
            <span>APPLICATION STATUS</span>
            <strong class="status-<?php echo htmlspecialchars($app["status"]); ?>">
                <?php echo strtoupper($app["status"]); ?>
            </strong>
        </div>

        <div class="account-row">
            <span>REQUESTED DATES</span>
            <strong>
                <?php echo date("F j, Y", strtotime($app["preferred_start_date"])); ?>
                &ndash;
                <?php echo date("F j, Y", strtotime($app["preferred_end_date"])); ?>
            </strong>
        </div>

        <div class="account-row">
            <span>SUBMITTED ON</span>
            <strong><?php echo date("F j, Y g:i A", strtotime($app["created_at"])); ?></strong>
        </div>

    </div>

    <div class="admin-text-block">
        <h4>YOUR EXHIBITION DESCRIPTION</h4>
        <?php if (!empty($app["exhibition_description"])): ?>
            <p><?php echo nl2br(htmlspecialchars($app["exhibition_description"])); ?></p>
        <?php else: ?>
            <p class="muted-empty">No description was provided.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($app["admin_message"])): ?>
        <div class="admin-text-block">
            <h4>MESSAGE FROM THE GALLERY</h4>
            <p><?php echo nl2br(htmlspecialchars($app["admin_message"])); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($app["amount"] !== null): ?>

        <div class="rental-payment-card">

            <div class="rental-payment-header">
                <p class="rental-payment-label">RENTAL PAYMENT</p>
                <span class="rental-payment-status status-<?php
                    echo $app["payment_status"] === "paid" ? "approved"
                       : ($app["payment_status"] === "overdue" ? "rejected" : "pending");
                ?>">
                    <?php echo strtoupper($app["payment_status"]); ?>
                </span>
            </div>

            <div class="rental-payment-amount">
                <span class="rental-payment-amount-label">AMOUNT DUE</span>
                <strong class="rental-payment-amount-value">
                    ₱<?php echo number_format((float)$app["amount"], 2); ?>
                </strong>
            </div>

            <div class="rental-payment-meta">

                <?php if ($app["due_date"]): ?>
                    <div class="rental-payment-meta-item">
                        <span>DUE DATE</span>
                        <strong><?php echo date("F j, Y", strtotime($app["due_date"])); ?></strong>
                    </div>
                <?php endif; ?>

                <?php if ($app["payment_status"] === "paid"): ?>

                    <div class="rental-payment-meta-item">
                        <span>PAID ON</span>
                        <strong><?php echo date("F j, Y", strtotime($app["paid_at"])); ?></strong>
                    </div>

                    <?php if ($app["method"]): ?>
                        <div class="rental-payment-meta-item">
                            <span>METHOD</span>
                            <strong><?php echo htmlspecialchars($app["method"]); ?></strong>
                        </div>
                    <?php endif; ?>

                    <?php if ($app["reference"]): ?>
                        <div class="rental-payment-meta-item">
                            <span>REFERENCE</span>
                            <strong><?php echo htmlspecialchars($app["reference"]); ?></strong>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

            </div>

            <?php if ($app["payment_status"] === "unpaid" || $app["payment_status"] === "overdue"): ?>
                <div class="rental-payment-note">
                    <p>
                        Please pay the rental fee by the due date. Contact the gallery
                        to arrange payment — cash, bank transfer, or GCash are accepted.
                        The admin will mark your payment as received once confirmed.
                    </p>
                </div>
            <?php elseif ($app["payment_status"] === "paid"): ?>
                <div class="rental-payment-note rental-payment-note--paid">
                    <p>
                        <strong>Payment received.</strong> Thank you — your booking is confirmed.
                    </p>
                </div>
            <?php endif; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once "includes/footer.php"; ?>

</body>
</html>
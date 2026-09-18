<?php

$basePath = "../";
define('EDL_ADMIN', true);

require_once "../database/config.php";
require_once "../security/authorize.php";
require_once "../security/shield.php";
require_once "admin-includes/helpers.php";

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$statusFilter = $_GET["status"] ?? "all";
$allowed = ["all", "pending", "approved", "rejected", "cancelled"];
if (!in_array($statusFilter, $allowed, true)) {
    $statusFilter = "all";
}

$sql = "SELECT ra.*, u.first_name, u.last_name, u.email,
               rp.amount, rp.status AS payment_status, rp.due_date
        FROM rental_applications ra
        JOIN users u ON u.id = ra.user_id
        LEFT JOIN rental_payments rp ON rp.rental_application_id = ra.id";

if ($statusFilter !== "all") {
    $sql .= " WHERE ra.status = :status";
}

$sql .= " ORDER BY ra.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($statusFilter !== "all") {
    $stmt->bindValue(":status", $statusFilter);
}
$stmt->execute();
$applications = $stmt->fetchAll();

$countStmt = $pdo->query(
    "SELECT status, COUNT(*) AS total FROM rental_applications GROUP BY status"
);
$counts = ["pending" => 0, "approved" => 0, "rejected" => 0, "cancelled" => 0];
foreach ($countStmt->fetchAll() as $row) {
    $counts[$row["status"]] = (int)$row["total"];
}
$total = array_sum($counts);

$pageTitle  = "Rental Applications";
$activePage = "rentals";
require_once "admin-head.php";
?>

<section class="admin-page">

    <div class="admin-header">
        <div>
            <p class="admin-label">ADMIN</p>
            <h1>Rental Applications</h1>
            <p class="admin-subtext">
                Review space rental requests and track their payments.
            </p>
        </div>
    </div>

    <div class="admin-tabs">
        <a href="rentals.php?status=all" class="admin-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
            ALL (<?php echo $total; ?>)
        </a>
        <a href="rentals.php?status=pending" class="admin-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
            PENDING (<?php echo $counts['pending']; ?>)
        </a>
        <a href="rentals.php?status=approved" class="admin-tab <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
            APPROVED (<?php echo $counts['approved']; ?>)
        </a>
        <a href="rentals.php?status=rejected" class="admin-tab <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
            REJECTED (<?php echo $counts['rejected']; ?>)
        </a>
        <a href="rentals.php?status=cancelled" class="admin-tab <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
            CANCELLED (<?php echo $counts['cancelled']; ?>)
        </a>
    </div>

    <?php if (empty($applications)): ?>

        <div class="empty-dashboard">
            <h3>No Rental Applications</h3>
            <p>There are no rental applications in this category.</p>
        </div>

    <?php else: ?>

        <?php foreach ($applications as $app): ?>

            <div class="inquiry-card">
                <div class="inquiry-card-row">

                    <div class="inquiry-main">
                        <p class="inquiry-type">
                            Requested by
                            <?php echo htmlspecialchars($app["first_name"] . " " . $app["last_name"]); ?>
                            (<?php echo htmlspecialchars($app["email"]); ?>)
                        </p>
                        <h3><?php echo htmlspecialchars($app["exhibition_title"]); ?></h3>
                        <p class="inquiry-meta">
                            <?php echo date("M j, Y", strtotime($app["preferred_start_date"])); ?>
                            &ndash;
                            <?php echo date("M j, Y", strtotime($app["preferred_end_date"])); ?>
                        </p>
                        <?php if ($app["amount"] !== null): ?>
                            <p class="inquiry-meta">
                                <strong>Fee:</strong> ₱<?php echo number_format((float)$app["amount"], 2); ?>
                                &middot; Payment: <?php echo strtoupper($app["payment_status"]); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="inquiry-status">
                        <span class="status-label">STATUS</span>
                        <strong class="status-<?php echo htmlspecialchars($app["status"]); ?>">
                            <?php echo strtoupper($app["status"]); ?>
                        </strong>
                        <a href="rental-view.php?id=<?php echo (int)$app["id"]; ?>" class="admin-view-button">
                            VIEW
                        </a>
                    </div>

                </div>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</section>

<?php

session_start();
$basePath = "../";

require_once "../database/config.php";
require_once "../security/authorize.php";


// Only logged-in ADMIN users may view this page.
// NOTE: this assumes security/authorize.php provides an isAdmin()
// function (matching your users.role column: 'user' / 'admin').
// If your authorize.php uses a different function name, just
// swap it in here.
if (!isLoggedIn() || !isAdmin()) {

    header("Location: ../login.php");
    exit;

}


// ---- STATUS FILTER (tabs) ----

$allowedStatuses = ["all", "pending", "approved", "rejected"];

$statusFilter = $_GET["status"] ?? "all";

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "all";
}


// ---- FETCH INQUIRIES ----

if ($statusFilter === "all") {

    $sql = "SELECT ei.*, u.first_name, u.last_name, u.email
            FROM exhibition_inquiries ei
            JOIN users u ON u.id = ei.user_id
            ORDER BY ei.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

} else {

    $sql = "SELECT ei.*, u.first_name, u.last_name, u.email
            FROM exhibition_inquiries ei
            JOIN users u ON u.id = ei.user_id
            WHERE ei.status = :status
            ORDER BY ei.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":status", $statusFilter);
    $stmt->execute();

}

$inquiries = $stmt->fetchAll();


// ---- COUNTS FOR TABS ----

$countStmt = $pdo->query(
    "SELECT status, COUNT(*) AS total
     FROM exhibition_inquiries
     GROUP BY status"
);

$counts = [
    "pending"  => 0,
    "approved" => 0,
    "rejected" => 0,
];

foreach ($countStmt->fetchAll() as $row) {
    $counts[$row["status"]] = (int)$row["total"];
}

$totalCount = array_sum($counts);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Exhibition Inquiries | EDL Gallery Admin</title>
    <link rel="icon" type="image/x-icon" href="../Images/logo.png">
    <link rel="stylesheet" href="../style.css">
</head>

<body>

<?php require_once "../includes/header.php"; ?>


<section class="admin-page">

    <div class="admin-header">

        <p class="admin-label">ADMIN</p>

        <h1>Exhibition Inquiries</h1>

        <p class="admin-subtext">
            Review, approve, or reject exhibition inquiries
            submitted by users.
        </p>

    </div>


    <!-- STATUS TABS -->

    <div class="admin-tabs">

        <a
            href="inquiries.php?status=all"
            class="admin-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>"
        >
            ALL (<?php echo $totalCount; ?>)
        </a>

        <a
            href="inquiries.php?status=pending"
            class="admin-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>"
        >
            PENDING (<?php echo $counts['pending']; ?>)
        </a>

        <a
            href="inquiries.php?status=approved"
            class="admin-tab <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>"
        >
            APPROVED (<?php echo $counts['approved']; ?>)
        </a>

        <a
            href="inquiries.php?status=rejected"
            class="admin-tab <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>"
        >
            REJECTED (<?php echo $counts['rejected']; ?>)
        </a>

    </div>


    <!-- INQUIRIES LIST -->

    <?php if (empty($inquiries)): ?>

        <div class="empty-dashboard">

            <h3>No Inquiries Found</h3>

            <p>
                There are no exhibition inquiries in this category
                right now.
            </p>

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
                            Requested by
                            <?php
                            echo htmlspecialchars(
                                $inquiry["first_name"] . " " . $inquiry["last_name"]
                            );
                            ?>
                            (<?php echo htmlspecialchars($inquiry["email"]); ?>)
                        </p>

                        <p class="inquiry-meta">
                            Submitted on
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

                        <a
                            href="inquiry-view.php?id=<?php echo (int)$inquiry['id']; ?>"
                            class="admin-view-button"
                        >
                            VIEW
                        </a>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</section>


<?php require_once "../includes/footer.php"; ?>

</body>
</html>

<?php

session_start();
$basePath = "../";

require_once "../database/config.php";
require_once "../security/authorize.php";


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


// ---- FETCH ARTWORKS ----

$baseSql = "SELECT aw.*, e.title AS exhibition_title, a.name AS artist_name
            FROM artworks aw
            JOIN exhibitions e ON e.id = aw.exhibition_id
            JOIN artists a ON a.id = aw.artist_id";

if ($statusFilter === "all") {

    $stmt = $pdo->prepare($baseSql . " ORDER BY aw.created_at DESC");
    $stmt->execute();

} else {

    $stmt = $pdo->prepare($baseSql . " WHERE aw.status = :status ORDER BY aw.created_at DESC");
    $stmt->bindValue(":status", $statusFilter);
    $stmt->execute();

}

$artworks = $stmt->fetchAll();


// ---- COUNTS FOR TABS ----

$countStmt = $pdo->query(
    "SELECT status, COUNT(*) AS total FROM artworks GROUP BY status"
);

$counts = ["pending" => 0, "approved" => 0, "rejected" => 0];

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

    <title>Artworks | EDL Gallery Admin</title>
    <link rel="icon" type="image/x-icon" href="../Images/logo.png">
    <link rel="stylesheet" href="../style.css">
</head>

<body>

<?php require_once "../includes/header.php"; ?>


<section class="admin-page">

    <div class="admin-header">

        <p class="admin-label">ADMIN</p>

        <h1>Artworks</h1>

        <p class="admin-subtext">
            Review artworks submitted for upcoming exhibitions.
        </p>

    </div>


    <div class="admin-tabs">

        <a href="artworks.php?status=all"
        class="admin-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
            ALL (<?php echo $totalCount; ?>)
        </a>

        <a href="artworks.php?status=pending"
        class="admin-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
            PENDING (<?php echo $counts['pending']; ?>)
        </a>

        <a href="artworks.php?status=approved"
        class="admin-tab <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
            APPROVED (<?php echo $counts['approved']; ?>)
        </a>

        <a href="artworks.php?status=rejected"
        class="admin-tab <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
            REJECTED (<?php echo $counts['rejected']; ?>)
        </a>

    </div>


    <?php if (empty($artworks)): ?>

        <div class="empty-dashboard">
            <h3>No Artworks Found</h3>
            <p>There are no artworks in this category right now.</p>
        </div>

    <?php else: ?>

        <?php foreach ($artworks as $artwork): ?>

            <div class="inquiry-card">

                <div class="inquiry-card-row">

                    <div class="inquiry-main">

                        <p class="inquiry-type">
                            <?php echo htmlspecialchars($artwork["exhibition_title"]); ?>
                            &middot;
                            <?php echo htmlspecialchars($artwork["artist_name"]); ?>
                        </p>

                        <h3><?php echo htmlspecialchars($artwork["title"]); ?></h3>

                        <p class="inquiry-meta">
                            Submitted on
                            <?php echo date("F j, Y", strtotime($artwork["created_at"])); ?>
                        </p>

                    </div>


                    <div class="inquiry-status">

                        <span class="status-label">STATUS</span>

                        <strong class="status-<?php echo htmlspecialchars($artwork['status']); ?>">
                            <?php echo strtoupper($artwork["status"]); ?>
                        </strong>

                        <a
                            href="artwork-view.php?id=<?php echo (int)$artwork['id']; ?>"
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

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

$allowedStatuses = [
    "all", "draft", "artwork_submission",
    "ready_to_publish", "published", "completed", "cancelled"
];

$statusFilter = $_GET["status"] ?? "all";

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "all";
}


// ---- FETCH EXHIBITIONS ----

if ($statusFilter === "all") {

    $sql = "SELECT e.*, u.first_name, u.last_name
            FROM exhibitions e
            JOIN users u ON u.id = e.organizer_id
            ORDER BY e.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

} else {

    $sql = "SELECT e.*, u.first_name, u.last_name
            FROM exhibitions e
            JOIN users u ON u.id = e.organizer_id
            WHERE e.status = :status
            ORDER BY e.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":status", $statusFilter);
    $stmt->execute();

}

$exhibitions = $stmt->fetchAll();


// ---- COUNTS FOR TABS ----

$countStmt = $pdo->query(
    "SELECT status, COUNT(*) AS total FROM exhibitions GROUP BY status"
);

$counts = [
    "draft" => 0, "artwork_submission" => 0, "ready_to_publish" => 0,
    "published" => 0, "completed" => 0, "cancelled" => 0,
];

foreach ($countStmt->fetchAll() as $row) {
    $counts[$row["status"]] = (int)$row["total"];
}

$totalCount = array_sum($counts);


function formatStatusLabel($status) {

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

$pageTitle   = "Exhibitions";
$activePage  = "exhibitions";
$extraStyles = ["exhibition-create.css"];
require_once "admin-head.php";
?>

    <link rel="stylesheet" href="admin.css">
<section class="admin-page">

    <div class="admin-header">

    <div>

        <p class="admin-label">ADMIN</p>

        <h1>Exhibitions</h1>

        <p class="admin-subtext">
            Track every exhibition from setup through to publishing.
        </p>

    </div>

</div>


    <div class="admin-tabs">

        <a href="exhibitions.php?status=all"
            class="admin-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
            ALL (<?php echo $totalCount; ?>)
        </a>

        <a href="exhibitions.php?status=draft"
            class="admin-tab <?php echo $statusFilter === 'draft' ? 'active' : ''; ?>">
            DRAFT (<?php echo $counts['draft']; ?>)
        </a>

        <a href="exhibitions.php?status=artwork_submission"
            class="admin-tab <?php echo $statusFilter === 'artwork_submission' ? 'active' : ''; ?>">
            ARTWORK SUBMISSION (<?php echo $counts['artwork_submission']; ?>)
        </a>

        <a href="exhibitions.php?status=ready_to_publish"
            class="admin-tab <?php echo $statusFilter === 'ready_to_publish' ? 'active' : ''; ?>">
            READY TO PUBLISH (<?php echo $counts['ready_to_publish']; ?>)
        </a>

        <a href="exhibitions.php?status=published"
            class="admin-tab <?php echo $statusFilter === 'published' ? 'active' : ''; ?>">
            PUBLISHED (<?php echo $counts['published']; ?>)
        </a>

        <a href="exhibitions.php?status=completed"
            class="admin-tab <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
            COMPLETED (<?php echo $counts['completed']; ?>)
        </a>

        <a href="exhibitions.php?status=cancelled"
            class="admin-tab <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
            CANCELLED (<?php echo $counts['cancelled']; ?>)
        </a>

    </div>


    <?php if (empty($exhibitions)): ?>

        <div class="empty-dashboard">
            <h3>No Exhibitions Found</h3>
            <p>There are no exhibitions in this category right now.</p>
        </div>

    <?php else: ?>

        <?php foreach ($exhibitions as $exhibition): ?>

            <div class="inquiry-card">

                <div class="inquiry-card-row">

                    <div class="inquiry-main">

                        <p class="inquiry-type">
                            Organized by
                            <?php
                            echo htmlspecialchars(
                                $exhibition["first_name"] . " " . $exhibition["last_name"]
                            );
                            ?>
                        </p>

                        <h3><?php echo htmlspecialchars($exhibition["title"]); ?></h3>

                        <p class="inquiry-meta">
                            <?php
                            echo date("F j, Y", strtotime($exhibition["start_date"]));
                            echo " &ndash; ";
                            echo date("F j, Y", strtotime($exhibition["end_date"]));
                            ?>
                        </p>

                    </div>


                    <div class="inquiry-status">

                        <span class="status-label">STATUS</span>

                        <strong class="status-<?php echo htmlspecialchars($exhibition['status']); ?>">
                            <?php echo formatStatusLabel($exhibition["status"]); ?>
                        </strong>

                        <a
                            href="exhibition-view.php?id=<?php echo (int)$exhibition['id']; ?>"
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

<?php require_once "admin-foot.php"; ?>
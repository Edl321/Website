<?php

session_start();
$basePath = "../";

define('EDL_ADMIN', true);

require_once "../database/config.php";
require_once "../security/authorize.php";
require_once "admin-includes/helpers.php";


if (!isLoggedIn() || !isAdmin()) {

    header("Location: ../login.php");
    exit;

}


// ---- QUICK STATS ----

$pendingInquiries = $pdo->query(
    "SELECT COUNT(*) FROM exhibition_inquiries WHERE status = 'pending'"
)->fetchColumn();

$pendingRentalApplications = $pdo->query(
    "SELECT COUNT(*) FROM rental_applications WHERE status = 'pending'"
)->fetchColumn();

$pendingArtworks = $pdo->query(
    "SELECT COUNT(*) FROM artworks WHERE status = 'pending'"
)->fetchColumn();

$activeExhibitions = $pdo->query(
    "SELECT COUNT(*) FROM exhibitions
    WHERE status IN ('draft', 'artwork_submission', 'ready_to_publish')"
)->fetchColumn();

$publishedExhibitions = $pdo->query(
    "SELECT COUNT(*) FROM exhibitions WHERE status = 'published'"
)->fetchColumn();

$totalArtists = $pdo->query("SELECT COUNT(*) FROM artists")->fetchColumn();

$totalUsers = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'user'"
)->fetchColumn();

$pageTitle  = "Dashboard";
$activePage = "dashboard";
require_once "admin-head.php";
?>

<section class="admin-page">

    <div class="admin-header">

        <div>

            <p class="admin-label">ADMIN</p>

            <h1>
                Welcome back,
                <?php echo htmlspecialchars($_SESSION["first_name"]); ?>
            </h1>

        </div>

    </div>


    <div class="admin-stats-grid">

        <a href="inquiries.php?status=pending" class="admin-stat-card">
            <span class="admin-stat-number"><?php echo $pendingInquiries; ?></span>
            <span class="admin-stat-label">PENDING INQUIRIES</span>
        </a>

        <a href="rental-application.php?status=pending" class="admin-stat-card">
            <span class="admin-stat-number"><?php echo $pendingRentalApplications; ?></span>
            <span class="admin-stat-label">PENDING RENTAL APPLICATIONS</span>
        </a>

        <a href="artworks.php?status=pending" class="admin-stat-card">
            <span class="admin-stat-number"><?php echo $pendingArtworks; ?></span>
            <span class="admin-stat-label">PENDING ARTWORKS</span>
        </a>

        <a href="exhibitions.php" class="admin-stat-card">
            <span class="admin-stat-number"><?php echo $activeExhibitions; ?></span>
            <span class="admin-stat-label">EXHIBITIONS IN PROGRESS</span>
        </a>

        <a href="exhibitions.php?status=published" class="admin-stat-card">
            <span class="admin-stat-number"><?php echo $publishedExhibitions; ?></span>
            <span class="admin-stat-label">PUBLISHED EXHIBITIONS</span>
        </a>

        <a href="artists.php" class="admin-stat-card">
            <span class="admin-stat-number"><?php echo $totalArtists; ?></span>
            <span class="admin-stat-label">ARTISTS</span>
        </a>

        <a href="users.php" class="admin-stat-card">
            <span class="admin-stat-number"><?php echo $totalUsers; ?></span>
            <span class="admin-stat-label">REGISTERED USERS</span>
        </a>

    </div>


    <!-- QUICK ACTIONS -->

    <div class="admin-header">
        <p class="admin-label">MANAGE</p>
    </div>

    <div class="admin-quick-actions">

        <a href="rental-application.php" class="admin-quick-action">
            <h3>Rental Applications</h3>
            <p>Review exhibition rental applications submitted by users.</p>
        </a>

        <a href="inquiries.php" class="admin-quick-action">
            <h3>Exhibition Inquiries</h3>
            <p>Review and approve or reject rental requests.</p>
        </a>

        <a href="exhibitions.php" class="admin-quick-action">
            <h3>Exhibitions</h3>
            <p>Track exhibitions from setup through to publishing.</p>
        </a>

        <a href="artworks.php" class="admin-quick-action">
            <h3>Artworks</h3>
            <p>Review submitted artworks before they go live.</p>
        </a>

        <a href="artists.php" class="admin-quick-action">
            <h3>Artists</h3>
            <p>Manage the gallery's artist directory.</p>
        </a>

        <a href="users.php" class="admin-quick-action">
            <h3>Users</h3>
            <p>View everyone registered on EDL Gallery.</p>
        </a>

    </div>

</section>

<?php require_once "admin-foot.php"; ?>
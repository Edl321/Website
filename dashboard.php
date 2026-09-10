<?php
session_start();
$basePath = "";
require_once "database/config.php";
require_once "security/authorize.php";

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Public information used by the rental dashboard.
$exhibitionStmt = $pdo->query("SELECT COUNT(*) FROM exhibitions");
$exhibitionCount = $exhibitionStmt->fetchColumn();

$artistStmt = $pdo->query("SELECT COUNT(*) FROM artists");
$artistCount = $artistStmt->fetchColumn();

// Show the nearest upcoming exhibition, if one exists.
$upcomingStmt = $pdo->query(
    "SELECT title, start_date, end_date, image
    FROM exhibitions
    WHERE end_date >= CURDATE()
    ORDER BY start_date ASC
    LIMIT 1"
);
$upcomingExhibition = $upcomingStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | EDL Gallery</title>

    <link rel = "icon" type="image/x-icon" href = "Images/logo.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="user-dashboard.css">
</head>
<body>

<?php require_once "includes/header.php"; ?>

<main class="user-dashboard">

    <section class="user-dashboard-hero">
        <div>
            <p class="user-dashboard-label">EDL GALLERY / USER AREA</p>
            <h1>Welcome back, <span><?php echo htmlspecialchars($_SESSION["first_name"]); ?></span>!</h1>
            <p class="user-dashboard-welcome">
                Manage your exhibition rental journey and keep track of your gallery plans.
            </p>
        </div>

        <div class="user-dashboard-date">
            <strong><?php echo date("F j, Y"); ?></strong>
            <?php echo date("l"); ?>
        </div>
    </section>

    <section class="user-dashboard-stats">
        <div class="user-stat-card">
            <div class="user-stat-icon">▣</div>
            <p>Your Applications</p>
            <h2>0</h2>
        </div>

        <div class="user-stat-card">
            <div class="user-stat-icon">✓</div>
            <p>Application Status</p>
            <h2>Not Applied</h2>
        </div>

        <div class="user-stat-card">
            <div class="user-stat-icon">◫</div>
            <p>Gallery Exhibitions</p>
            <h2><?php echo (int)$exhibitionCount; ?></h2>
        </div>
    </section>

    <div class="user-dashboard-grid">

        <div>
            <section class="rent-cta">
                <div class="rent-cta-content">
                    <p>Rent Our Space</p>
                    <h2>Turn Your Vision Into an Exhibition.</h2>
                    <span>
                        Submit your exhibition idea, choose your preferred date,
                        and let EDL Gallery help bring your work to an audience.
                    </span>
                    <a href="rent-space.php" class="rent-button">Start Rental Application →</a>
                </div>
                <div class="rent-cta-image"></div>
            </section>

            <section class="user-panel">
                <div class="user-panel-title">
                    <div>
                        <p>YOUR RENTAL JOURNEY</p>
                        <h2>How It Works</h2>
                    </div>
                </div>

                <div class="rental-steps">
                    <div class="rental-step">
                        <span class="rental-step-number">01</span>
                        <h3>Submit Application</h3>
                        <p>Provide your exhibition details, preferred dates, and supporting information.</p>
                    </div>

                    <div class="rental-step">
                        <span class="rental-step-number">02</span>
                        <h3>Gallery Review</h3>
                        <p>EDL Gallery reviews your request and checks the requested schedule.</p>
                    </div>

                    <div class="rental-step">
                        <span class="rental-step-number">03</span>
                        <h3>Confirmation</h3>
                        <p>Once approved, your exhibition schedule can be prepared for the gallery.</p>
                    </div>
                </div>
            </section>

            <section class="user-panel">
                <div class="user-panel-title">
                    <div>
                        <p>UPCOMING</p>
                        <h2>Gallery Schedule</h2>
                    </div>
                    <a href="exhibition.php" class="user-panel-link">View Exhibitions →</a>
                </div>

                <?php if ($upcomingExhibition): ?>
                    <div class="application-status">
                        <strong><?php echo htmlspecialchars($upcomingExhibition["title"]); ?></strong>
                        <p>
                            <?php echo date("M j, Y", strtotime($upcomingExhibition["start_date"])); ?>
                            –
                            <?php echo date("M j, Y", strtotime($upcomingExhibition["end_date"])); ?>
                        </p>
                        <span class="status-badge">Upcoming Exhibition</span>
                    </div>
                <?php else: ?>
                    <div class="application-status">
                        <strong>No Upcoming Exhibition</strong>
                        <p>The gallery currently has no scheduled upcoming exhibition.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside>
            <section class="user-panel">
                <div class="user-panel-title">
                    <div>
                        <p>MANAGE</p>
                        <h2>Quick Actions</h2>
                    </div>
                </div>

                <div class="user-actions">
                    <a href="rent-space.php" class="user-action">
                        <div class="user-action-icon">+</div>
                        <div class="user-action-content">
                            <h3>Rent Our Space</h3>
                            <p>Submit a new exhibition rental request.</p>
                        </div>
                        <span class="user-action-arrow">→</span>
                    </a>

                    <a href="exhibition.php" class="user-action">
                        <div class="user-action-icon">□</div>
                        <div class="user-action-content">
                            <h3>View Exhibitions</h3>
                            <p>Check current and upcoming exhibitions.</p>
                        </div>
                        <span class="user-action-arrow">→</span>
                    </a>

                    <a href="artist.php" class="user-action">
                        <div class="user-action-icon">○</div>
                        <div class="user-action-content">
                            <h3>Meet Our Artists</h3>
                            <p>Explore artists featured by EDL Gallery.</p>
                        </div>
                        <span class="user-action-arrow">→</span>
                    </a>

                    <a href="contact.php" class="user-action">
                        <div class="user-action-icon">@</div>
                        <div class="user-action-content">
                            <h3>Contact Gallery</h3>
                            <p>Have a question about renting the space?</p>
                        </div>
                        <span class="user-action-arrow">→</span>
                    </a>
                </div>
            </section>

            <section class="user-panel">
                <div class="user-panel-title">
                    <div>
                        <p>YOUR PROFILE</p>
                        <h2>Account</h2>
                    </div>
                </div>

                <div class="user-account-list">
                    <div class="user-account-row">
                        <span>FIRST NAME</span>
                        <strong><?php echo htmlspecialchars($_SESSION["first_name"]); ?></strong>
                    </div>
                    <div class="user-account-row">
                        <span>LAST NAME</span>
                        <strong><?php echo htmlspecialchars($_SESSION["last_name"]); ?></strong>
                    </div>
                    <div class="user-account-row">
                        <span>EMAIL</span>
                        <strong><?php echo htmlspecialchars($_SESSION["email"]); ?></strong>
                    </div>
                </div>
            </section>

            <section class="user-panel">
                <div class="user-panel-title">
                    <div>
                        <p>GALLERY</p>
                        <h2>About EDL</h2>
                    </div>
                </div>
                <p style="margin:0;color:#666;font-size:14px;line-height:1.7;">
                    EDL Gallery provides a space for artists and groups to present
                    creative work through meaningful exhibitions and experiences.
                </p>
                <a href="about-us.php" class="user-panel-link" style="display:inline-block;margin-top:18px;">Learn More →</a>
            </section>
        </aside>

    </div>
</main>

<?php require_once "includes/footer.php"; ?>

</body>
</html>

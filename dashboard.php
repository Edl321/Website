<?php

$basePath = "";

require_once "security/shield.php";
require_once "database/config.php";
require_once "security/authorize.php";


if (!isUser()) {
    header("Location: login.php");
    exit;
}


$userId = $_SESSION["user_id"];

$inquiryCountStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM exhibition_inquiries
    WHERE user_id = :user_id
");

$inquiryCountStmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$inquiryCountStmt->execute();

$inquiryCount = (int)$inquiryCountStmt->fetchColumn();

$latestInquiryStmt = $pdo->prepare("
    SELECT id, exhibition_title, status, created_at
    FROM exhibition_inquiries
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 1
");

$latestInquiryStmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$latestInquiryStmt->execute();

$latestInquiry = $latestInquiryStmt->fetch();

$latestInquiryStatus = $latestInquiry
    ? $latestInquiry["status"]
    : "Not Applied";

$recentInquiriesStmt = $pdo->prepare("
    SELECT id, exhibition_title, exhibition_type, status, created_at
    FROM exhibition_inquiries
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 3
");

$recentInquiriesStmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$recentInquiriesStmt->execute();

$recentInquiries = $recentInquiriesStmt->fetchAll();

$notificationStmt = $pdo->prepare("
    SELECT id, title, message, is_read, created_at
    FROM notifications
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 3
");

$notificationStmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$notificationStmt->execute();

$notifications = $notificationStmt->fetchAll();


$unreadStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = :user_id
    AND is_read = 0
");

$unreadStmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$unreadStmt->execute();

$unreadCount = (int)$unreadStmt->fetchColumn();

$exhibitionCountStmt = $pdo->query("
    SELECT COUNT(*)
    FROM exhibitions
    WHERE status = 'published'
");

$exhibitionCount = (int)$exhibitionCountStmt->fetchColumn();


$upcomingStmt = $pdo->query("
    SELECT title, start_date, end_date, image
    FROM exhibitions
    WHERE status = 'published'
    AND end_date >= CURDATE()
    ORDER BY start_date ASC
    LIMIT 1
");

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
            <h1>Welcome back, <span><?php echo htmlspecialchars($_SESSION["first_name"]); ?></span></h1>
            <p class="user-dashboard-welcome">
                Submit exhibition inquiries and keep track of your gallery plans.
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
            <p>Your Inquiries</p>
            <h2><?php echo $inquiryCount; ?></h2>
        </div>

        <div class="user-stat-card">
            <div class="user-stat-icon">✓</div>
            <p>Latest Inquiry</p>
            <h2>
                <?php echo htmlspecialchars(ucwords(str_replace("_", " ", $latestInquiryStatus))); ?>
            </h2>
        </div>

        <div class="user-stat-card">
            <div class="user-stat-icon">◫</div>
            <p>Gallery Exhibitions</p>
            <h2><?php echo $exhibitionCount; ?></h2>
        </div>

    </section>

    <div class="user-dashboard-grid">

        <div>

            <section class="rent-cta">

                <div class="rent-cta-content">
                    <p>Rent Our Space</p>
                    <h2>Turn Your Vision Into an Exhibition.</h2>
                    <span>
                        Submit your exhibition idea, choose your preferred dates,
                        and let EDL Gallery help bring your work to an audience.
                    </span>
                    <a href="rent-space.php" class="rent-button">Learn About the Space →</a>
                </div>

                <div class="rent-cta-image"></div>
            </section>


            <section class="user-panel">

                <div class="user-panel-title">

                    <div>
                        <p>UPDATES</p>
                        <h2>Notifications</h2>
                        <?php if ($unreadCount > 0): ?>
                            <a href="notification.php" class="unread-count">
                                <?php echo $unreadCount; ?> UNREAD
                            </a>
                        <?php endif; ?>
                        </div>

                </div>

                <?php if (empty($notifications)): ?>

                    <p>No notifications yet.</p>

                <?php else: ?>

                    <div class="notifications-list">

                        <?php foreach ($notifications as $notification): ?>

                            <div class="notification-item <?php echo ($notification["is_read"] == 0) ? "unread" : ""; ?>">

                                <div class="notification-info">

                                    <h3>
                                        <?php echo htmlspecialchars($notification["title"]); ?>
                                    </h3>

                                    <p>
                                        <?php echo htmlspecialchars($notification["message"]); ?>
                                    </p>

                                    <small>
                                        <?php echo date("F d, Y h:i A",strtotime($notification["created_at"]));?>
                                    </small>

                                    <?php if ($notification["is_read"] == 0): ?>

                                        <form method="POST" action="notification-read.php">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="notification_id" value="<?php echo (int)$notification["id"]; ?>">
                                            <button type="submit" class="mark-read-button">Mark as Read</button>
                                        </form>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <div class="user-panel-title my-applications-title">

                    <div>
                        <p>INQUIRIES</p>
                        <h2>My Inquiries</h2>
                    </div>

                </div>

                <?php if (empty($recentInquiries)): ?>

                    <p>No exhibition inquiries submitted yet.</p>

                <?php else: ?>

                    <div class="applications-list">

                        <?php foreach ($recentInquiries as $inquiry): ?>

                            <div class="application-item">

                                <div class="application-info">

                                    <h3>
                                        <?php echo htmlspecialchars($inquiry["exhibition_title"]); ?>
                                    </h3>

                                    <?php if (!empty($inquiry["exhibition_type"])): ?>
                                        <p>
                                            <strong>Type:</strong>
                                            <?php echo htmlspecialchars($inquiry["exhibition_type"]); ?>
                                        </p>
                                    <?php endif; ?>

                                    <p>
                                        <strong>Submitted:</strong>
                                        <?php echo date("F d, Y",strtotime($inquiry["created_at"]));?>
                                    </p>

                                </div>

                                <div class="application-status">

                                    <span class="status-badge status-<?php echo htmlspecialchars($inquiry["status"]); ?>">
                                        <?php echo htmlspecialchars(strtoupper($inquiry["status"])); ?>
                                    </span>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>


            <div class="rental-steps">

                <div class="rental-step">
                    <span class="rental-step-number">01</span>
                    <h3>Submit an Inquiry</h3>
                    <p>Tell us about your exhibition, preferred dates, and the artists involved.</p>
                </div>

                <div class="rental-step">
                    <span class="rental-step-number">02</span>
                    <h3>Gallery Review</h3>
                    <p>EDL Gallery reviews your request and checks the requested schedule.</p>
                </div>

                <div class="rental-step">
                    <span class="rental-step-number">03</span>
                    <h3>Exhibition Setup</h3>
                    <p>Once approved, add your artists and artworks and prepare your show.</p>
                </div>

            </div>

        </div>

        <aside>


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


            <section class="user-panel">

                <div class="user-panel-title">

                    <div>

                        <p>MANAGE</p>
                        <h2>Quick Actions</h2>
                    </div>

                </div>

                <div class="user-actions">

                    <a href="my-rentals.php" class="user-action">

                        <div class="user-action-icon">▤</div>
                        <div class="user-action-content">
                        <h3>My Rental Applications</h3>
                        <p>Track your space rental bookings.</p>
                        </div>
                        
                        <span class="user-action-arrow">→</span>
                    </a>

                    <a href="rent-space.php" class="user-action">

                        <div class="user-action-icon">+</div>

                        <div class="user-action-content">
                            <h3>Rent Our Space</h3>
                            <p>Learn about the gallery and how to exhibit.</p>
                        </div>

                        <span class="user-action-arrow">→</span>
                    </a>

                    <a href="submit-inquiry.php" class="user-action">

                        <div class="user-action-icon">✎</div>

                        <div class="user-action-content">
                            <h3>Submit an Inquiry</h3>
                            <p>Propose a new exhibition to EDL Gallery.</p>
                        </div>

                        <span class="user-action-arrow">→</span>
                    </a>

                    <a href="notification.php" class="user-action">

                        <div class="user-action-icon">🔔</div>

                        <div class="user-action-content">
                            <h3>Notifications</h3>
                            <p>View your latest updates and notifications.</p>
                        </div>

                        <?php if ($unreadCount > 0): ?>
                            <span class="user-action-badge">
                                <?php echo $unreadCount; ?>
                            </span>
                        <?php endif; ?>
                        <span class="user-action-arrow">→</span>
                    </a>

                    <a href="my-inquiries.php" class="user-action">

                        <div class="user-action-icon">◎</div>

                        <div class="user-action-content">
                            <h3>My Inquiries</h3>
                            <p>Track the status of your exhibition inquiries.</p>
                        </div>

                        <span class="user-action-arrow">→</span>
                    </a>

                    <a href="my-artists.php" class="user-action">

                        <div class="user-action-icon">✦</div>

                        <div class="user-action-content">
                            <h3>My Artists</h3>
                            <p>Add and manage the artists you work with.</p>
                        </div>

                        <span class="user-action-arrow">→</span>
                    </a>

                    <a href="my-exhibitions.php" class="user-action">

                        <div class="user-action-icon">▣</div>

                        <div class="user-action-content">
                            <h3>My Exhibitions</h3>
                            <p>Manage the exhibitions you've been approved for.</p>
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
                            <p>Have a question about exhibiting?</p>
                        </div>

                        <span class="user-action-arrow">→</span>
                    </a>

                </div>

            </section>


            <section class="user-panel profile-panel">

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

                <p class="user-panel-blurb">
                    EDL Gallery provides a space for artists and groups to present
                    creative work through meaningful exhibitions and experiences.
                </p>

                <a href="about-us.php" class="user-panel-link user-panel-link--spaced">Learn More →</a>

            </section>

        </aside>

    </div>

</main>

<?php require_once "includes/footer.php"; ?>

</body>
</html>
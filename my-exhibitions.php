<?php

session_start();
$basePath = "";

require_once "database/config.php";
require_once "security/authorize.php";


if (!isUser()) {
    header("Location: login.php");
    exit;
}


$userId = $_SESSION["user_id"];


// Only this user's own exhibitions - never anyone else's.
$sql = "SELECT *
        FROM exhibitions
        WHERE organizer_id = :organizer_id
        ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":organizer_id", $userId, PDO::PARAM_INT);
$stmt->execute();

$exhibitions = $stmt->fetchAll();


// Small helper so the status text always looks tidy on screen.
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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Exhibitions | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="image/logo.png">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php require_once "include/header.php"; ?>


<section class="dashboard-hero" style="background-image:
    linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)),
    url('image/background-2.jpg');">

    <div class="dashboard-hero-content">

        <p class="dashboard-label">EDL GALLERY</p>

        <h1>My <span>Exhibitions</span></h1>

        <p class="dashboard-description">
            Manage the exhibitions you have been approved to hold
            at EDL Gallery.
        </p>

    </div>

</section>


<section class="dashboard-content">

    <div class="dashboard-heading">
        <p>YOUR EXHIBITIONS</p>
        <h2>Manage Your Exhibitions</h2>
    </div>


    <?php if (empty($exhibitions)): ?>

        <div class="empty-dashboard">

            <h3>No Exhibitions Yet</h3>

            <p>
                Exhibitions appear here once an admin approves one of
                your exhibition inquiries.
            </p>

            <a href="my-inquiries.php" class="dashboard-outline-button">
                VIEW MY INQUIRIES
            </a>

            <a href="rent-space.php" class="dashboard-outline-button">
                RENT OUR SPACE
            </a>

        </div>

    <?php else: ?>

        <?php foreach ($exhibitions as $exhibition): ?>

            <div class="inquiry-card">

                <div class="inquiry-card-row">

                    <div class="inquiry-main">

                        <h3>
                            <?php echo htmlspecialchars($exhibition["title"]); ?>
                        </h3>

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
                            href="exhibition-edit.php?id=<?php echo (int)$exhibition['id']; ?>"
                            class="admin-view-button"
                        >
                            MANAGE
                        </a>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</section>


<?php require_once "include/footer.php"; ?>

</body>
</html>

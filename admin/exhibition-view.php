<?php

session_start();
$basePath = "../";

require_once "../database/config.php";
require_once "../security/authorize.php";
require_once "../security/shield.php";


if (!isLoggedIn() || !isAdmin()) {

    header("Location: ../login.php");
    exit;

}


// ---- GET THE EXHIBITION ID FROM THE URL ----

$exhibitionId = $_GET["id"] ?? "";

if (!ctype_digit((string)$exhibitionId)) {
    header("Location: exhibitions.php");
    exit;
}

$exhibitionId = (int)$exhibitionId;


// Which status changes are allowed FROM each current status.
// This keeps the lifecycle honest: an exhibition can only move
// forward (or be cancelled), never skip around at random.
$allowedTransitions = [
    "draft"              => ["artwork_submission", "cancelled"],
    "artwork_submission" => ["ready_to_publish", "cancelled"],
    "ready_to_publish"   => ["published", "cancelled"],
    "published"          => ["completed", "cancelled"],
];


$errors   = [];
$feedback = "";


// ---- HANDLE A STATUS CHANGE ----

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {

        $errors[] = "Invalid security token. Please try again.";

    } else {

        $newStatus = $_POST["new_status"] ?? "";

        // Re-fetch fresh, so we always check against the real current status.
        $stmt = $pdo->prepare("SELECT * FROM exhibitions WHERE id = :id");
        $stmt->bindValue(":id", $exhibitionId, PDO::PARAM_INT);
        $stmt->execute();
        $current = $stmt->fetch();

        if (!$current) {

            $errors[] = "This exhibition no longer exists.";

        } else {

            $currentStatus = $current["status"];
            $validNextSteps = $allowedTransitions[$currentStatus] ?? [];

            if (!in_array($newStatus, $validNextSteps, true)) {

                $errors[] = "That status change is not allowed from the current status.";

            } else {

                if ($newStatus === "published") {

                    $updateSql = "UPDATE exhibitions
                                SET status = :status, published_at = NOW()
                                WHERE id = :id";

                } else {

                    $updateSql = "UPDATE exhibitions
                                SET status = :status
                                WHERE id = :id";

                }

                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->bindValue(":status", $newStatus);
                $updateStmt->bindValue(":id", $exhibitionId, PDO::PARAM_INT);
                $updateStmt->execute();


                // Let the organizer know their exhibition status changed.
                $notifTitle = "Your exhibition status was updated";
                $notifMessage = "\"" . $current["title"] . "\" is now: " . strtoupper($newStatus) . ".";

                $notifStmt = $pdo->prepare(
                    "INSERT INTO notifications (user_id, title, message, is_read, created_at)
                     VALUES (:user_id, :title, :message, 0, NOW())"
                );
                $notifStmt->bindValue(":user_id", $current["organizer_id"], PDO::PARAM_INT);
                $notifStmt->bindValue(":title", $notifTitle);
                $notifStmt->bindValue(":message", $notifMessage);
                $notifStmt->execute();

                $feedback = "Exhibition status updated to " . strtoupper($newStatus) . ".";

            }

        }

    }

}


// ---- FETCH THE EXHIBITION FOR DISPLAY ----

$sql = "SELECT e.*, u.first_name, u.last_name, u.email
        FROM exhibitions e
        JOIN users u ON u.id = e.organizer_id
        WHERE e.id = :id";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $exhibitionId, PDO::PARAM_INT);
$stmt->execute();

$exhibition = $stmt->fetch();

if (!$exhibition) {
    header("Location: exhibitions.php");
    exit;
}


// ---- ARTISTS ATTACHED TO THIS EXHIBITION ----

$artistStmt = $pdo->prepare(
    "SELECT a.*
     FROM exhibition_artists ea
     JOIN artists a ON a.id = ea.artist_id
     WHERE ea.exhibition_id = :exhibition_id"
);
$artistStmt->bindValue(":exhibition_id", $exhibitionId, PDO::PARAM_INT);
$artistStmt->execute();
$artists = $artistStmt->fetchAll();


// ---- ARTWORKS SUBMITTED FOR THIS EXHIBITION ----

$artworkStmt = $pdo->prepare(
    "SELECT * FROM artworks WHERE exhibition_id = :exhibition_id ORDER BY created_at DESC"
);
$artworkStmt->bindValue(":exhibition_id", $exhibitionId, PDO::PARAM_INT);
$artworkStmt->execute();
$artworks = $artworkStmt->fetchAll();

$artworkCounts = ["pending" => 0, "approved" => 0, "rejected" => 0];
foreach ($artworks as $artwork) {
    $artworkCounts[$artwork["status"]] = ($artworkCounts[$artwork["status"]] ?? 0) + 1;
}


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

$nextSteps = $allowedTransitions[$exhibition["status"]] ?? [];

$pageTitle  = "Exhibition Details";
$activePage = "exhibitions";
require_once "includes/admin-head.php";
?>

<section class="admin-page">

    <a href="exhibitions.php" class="admin-back-link">
        &larr; Back to Exhibitions
    </a>

    <div class="admin-header">

        <p class="admin-label">ADMIN</p>

        <h1><?php echo htmlspecialchars($exhibition["title"]); ?></h1>

        <span class="status-label">STATUS</span>

        <strong class="status-<?php echo htmlspecialchars($exhibition['status']); ?>">
            <?php echo formatStatusLabel($exhibition["status"]); ?>
        </strong>

    </div>


    <?php if (!empty($feedback)): ?>
        <div class="inquiry-success-note">
            <p><?php echo htmlspecialchars($feedback); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="form-errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>


    <!-- EXHIBITION DETAILS -->

    <div class="account-information">

        <div class="account-row">
            <span>ORGANIZER</span>
            <strong>
                <?php
                echo htmlspecialchars(
                    $exhibition["first_name"] . " " . $exhibition["last_name"]
                );
                ?>
                (<?php echo htmlspecialchars($exhibition["email"]); ?>)
            </strong>
        </div>

        <div class="account-row">
            <span>DATES</span>
            <strong>
                <?php
                echo date("F j, Y", strtotime($exhibition["start_date"]));
                echo " &ndash; ";
                echo date("F j, Y", strtotime($exhibition["end_date"]));
                ?>
            </strong>
        </div>

        <div class="account-row">
            <span>ARTISTS ATTACHED</span>
            <strong><?php echo count($artists); ?></strong>
        </div>

        <div class="account-row">
            <span>ARTWORKS</span>
            <strong>
                <?php echo count($artworks); ?> total
                (<?php echo $artworkCounts['approved']; ?> approved,
                <?php echo $artworkCounts['pending']; ?> pending,
                <?php echo $artworkCounts['rejected']; ?> rejected)
            </strong>
        </div>

        <?php if (!empty($exhibition["published_at"])): ?>
            <div class="account-row">
                <span>PUBLISHED ON</span>
                <strong><?php echo date("F j, Y g:i A", strtotime($exhibition["published_at"])); ?></strong>
            </div>
        <?php endif; ?>

    </div>


    <div class="admin-text-block">
        <h4>DESCRIPTION</h4>
        <p><?php echo nl2br(htmlspecialchars($exhibition["description"])); ?></p>
    </div>


    <!-- ARTISTS LIST -->

    <div class="admin-text-block">

        <h4>ARTISTS</h4>

        <?php if (empty($artists)): ?>

            <p>No artists have been added to this exhibition yet.</p>

        <?php else: ?>

            <?php foreach ($artists as $artist): ?>
                <p><?php echo htmlspecialchars($artist["name"]); ?></p>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>


    <!-- ARTWORKS LIST -->

    <div class="admin-text-block">

        <h4>ARTWORKS</h4>

        <?php if (empty($artworks)): ?>

            <p>No artworks have been submitted for this exhibition yet.</p>

        <?php else: ?>

            <?php foreach ($artworks as $artwork): ?>
                <p>
                    <?php echo htmlspecialchars($artwork["title"]); ?>
                    &mdash;
                    <strong class="status-<?php echo htmlspecialchars($artwork['status']); ?>">
                        <?php echo strtoupper($artwork["status"]); ?>
                    </strong>
                </p>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>


    <!-- STATUS ACTIONS -->

    <?php if (!empty($nextSteps)): ?>

        <div class="admin-action-box" style="margin-top: 30px;">

            <h4>UPDATE STATUS</h4>

            <p>
                Choose the next status for this exhibition. The
                organizer will be notified of the change.
            </p>

            <?php foreach ($nextSteps as $step): ?>

                <form
                    method="POST"
                    action="exhibition-view.php?id=<?php echo $exhibitionId; ?>"
                    style="display:inline-block; margin-right:10px;"
                >

                    <?php echo csrf_field(); ?>

                    <input type="hidden" name="new_status" value="<?php echo htmlspecialchars($step); ?>">

                    <button
                        type="submit"
                        class="<?php echo $step === 'cancelled' ? 'reject-button' : 'approve-button'; ?>"
                    >
                        <?php echo $step === 'cancelled' ? 'CANCEL EXHIBITION' : 'MARK AS ' . formatStatusLabel($step); ?>
                    </button>

                </form>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<?php require_once "includes/admin-foot.php"; ?>

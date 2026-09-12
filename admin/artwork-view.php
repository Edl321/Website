<?php

session_start();
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


$adminId = $_SESSION["user_id"];


// ---- GET THE ARTWORK ID FROM THE URL ----

$artworkId = $_GET["id"] ?? "";

if (!ctype_digit((string)$artworkId)) {
    header("Location: artworks.php");
    exit;
}

$artworkId = (int)$artworkId;


$errors   = [];
$feedback = "";


// ---- HANDLE APPROVE / REJECT ----

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {

        $errors[] = "Invalid security token. Please try again.";

    } else {

        $action    = $_POST["action"] ?? "";
        $adminNote = trim($_POST["admin_note"] ?? "");

        // Re-fetch fresh so we always act on the current status.
        $stmt = $pdo->prepare("SELECT * FROM artworks WHERE id = :id");
        $stmt->bindValue(":id", $artworkId, PDO::PARAM_INT);
        $stmt->execute();
        $current = $stmt->fetch();

        if (!$current) {

            $errors[] = "This artwork no longer exists.";

        } elseif ($current["status"] !== "pending") {

            $errors[] = "This artwork has already been reviewed.";

        } elseif ($action === "reject" && $adminNote === "") {

            $errors[] = "Please provide a reason before rejecting an artwork.";

        } elseif (in_array($action, ["approve", "reject"], true)) {

            $newStatus = ($action === "approve") ? "approved" : "rejected";

            $updateStmt = $pdo->prepare(
                "UPDATE artworks
                 SET status = :status, admin_note = :admin_note, reviewed_at = NOW()
                 WHERE id = :id"
            );
            $updateStmt->bindValue(":status", $newStatus);
            $updateStmt->bindValue(":admin_note", $adminNote !== "" ? $adminNote : null);
            $updateStmt->bindValue(":id", $artworkId, PDO::PARAM_INT);
            $updateStmt->execute();


            // Notify the exhibition organizer of the decision.
            $orgStmt = $pdo->prepare(
                "SELECT e.organizer_id, e.title AS exhibition_title
                 FROM exhibitions e
                 JOIN artworks aw ON aw.exhibition_id = e.id
                 WHERE aw.id = :id"
            );
            $orgStmt->bindValue(":id", $artworkId, PDO::PARAM_INT);
            $orgStmt->execute();
            $organizer = $orgStmt->fetch();

            if ($organizer) {

                $notifTitle = ($action === "approve")
                    ? "An artwork was approved"
                    : "An artwork was not approved";

                $notifMessage = "\"" . $current["title"] . "\" in \""
                    . $organizer["exhibition_title"] . "\" is now " . strtoupper($newStatus) . "."
                    . ($adminNote !== "" ? " Note: " . $adminNote : "");

                $notifStmt = $pdo->prepare(
                    "INSERT INTO notifications (user_id, title, message, is_read, created_at)
                     VALUES (:user_id, :title, :message, 0, NOW())"
                );
                $notifStmt->bindValue(":user_id", $organizer["organizer_id"], PDO::PARAM_INT);
                $notifStmt->bindValue(":title", $notifTitle);
                $notifStmt->bindValue(":message", $notifMessage);
                $notifStmt->execute();

            }

            $feedback = "Artwork " . $newStatus . ".";

        } else {

            $errors[] = "Unknown action.";

        }

    }

}


// ---- FETCH ARTWORK FOR DISPLAY ----

$sql = "SELECT aw.*, e.title AS exhibition_title, a.name AS artist_name
        FROM artworks aw
        JOIN exhibitions e ON e.id = aw.exhibition_id
        JOIN artists a ON a.id = aw.artist_id
        WHERE aw.id = :id";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $artworkId, PDO::PARAM_INT);
$stmt->execute();

$artwork = $stmt->fetch();

if (!$artwork) {
    header("Location: artworks.php");
    exit;
}

$pageTitle  = "Artwork Details";
$activePage = "artworks";
require_once "admin-head.php";
?>

<section class="admin-page">

    <a href="artworks.php" class="admin-back-link">
        &larr; Back to Artworks
    </a>

    <div class="admin-header">

        <p class="admin-label">ADMIN</p>

        <h1><?php echo htmlspecialchars($artwork["title"]); ?></h1>

        <span class="status-label">STATUS</span>

        <strong class="status-<?php echo htmlspecialchars($artwork['status']); ?>">
            <?php echo strtoupper($artwork["status"]); ?>
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


    <?php if (!empty($artwork["image"])): ?>
        <img
            src="../<?php echo htmlspecialchars($artwork['image']); ?>"
            alt="<?php echo htmlspecialchars($artwork['title']); ?>"
            style="max-width:320px; display:block; margin-bottom:30px;"
        >
    <?php endif; ?>


    <div class="account-information">

        <div class="account-row">
            <span>EXHIBITION</span>
            <strong><?php echo htmlspecialchars($artwork["exhibition_title"]); ?></strong>
        </div>

        <div class="account-row">
            <span>ARTIST</span>
            <strong><?php echo htmlspecialchars($artwork["artist_name"]); ?></strong>
        </div>

        <div class="account-row">
            <span>MEDIUM</span>
            <strong><?php echo htmlspecialchars($artwork["medium"] ?? "-"); ?></strong>
        </div>

        <div class="account-row">
            <span>YEAR CREATED</span>
            <strong><?php echo htmlspecialchars((string)($artwork["year_created"] ?? "-")); ?></strong>
        </div>

        <div class="account-row">
            <span>DIMENSIONS</span>
            <strong><?php echo htmlspecialchars($artwork["dimensions"] ?? "-"); ?></strong>
        </div>

        <?php if (!empty($artwork["price"])): ?>
            <div class="account-row">
                <span>PRICE</span>
                <strong><?php echo htmlspecialchars($artwork["price"]); ?></strong>
            </div>
        <?php endif; ?>

        <div class="account-row">
            <span>SUBMITTED ON</span>
            <strong><?php echo date("F j, Y g:i A", strtotime($artwork["created_at"])); ?></strong>
        </div>

        <?php if (!empty($artwork["reviewed_at"])): ?>
            <div class="account-row">
                <span>REVIEWED ON</span>
                <strong><?php echo date("F j, Y g:i A", strtotime($artwork["reviewed_at"])); ?></strong>
            </div>
        <?php endif; ?>

    </div>


    <?php if (!empty($artwork["description"])): ?>
        <div class="admin-text-block">
            <h4>DESCRIPTION</h4>
            <p><?php echo nl2br(htmlspecialchars($artwork["description"])); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($artwork["admin_note"])): ?>
        <div class="admin-text-block">
            <h4>ADMIN NOTE</h4>
            <p><?php echo nl2br(htmlspecialchars($artwork["admin_note"])); ?></p>
        </div>
    <?php endif; ?>


    <!-- APPROVE / REJECT ACTIONS -->

    <?php if ($artwork["status"] === "pending"): ?>

        <div class="admin-actions">

            <div class="admin-action-box">

                <h4>APPROVE THIS ARTWORK</h4>

                <p>This artwork will be marked approved for the exhibition.</p>

                <form method="POST" action="artwork-view.php?id=<?php echo $artworkId; ?>">

                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="approve">

                    <div class="form-group">
                        <label for="approve_note">NOTE (OPTIONAL)</label>
                        <textarea id="approve_note" name="admin_note" rows="3"></textarea>
                    </div>

                    <button type="submit" class="approve-button">APPROVE ARTWORK</button>

                </form>

            </div>


            <div class="admin-action-box">

                <h4>REJECT THIS ARTWORK</h4>

                <p>Please explain why this artwork is being rejected.</p>

                <form method="POST" action="artwork-view.php?id=<?php echo $artworkId; ?>">

                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="reject">

                    <div class="form-group">
                        <label for="reject_note">REASON FOR REJECTION</label>
                        <textarea id="reject_note" name="admin_note" rows="3" required></textarea>
                    </div>

                    <button type="submit" class="reject-button">REJECT ARTWORK</button>

                </form>

            </div>

        </div>

    <?php endif; ?>

</section>

<?php require_once "admin-foot.php"; ?>
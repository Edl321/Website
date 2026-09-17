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


$artistId = $_GET["id"] ?? "";

if (!ctype_digit((string)$artistId)) {
    header("Location: artists.php");
    exit;
}

$artistId = (int)$artistId;


$errors   = [];
$feedback = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {

        $errors[] = "Invalid security token. Please try again.";

    } elseif (($_POST["action"] ?? "") === "delete") {

        try {

            $artistToDelete = getArtistById($pdo, $artistId);

            $deleteStmt = $pdo->prepare("DELETE FROM artists WHERE id = :id");
            $deleteStmt->bindValue(":id", $artistId, PDO::PARAM_INT);
            $deleteStmt->execute();


            if ($artistToDelete) {
                deleteManagedImage((string)$artistToDelete["image"], "artists/artist_");
            }

            header("Location: artists.php");
            exit;

        } catch (PDOException $e) {

            $errors[] = "This artist could not be deleted because they are still attached to an exhibition or artwork.";

        }

    }

}

$artist = getArtistById($pdo, $artistId);

if (!$artist) {
    header("Location: artists.php");
    exit;
}


$exhibitionStmt = $pdo->prepare(
    "SELECT e.id, e.title, e.status
    FROM exhibition_artists ea
    JOIN exhibitions e ON e.id = ea.exhibition_id
    WHERE ea.artist_id = :artist_id
    ORDER BY e.start_date DESC"
);
$exhibitionStmt->bindValue(":artist_id", $artistId, PDO::PARAM_INT);
$exhibitionStmt->execute();
$exhibitions = $exhibitionStmt->fetchAll();

$artworkStmt = $pdo->prepare(
    "SELECT aw.id, aw.title, aw.status, e.title AS exhibition_title
    FROM artworks aw
    JOIN exhibitions e ON e.id = aw.exhibition_id
    WHERE aw.artist_id = :artist_id
    ORDER BY aw.created_at DESC"
);
$artworkStmt->bindValue(":artist_id", $artistId, PDO::PARAM_INT);
$artworkStmt->execute();
$artworks = $artworkStmt->fetchAll();

$pageTitle  = $artist["name"];
$activePage = "artists";
require_once "admin-head.php";
?>

<section class="admin-page">

    <a href="artists.php" class="admin-back-link">
        &larr; Back to Artists
    </a>

    <div class="admin-header">

        <p class="admin-label">ADMIN</p>

        <h1><?php echo htmlspecialchars($artist["name"]); ?></h1>

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


    <?php if (!empty($artist["image"])): ?>
        <img
            src="../Images/<?php echo htmlspecialchars($artist['image']); ?>"
            alt="<?php echo htmlspecialchars($artist['name']); ?>"
            style="max-width:320px; display:block; margin-bottom:30px;"
        >
    <?php endif; ?>


    <div class="account-information">

        <?php if (!empty($artist["created_at"])): ?>
            <div class="account-row">
                <span>ADDED ON</span>
                <strong><?php echo date("F j, Y", strtotime($artist["created_at"])); ?></strong>
            </div>
        <?php endif; ?>

        <div class="account-row">
            <span>EXHIBITIONS ATTACHED</span>
            <strong><?php echo count($exhibitions); ?></strong>
        </div>

        <div class="account-row">
            <span>ARTWORKS</span>
            <strong><?php echo count($artworks); ?></strong>
        </div>

    </div>


    <div class="admin-text-block">
        <h4>BIOGRAPHY</h4>
        <p><?php echo nl2br(htmlspecialchars($artist["biography"])); ?></p>
    </div>

    <div class="admin-text-block">

        <h4>EXHIBITIONS</h4>

        <?php if (empty($exhibitions)): ?>

            <p>This artist hasn't been added to any exhibitions yet.</p>

        <?php else: ?>

            <?php foreach ($exhibitions as $exhibition): ?>
                <p>
                    <?php echo htmlspecialchars($exhibition["title"]); ?>
                    &mdash;
                    <strong class="status-<?php echo htmlspecialchars($exhibition['status']); ?>">
                        <?php echo strtoupper($exhibition["status"]); ?>
                    </strong>
                </p>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <div class="admin-text-block">

        <h4>ARTWORKS</h4>

        <?php if (empty($artworks)): ?>

            <p>No artworks recorded for this artist yet.</p>

        <?php else: ?>

            <?php foreach ($artworks as $artwork): ?>
                <p>
                    <?php echo htmlspecialchars($artwork["title"]); ?>
                    (<?php echo htmlspecialchars($artwork["exhibition_title"]); ?>)
                    &mdash;
                    <strong class="status-<?php echo htmlspecialchars($artwork['status']); ?>">
                        <?php echo strtoupper($artwork["status"]); ?>
                    </strong>
                </p>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>


    <div class="admin-action-box" style="margin-top: 30px;">

        <h4>MANAGE THIS ARTIST</h4>

        <a href="artists.php?edit=<?php echo (int)$artist['id']; ?>" class="admin-view-button">
            EDIT
        </a>

        <form
            method="POST"
            action="artist-view.php?id=<?php echo (int)$artist['id']; ?>"
            onsubmit="return confirm('Delete this artist? This cannot be undone.');"
            style="display:inline-block; margin-left:10px;"
        >
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="reject-button">DELETE</button>
        </form>

    </div>

</section>


<?php require_once "admin-foot.php"; ?>
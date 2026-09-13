<?php

session_start();
$basePath = "";

require_once "database/config.php";
require_once "security/authorize.php";
require_once "security/shield.php";


if (!isUser()) {
    header("Location: login.php");
    exit;
}


$userId = $_SESSION["user_id"];


// =========================================================
// CONSTANTS
// =========================================================

const ARTIST_NAME_MAX_LENGTH      = 150;
const ARTIST_BIOGRAPHY_MAX_LENGTH = 5000;

const ARTIST_UPLOAD_SUBDIR = "artists/";
const ARTIST_UPLOAD_DIR    = "Images/" . ARTIST_UPLOAD_SUBDIR;

const ARTIST_ALLOWED_IMAGE_TYPES = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp",
];
const ARTIST_MAX_IMAGE_BYTES = 5 * 1024 * 1024; // 5 MB


// =========================================================
// HELPERS
// =========================================================

/**
 * Fetch a single artist row, but only if the current user owns it
 * OR it's a shared/admin-owned artist (user_id IS NULL).
 */
function getVisibleArtist(PDO $pdo, int $artistId, int $userId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT * FROM artists
        WHERE id = :id
        AND (user_id = :user_id OR user_id IS NULL)"
    );
    $stmt->bindValue(":id", $artistId, PDO::PARAM_INT);
    $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch();

    return $row ?: null;
}


/**
 * Only delete files this module created.
 */
function deleteManagedArtistImage(string $imagePath): void
{
    if ($imagePath === "") {
        return;
    }

    $expectedPrefix = ARTIST_UPLOAD_SUBDIR . "artist_";

    if (strpos($imagePath, $expectedPrefix) !== 0) {
        return;
    }

    if (strpos($imagePath, "..") !== false) {
        return;
    }

    $fullPath = "Images/" . $imagePath;

    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}


// =========================================================
// STATE
// =========================================================

$errors   = [];
$feedback = "";

$editingId = "";
$name      = "";
$biography = "";
$image     = "";


// =========================================================
// EDIT MODE
// =========================================================

if (isset($_GET["edit"]) && ctype_digit((string)$_GET["edit"])) {

    $artistToEdit = getVisibleArtist($pdo, (int)$_GET["edit"], $userId);

    if (!$artistToEdit) {

        $errors[] = "That artist could not be found, or you don't have permission to edit it.";

    } elseif ((int)$artistToEdit["user_id"] !== (int)$userId) {

        $errors[] = "You can only edit artists you created yourself.";

    } else {

        $editingId = $artistToEdit["id"];
        $name      = $artistToEdit["name"];
        $biography = $artistToEdit["biography"];
        $image     = $artistToEdit["image"];

    }

}


// =========================================================
// POST HANDLING
// =========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {

        $errors[] = "Invalid security token. Please try again.";

    } else {

        $action = $_POST["action"] ?? "";


        // ---- DELETE ----

        if ($action === "delete") {

            $deleteId = $_POST["artist_id"] ?? "";

            if (!ctype_digit((string)$deleteId)) {

                $errors[] = "Invalid artist reference.";

            } else {

                $deleteId   = (int)$deleteId;
                $toDelete   = getVisibleArtist($pdo, $deleteId, $userId);

                if (!$toDelete) {

                    $errors[] = "That artist no longer exists.";

                } elseif ((int)$toDelete["user_id"] !== (int)$userId) {

                    $errors[] = "You can only delete artists you created yourself.";

                } else {

                    $attachedStmt = $pdo->prepare(
                        "SELECT COUNT(*) FROM exhibition_artists WHERE artist_id = :id"
                    );
                    $attachedStmt->bindValue(":id", $deleteId, PDO::PARAM_INT);
                    $attachedStmt->execute();

                    if ((int)$attachedStmt->fetchColumn() > 0) {

                        $errors[] = "This artist is attached to one or more exhibitions. Remove them from those exhibitions first, then delete.";

                    } else {

                        try {

                            $deleteStmt = $pdo->prepare("DELETE FROM artists WHERE id = :id");
                            $deleteStmt->bindValue(":id", $deleteId, PDO::PARAM_INT);
                            $deleteStmt->execute();

                            deleteManagedArtistImage((string)$toDelete["image"]);

                            $feedback = "Artist deleted.";

                        } catch (PDOException $e) {

                            $errors[] = "This artist could not be deleted.";

                        }

                    }

                }

            }

        }


        // ---- CREATE OR UPDATE ----

        elseif ($action === "create" || $action === "update") {

            $rawId     = $_POST["artist_id"] ?? "";
            $name      = trim($_POST["name"] ?? "");
            $biography = trim($_POST["biography"] ?? "");

            $image    = "";
            $oldImage = "";

            if ($action === "update") {

                if (!ctype_digit((string)$rawId)) {

                    $errors[] = "Invalid artist reference.";
                    $editingId = "";

                } else {

                    $editingId  = (int)$rawId;
                    $currentRow = getVisibleArtist($pdo, $editingId, $userId);

                    if (!$currentRow) {

                        $errors[] = "That artist no longer exists.";
                        $editingId = "";

                    } elseif ((int)$currentRow["user_id"] !== (int)$userId) {

                        $errors[] = "You can only edit artists you created yourself.";
                        $editingId = "";

                    } else {

                        $image    = (string)$currentRow["image"];
                        $oldImage = $image;

                    }

                }

            } else {
                $editingId = "";
            }


            // ---- FIELD VALIDATION ----

            if ($name === "") {
                $errors[] = "Artist name is required.";
            } elseif (mb_strlen($name) > ARTIST_NAME_MAX_LENGTH) {
                $errors[] = "Artist name must be " . ARTIST_NAME_MAX_LENGTH . " characters or fewer.";
            }

            if ($biography === "") {
                $errors[] = "Artist biography is required.";
            } elseif (mb_strlen($biography) > ARTIST_BIOGRAPHY_MAX_LENGTH) {
                $errors[] = "Artist biography must be " . ARTIST_BIOGRAPHY_MAX_LENGTH . " characters or fewer.";
            }


            // ---- OPTIONAL IMAGE UPLOAD ----

            $newImagePath = null;

            if (!empty($_FILES["image"]["name"]) && empty($errors)) {

                $upload = $_FILES["image"];

                if ($upload["error"] !== UPLOAD_ERR_OK) {

                    $errors[] = "There was a problem uploading the photo. Please try again.";

                } elseif ($upload["size"] > ARTIST_MAX_IMAGE_BYTES) {

                    $errors[] = "Artist photo must be smaller than 5MB.";

                } elseif (!is_uploaded_file($upload["tmp_name"])) {

                    $errors[] = "There was a problem uploading the photo. Please try again.";

                } else {

                    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                    $realType = $finfo ? finfo_file($finfo, $upload["tmp_name"]) : false;
                    if ($finfo) {
                        finfo_close($finfo);
                    }

                    $imageInfo = @getimagesize($upload["tmp_name"]);

                    if (
                        $realType === false ||
                        !isset(ARTIST_ALLOWED_IMAGE_TYPES[$realType]) ||
                        $imageInfo === false
                    ) {

                        $errors[] = "Artist photo must be a valid JPG, PNG, or WEBP image.";

                    } else {

                        $extension   = ARTIST_ALLOWED_IMAGE_TYPES[$realType];
                        $newFileName = "artist_" . bin2hex(random_bytes(8)) . "." . $extension;

                        if (!is_dir(ARTIST_UPLOAD_DIR)) {
                            mkdir(ARTIST_UPLOAD_DIR, 0755, true);
                        }

                        $destination = ARTIST_UPLOAD_DIR . $newFileName;

                        if (move_uploaded_file($upload["tmp_name"], $destination)) {
                            $newImagePath = ARTIST_UPLOAD_SUBDIR . $newFileName;
                        } else {
                            $errors[] = "There was a problem uploading the photo. Please try again.";
                        }

                    }

                }

            }

            if ($newImagePath !== null) {
                $image = $newImagePath;
            }


            // ---- SAVE ----

            if (empty($errors)) {

                if ($action === "create") {

                    $insertStmt = $pdo->prepare(
                        "INSERT INTO artists (name, biography, image, created_at, user_id)
                        VALUES (:name, :biography, :image, NOW(), :user_id)"
                    );
                    $insertStmt->bindValue(":name", $name);
                    $insertStmt->bindValue(":biography", $biography);
                    $insertStmt->bindValue(":image", $image !== "" ? $image : null);
                    $insertStmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
                    $insertStmt->execute();

                    $feedback = "Artist added.";

                    $editingId = "";
                    $name = "";
                    $biography = "";
                    $image = "";

                } else {

                    $updateStmt = $pdo->prepare(
                        "UPDATE artists
                        SET name = :name, biography = :biography, image = :image
                        WHERE id = :id AND user_id = :user_id"
                    );
                    $updateStmt->bindValue(":name", $name);
                    $updateStmt->bindValue(":biography", $biography);
                    $updateStmt->bindValue(":image", $image !== "" ? $image : null);
                    $updateStmt->bindValue(":id", (int)$editingId, PDO::PARAM_INT);
                    $updateStmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
                    $updateStmt->execute();

                    if ($oldImage !== "" && $oldImage !== $image) {
                        deleteManagedArtistImage($oldImage);
                    }

                    $feedback = "Artist updated.";

                }

            } elseif ($newImagePath !== null) {

                deleteManagedArtistImage($newImagePath);
                $image = $oldImage;

            }

        }

    }

}


// =========================================================
// FETCH ARTIST LISTS
// =========================================================

$myStmt = $pdo->prepare(
    "SELECT * FROM artists
     WHERE user_id = :user_id
     ORDER BY name ASC"
);
$myStmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$myStmt->execute();

$myArtists = $myStmt->fetchAll();


$sharedStmt = $pdo->prepare(
    "SELECT * FROM artists
     WHERE user_id IS NULL
     ORDER BY name ASC"
);
$sharedStmt->execute();

$sharedArtists = $sharedStmt->fetchAll();


$pageTitle  = "My Artists";
$activePage = "artists";
require_once "includes/header.php";
?>
<link rel="icon" type="image/x-icon" href="Images/logo.png">
<link rel="stylesheet" href="style.css">

<section class="dashboard-content">

    <a href="dashboard.php" class="back-dashboard-link">
        &larr; Back to Dashboard
    </a>

    <div class="dashboard-heading">
        <p>YOUR ARTISTS</p>
        <h2>My Artists</h2>
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

    <!-- ADD / EDIT FORM -->

    <div class="admin-action-box">

        <h4><?php echo $editingId ? "EDIT ARTIST" : "ADD A NEW ARTIST"; ?></h4>

        <form
            method="POST"
            action="my-artists.php"
            enctype="multipart/form-data"
        >

            <?php echo csrf_field(); ?>

            <input type="hidden" name="action" value="<?php echo $editingId ? 'update' : 'create'; ?>">
            <input type="hidden" name="artist_id" value="<?php echo htmlspecialchars((string)$editingId); ?>">

            <div class="form-group">
                <label for="name">ARTIST NAME</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?php echo htmlspecialchars($name); ?>"
                    maxlength="<?php echo ARTIST_NAME_MAX_LENGTH; ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="biography">BIOGRAPHY</label>
                <textarea
                    id="biography"
                    name="biography"
                    rows="5"
                    maxlength="<?php echo ARTIST_BIOGRAPHY_MAX_LENGTH; ?>"
                    required
                ><?php echo htmlspecialchars($biography); ?></textarea>
            </div>

            <div class="form-group">

                <label for="image">PHOTO (OPTIONAL)</label>

                <?php if (!empty($image)): ?>

                    <img
                        src="Images/<?php echo htmlspecialchars($image); ?>"
                        alt="Current photo"
                        class="artist-form-preview"
                    >

                <?php endif; ?>

                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">

            </div>

            <button type="submit" class="approve-button">
                <?php echo $editingId ? "SAVE CHANGES" : "ADD ARTIST"; ?>
            </button>

            <?php if ($editingId): ?>
                <a href="my-artists.php" class="admin-view-button admin-view-button-spaced">
                    CANCEL
                </a>
            <?php endif; ?>

        </form>

    </div>


    <!-- MY ARTISTS -->

    <div class="admin-header admin-header-spaced">
        <p class="admin-label">YOUR DIRECTORY</p>
        <h1 class="admin-heading-small">Artists You Added (<?php echo count($myArtists); ?>)</h1>
    </div>

    <?php if (empty($myArtists)): ?>

        <div class="empty-dashboard">
            <h3>No Artists Yet</h3>
            <p>Add your first artist using the form above.</p>
        </div>

    <?php else: ?>

        <?php foreach ($myArtists as $artist): ?>

            <div class="admin-list-row">

                <div class="admin-list-row-main">
                    <h3><?php echo htmlspecialchars($artist["name"]); ?></h3>
                    <p class="inquiry-meta">
                        <?php
                        $bio = $artist["biography"];
                        echo htmlspecialchars(
                            mb_strlen($bio) > 140 ? mb_substr($bio, 0, 140) . "..." : $bio
                        );
                        ?>
                    </p>
                </div>

                <div class="admin-list-row-actions">

                    <a href="my-artists.php?edit=<?php echo (int)$artist['id']; ?>" class="admin-view-button">
                        EDIT
                    </a>

                    <form
                        method="POST"
                        action="my-artists.php"
                        onsubmit="return confirm('Delete this artist? This cannot be undone.');"
                        class="inline-form"
                    >
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="artist_id" value="<?php echo (int)$artist['id']; ?>">
                        <button type="submit" class="reject-button">DELETE</button>
                    </form>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>


    <!-- SHARED ARTISTS -->

    <?php if (!empty($sharedArtists)): ?>

        <div class="admin-header admin-header-spaced">
            <p class="admin-label">SHARED DIRECTORY</p>
            <h1 class="admin-heading-small">Artists Provided by the Gallery (<?php echo count($sharedArtists); ?>)</h1>
            <p class="admin-subtext">
                These artists are managed by the gallery. You can attach them to your exhibitions but you can't edit them.
            </p>
        </div>

        <?php foreach ($sharedArtists as $artist): ?>

            <div class="admin-list-row">

                <div class="admin-list-row-main">
                    <h3><?php echo htmlspecialchars($artist["name"]); ?></h3>
                    <p class="inquiry-meta">
                        <?php
                        $bio = $artist["biography"];
                        echo htmlspecialchars(
                            mb_strlen($bio) > 140 ? mb_substr($bio, 0, 140) . "..." : $bio
                        );
                        ?>
                    </p>
                </div>

                <div class="admin-list-row-actions">
                    <span class="inquiry-meta">Managed by gallery</span>
                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</section>

<?php require_once "includes/footer.php"; ?>
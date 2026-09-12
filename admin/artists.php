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


$errors   = [];
$feedback = "";

// Values used to pre-fill the form (either from a failed submit,
// or from an artist we're editing).
$editingId = "";
$name      = "";
$biography = "";
$image     = "";

// Field length limits (kept in one place so the form + validation agree).
const ARTIST_NAME_MAX_LENGTH      = 150;
const ARTIST_BIOGRAPHY_MAX_LENGTH = 5000;

// Where artist photos live. The PUBLIC artist.php page renders photos as
// "Images/<image column>", so anything we save here has to resolve
// correctly against that "Images/" prefix - hence the "artists/" subfolder
// rather than a top-level "uploads/" folder.
const ARTIST_UPLOAD_SUBDIR = "artists/"; // relative to /Images/
const ARTIST_UPLOAD_DIR    = "../Images/" . ARTIST_UPLOAD_SUBDIR;

// Only these MIME types are accepted, and the extension used on disk is
// ALWAYS derived from this map - never from the client-supplied filename.
// This closes off double-extension / polyglot-file upload tricks.
const ARTIST_ALLOWED_IMAGE_TYPES = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp",
];
const ARTIST_MAX_IMAGE_BYTES = 5 * 1024 * 1024; // 5 MB


/**
 * Fetch a single artist row by id, or null if it doesn't exist.
 */
function getArtistById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM artists WHERE id = :id");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch();

    return $row ?: null;
}


/**
 * Only ever delete files that this module created itself
 * (Images/artists/artist_<random>.<ext>). This protects any shared/legacy
 * seed images (e.g. Images/carlo.jpg) from being removed even if an
 * artist record referencing them is edited or deleted.
 */
function deleteArtistImageFile(string $imagePath): void
{
    if ($imagePath === "") {
        return;
    }

    $expectedPrefix = ARTIST_UPLOAD_SUBDIR . "artist_";

    if (strpos($imagePath, $expectedPrefix) !== 0) {
        return;
    }

    // Basic guard against any path traversal in a stored value.
    if (strpos($imagePath, "..") !== false) {
        return;
    }

    $fullPath = "../Images/" . $imagePath;

    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}


// ---- IF WE'RE EDITING, LOAD THE ARTIST INTO THE FORM ----

if (isset($_GET["edit"]) && ctype_digit((string)$_GET["edit"])) {

    $artistToEdit = getArtistById($pdo, (int)$_GET["edit"]);

    if ($artistToEdit) {
        $editingId = $artistToEdit["id"];
        $name      = $artistToEdit["name"];
        $biography = $artistToEdit["biography"];
        $image     = $artistToEdit["image"];
    } else {
        $errors[] = "That artist could not be found. It may have already been deleted.";
    }

}


// ---- HANDLE ADD / UPDATE / DELETE ----

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {

        $errors[] = "Invalid security token. Please try again.";

    } else {

        $action = $_POST["action"] ?? "";


        // ---- DELETE ----

        if ($action === "delete") {

            $deleteId = $_POST["artist_id"] ?? "";

            if (ctype_digit((string)$deleteId)) {

                $deleteId    = (int)$deleteId;
                $artistToDel = getArtistById($pdo, $deleteId);

                if (!$artistToDel) {

                    $errors[] = "That artist no longer exists.";

                } else {

                    try {

                        $deleteStmt = $pdo->prepare("DELETE FROM artists WHERE id = :id");
                        $deleteStmt->bindValue(":id", $deleteId, PDO::PARAM_INT);
                        $deleteStmt->execute();

                        // Clean up the photo on disk now that the row is gone.
                        deleteArtistImageFile((string)$artistToDel["image"]);

                        $feedback = "Artist deleted.";

                    } catch (PDOException $e) {

                        // Most likely a foreign key error because this artist
                        // is still attached to an exhibition or an artwork.
                        $errors[] = "This artist could not be deleted because they are still attached to an exhibition or artwork.";

                    }

                }

            } else {

                $errors[] = "Invalid artist reference.";

            }

        }


        // ---- ADD OR UPDATE ----

        elseif ($action === "create" || $action === "update") {

            $rawId     = $_POST["artist_id"] ?? "";
            $name      = trim($_POST["name"] ?? "");
            $biography = trim($_POST["biography"] ?? "");

            // The artist's CURRENT image (source of truth), used unless a
            // new file is uploaded below. We deliberately do NOT trust any
            // client-submitted "existing image" value - it's re-fetched
            // from the database instead, so a tampered hidden field can
            // never redirect this record at an arbitrary path.
            $image    = "";
            $oldImage = "";

            if ($action === "update") {

                if (!ctype_digit((string)$rawId)) {

                    $errors[] = "Invalid artist reference.";
                    $editingId = "";

                } else {

                    $editingId  = (int)$rawId;
                    $currentRow = getArtistById($pdo, $editingId);

                    if (!$currentRow) {
                        $errors[] = "That artist no longer exists.";
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

            // For "update", stop here if we couldn't resolve a real record.
            if ($action === "update" && $editingId === "" && empty($errors)) {
                $errors[] = "Unable to update this artist.";
            }


            // ---- OPTIONAL IMAGE UPLOAD ----

            $newImagePath = null; // set only if a new file is successfully saved

            if (!empty($_FILES["image"]["name"]) && empty($errors)) {

                $upload = $_FILES["image"];

                if ($upload["error"] !== UPLOAD_ERR_OK) {

                    $errors[] = "There was a problem uploading the photo. Please try again.";

                } elseif ($upload["size"] > ARTIST_MAX_IMAGE_BYTES) {

                    $errors[] = "Artist photo must be smaller than 5MB.";

                } elseif (!is_uploaded_file($upload["tmp_name"])) {

                    $errors[] = "There was a problem uploading the photo. Please try again.";

                } else {

                    // 1) Verify the real MIME type from the file's contents
                    //    (never trust the browser-supplied name or type).
                    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                    $realType = $finfo ? finfo_file($finfo, $upload["tmp_name"]) : false;
                    if ($finfo) {
                        finfo_close($finfo);
                    }

                    // 2) Verify it's an actual, decodable image (blocks most
                    //    polyglot / disguised-payload files that merely fake
                    //    the right magic bytes).
                    $imageInfo = @getimagesize($upload["tmp_name"]);

                    if (
                        $realType === false ||
                        !isset(ARTIST_ALLOWED_IMAGE_TYPES[$realType]) ||
                        $imageInfo === false
                    ) {

                        $errors[] = "Artist photo must be a valid JPG, PNG, or WEBP image.";

                    } else {

                        // 3) The extension is chosen by US, from the verified
                        //    MIME type - never from the uploaded filename.
                        $extension   = ARTIST_ALLOWED_IMAGE_TYPES[$realType];
                        $newFileName = "artist_" . bin2hex(random_bytes(8)) . "." . $extension;

                        if (!is_dir(ARTIST_UPLOAD_DIR)) {
                            mkdir(ARTIST_UPLOAD_DIR, 0755, true);
                        }

                        $destination = ARTIST_UPLOAD_DIR . $newFileName;

                        if (move_uploaded_file($upload["tmp_name"], $destination)) {
                            // Stored relative to /Images/, matching how the
                            // public artist.php page reads this column.
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


            if (empty($errors)) {

                if ($action === "create") {

                    $insertStmt = $pdo->prepare(
                        "INSERT INTO artists (name, biography, image, created_at)
                        VALUES (:name, :biography, :image, NOW())"
                    );
                    $insertStmt->bindValue(":name", $name);
                    $insertStmt->bindValue(":biography", $biography);
                    $insertStmt->bindValue(":image", $image !== "" ? $image : null);
                    $insertStmt->execute();

                    $feedback = "Artist added.";

                    // Clear the form for the next entry.
                    $editingId = "";
                    $name = "";
                    $biography = "";
                    $image = "";

                } else {

                    $updateStmt = $pdo->prepare(
                        "UPDATE artists
                        SET name = :name, biography = :biography, image = :image
                        WHERE id = :id"
                    );
                    $updateStmt->bindValue(":name", $name);
                    $updateStmt->bindValue(":biography", $biography);
                    $updateStmt->bindValue(":image", $image !== "" ? $image : null);
                    $updateStmt->bindValue(":id", (int)$editingId, PDO::PARAM_INT);
                    $updateStmt->execute();

                    // If a new photo replaced an old one, remove the old
                    // file from disk now that the DB row points elsewhere.
                    if ($oldImage !== "" && $oldImage !== $image) {
                        deleteArtistImageFile($oldImage);
                    }

                    $feedback = "Artist updated.";

                }

            } elseif ($newImagePath !== null) {

                // Validation failed elsewhere after we already saved a new
                // file to disk - don't leave it orphaned.
                deleteArtistImageFile($newImagePath);
                $image = $oldImage;

            }

        }

    }

}


// ---- FETCH ALL ARTISTS FOR THE LIST ----

$artists = $pdo->query(
    "SELECT * FROM artists ORDER BY name ASC"
)->fetchAll();

$pageTitle  = "Artists";
$activePage = "artists";
require_once "admin-head.php";
?>
<section class="admin-page">

    <div class="admin-header">

        <p class="admin-label">ADMIN</p>

        <h1>Artists</h1>

        <p class="admin-subtext">
            Manage the artists featured across EDL Gallery.
        </p>

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
            action="artists.php"
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
                        src="../Images/<?php echo htmlspecialchars($image); ?>"
                        alt="Current photo"
                        style="max-width:150px; display:block; margin-bottom:12px;"
                    >

                <?php endif; ?>

                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">

            </div>

            <button type="submit" class="approve-button">
                <?php echo $editingId ? "SAVE CHANGES" : "ADD ARTIST"; ?>
            </button>

            <?php if ($editingId): ?>
                <a href="artists.php" class="admin-view-button" style="margin-left:10px;">
                    CANCEL
                </a>
            <?php endif; ?>

        </form>

    </div>


    <!-- ARTIST LIST -->

    <div class="admin-header" style="margin-top:50px;">
        <p class="admin-label">DIRECTORY</p>
        <h1 style="font-size:26px;">All Artists (<?php echo count($artists); ?>)</h1>
    </div>

    <?php if (empty($artists)): ?>

        <div class="empty-dashboard">
            <h3>No Artists Yet</h3>
            <p>Add your first artist using the form above.</p>
        </div>

    <?php else: ?>

        <?php foreach ($artists as $artist): ?>

            <div class="admin-list-row">

                <div class="admin-list-row-main">
                    <h3><?php echo htmlspecialchars($artist["name"]); ?></h3>
                    <p class="inquiry-meta">
                        <?php
                        $bio = $artist["biography"];
                        echo htmlspecialchars(
                            strlen($bio) > 140 ? substr($bio, 0, 140) . "..." : $bio
                        );
                        ?>
                    </p>
                </div>

                <div class="admin-list-row-actions">

                    <a href="artist-view.php?id=<?php echo (int)$artist['id']; ?>" class="admin-view-button">
                        VIEW
                    </a>

                    <a href="artists.php?edit=<?php echo (int)$artist['id']; ?>" class="admin-view-button">
                        EDIT
                    </a>

                    <form
                        method="POST"
                        action="artists.php"
                        onsubmit="return confirm('Delete this artist? This cannot be undone.');"
                        style="display:inline;"
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

</section>

<?php require_once "admin-foot.php"; ?>
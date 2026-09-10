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


// ---- IF WE'RE EDITING, LOAD THE ARTIST INTO THE FORM ----

if (isset($_GET["edit"]) && ctype_digit((string)$_GET["edit"])) {

    $editStmt = $pdo->prepare("SELECT * FROM artists WHERE id = :id");
    $editStmt->bindValue(":id", (int)$_GET["edit"], PDO::PARAM_INT);
    $editStmt->execute();
    $artistToEdit = $editStmt->fetch();

    if ($artistToEdit) {
        $editingId = $artistToEdit["id"];
        $name      = $artistToEdit["name"];
        $biography = $artistToEdit["biography"];
        $image     = $artistToEdit["image"];
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

                try {

                    $deleteStmt = $pdo->prepare("DELETE FROM artists WHERE id = :id");
                    $deleteStmt->bindValue(":id", (int)$deleteId, PDO::PARAM_INT);
                    $deleteStmt->execute();

                    $feedback = "Artist deleted.";

                } catch (Exception $e) {

                    // Most likely a foreign key error because this artist
                    // is still attached to an exhibition's artist list.
                    $errors[] = "This artist could not be deleted because they are attached to an exhibition.";

                }

            }

        }


        // ---- ADD OR UPDATE ----

        elseif ($action === "create" || $action === "update") {

            $editingId = $_POST["artist_id"] ?? "";
            $name      = trim($_POST["name"] ?? "");
            $biography = trim($_POST["biography"] ?? "");
            $image     = trim($_POST["existing_image"] ?? "");

            if (empty($name)) {
                $errors[] = "Artist name is required.";
            }

            if (empty($biography)) {
                $errors[] = "Artist biography is required.";
            }


            // ---- OPTIONAL IMAGE UPLOAD ----

            if (!empty($_FILES["image"]["name"])) {

                $allowedTypes = ["image/jpeg", "image/png", "image/webp"];
                $maxSizeBytes = 5 * 1024 * 1024; // 5 MB

                $fileTmpPath = $_FILES["image"]["tmp_name"];
                $fileType    = mime_content_type($fileTmpPath);
                $fileSize    = $_FILES["image"]["size"];

                if (!in_array($fileType, $allowedTypes, true)) {

                    $errors[] = "Artist photo must be a JPG, PNG, or WEBP file.";

                } elseif ($fileSize > $maxSizeBytes) {

                    $errors[] = "Artist photo must be smaller than 5MB.";

                } else {

                    $extension   = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
                    $newFileName = "artist_" . uniqid() . "." . $extension;

                    $uploadDir = "../uploads/artists/";

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $destination = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $destination)) {
                        // Store the path the same way it's read on the public
                        // artist.php page - relative to the project root.
                        $image = "uploads/artists/" . $newFileName;
                    } else {
                        $errors[] = "There was a problem uploading the photo. Please try again.";
                    }

                }

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

                    $feedback = "Artist updated.";

                }

            }

        }

    }

}


// ---- FETCH ALL ARTISTS FOR THE LIST ----

$artists = $pdo->query(
    "SELECT * FROM artists ORDER BY name ASC"
)->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Artists | EDL Gallery Admin</title>
    <link rel="icon" type="image/x-icon" href="../Images/logo.png">
    <link rel="stylesheet" href="../style.css">
</head>

<body>

<?php require_once "../includes/header.php"; ?>


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
            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($image); ?>">

            <div class="form-group">
                <label for="name">ARTIST NAME</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?php echo htmlspecialchars($name); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="biography">BIOGRAPHY</label>
                <textarea
                    id="biography"
                    name="biography"
                    rows="5"
                    required
                ><?php echo htmlspecialchars($biography); ?></textarea>
            </div>

            <div class="form-group">

                <label for="image">PHOTO (OPTIONAL)</label>

                <?php if (!empty($image)): ?>

                    <img
                        src="../<?php echo htmlspecialchars($image); ?>"
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


<?php require_once "../includes/footer.php"; ?>

</body>
</html>

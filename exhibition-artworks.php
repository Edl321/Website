<?php

session_start();

$basePath = "";

require_once "database/config.php";
require_once "security/shield.php";
require_once "security/authorize.php";

/*
|--------------------------------------------------------------------------
| USER ACCESS ONLY
|--------------------------------------------------------------------------
*/

if (!isUser()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"] ?? null;

if (!$userId) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET EXHIBITION ID
|--------------------------------------------------------------------------
*/

$exhibitionId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$exhibitionId) {
    die("Invalid Exhibition ID.");
}

/*
|--------------------------------------------------------------------------
| VERIFY EXHIBITION OWNERSHIP
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        title,
        start_date,
        end_date,
        description,
        image,
        status
    FROM exhibitions
    WHERE id = :exhibition_id
      AND organizer_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ":exhibition_id" => $exhibitionId,
    ":user_id" => $userId
]);

$exhibition = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exhibition) {
    die("Exhibition not found or you do not have permission to manage it.");
}


/*
|--------------------------------------------------------------------------
| MESSAGE VARIABLES
|--------------------------------------------------------------------------
*/

$errors = [];
$successMessage = "";


/*
|--------------------------------------------------------------------------
| HANDLE POST REQUESTS
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | CSRF PROTECTION
    |--------------------------------------------------------------------------
    */

    if (!verify_csrf_token()) {
        die("Invalid CSRF token.");
    }

    $action = $_POST["action"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | ADD ARTWORK
    |--------------------------------------------------------------------------
    */

    if ($action === "add_artwork") {

        $title = trim($_POST["title"] ?? "");
        $artistId = filter_input(
            INPUT_POST,
            "artist_id",
            FILTER_VALIDATE_INT
        );

        $medium = trim($_POST["medium"] ?? "");
        $yearCreated = trim($_POST["year_created"] ?? "");
        $dimensions = trim($_POST["dimensions"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $price = trim($_POST["price"] ?? "");


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if ($title === "") {
            $errors[] = "Artwork title is required.";
        }

        if (!$artistId) {
            $errors[] = "Please select an artist.";
        }

        if ($medium === "") {
            $errors[] = "Medium is required.";
        }

        if ($yearCreated === "") {
            $errors[] = "Year created is required.";
        } elseif (!preg_match('/^\d{4}$/', $yearCreated)) {
            $errors[] = "Year created must contain exactly four digits.";
        }

        if ($dimensions === "") {
            $errors[] = "Dimensions are required.";
        }

        if ($description === "") {
            $errors[] = "Artwork description is required.";
        }

        if ($price !== "") {

            if (!is_numeric($price)) {
                $errors[] = "Price must be a valid number.";
            } elseif ((float)$price < 0) {
                $errors[] = "Price cannot be negative.";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | VERIFY ARTIST BELONGS TO THIS EXHIBITION
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            $artistCheck = $pdo->prepare("
                SELECT
                    a.id,
                    a.name
                FROM exhibition_artists ea
                INNER JOIN artists a
                    ON a.id = ea.artist_id
                WHERE ea.exhibition_id = :exhibition_id
                  AND ea.artist_id = :artist_id
                LIMIT 1
            ");

            $artistCheck->execute([
                ":exhibition_id" => $exhibitionId,
                ":artist_id" => $artistId
            ]);

            $selectedArtist = $artistCheck->fetch(PDO::FETCH_ASSOC);

            if (!$selectedArtist) {
                $errors[] = "The selected artist is not assigned to this exhibition.";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | IMAGE UPLOAD
        |--------------------------------------------------------------------------
        */

        $uploadedImageName = null;

        if (!$errors) {

            if (
                isset($_FILES["image"]) &&
                $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
                    $errors[] = "There was a problem uploading the artwork image.";
                } else {

                    $maxFileSize = 5 * 1024 * 1024;

                    if ($_FILES["image"]["size"] > $maxFileSize) {
                        $errors[] = "Artwork image must not exceed 5MB.";
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CHECK MIME TYPE
                    |--------------------------------------------------------------------------
                    */

                    $allowedMimeTypes = [
                        "image/jpeg" => "jpg",
                        "image/png"  => "png",
                        "image/webp" => "webp"
                    ];

                    $finfo = new finfo(FILEINFO_MIME_TYPE);

                    $mimeType = $finfo->file(
                        $_FILES["image"]["tmp_name"]
                    );

                    if (!isset($allowedMimeTypes[$mimeType])) {
                        $errors[] = "Only JPG, PNG, and WEBP images are allowed.";
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CREATE IMAGE DIRECTORY
                    |--------------------------------------------------------------------------
                    */

                    $imageDirectory = __DIR__ . "/Images";

                    if (!is_dir($imageDirectory)) {

                        if (!mkdir($imageDirectory, 0755, true)) {
                            $errors[] = "Unable to create the image directory.";
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | GENERATE UNIQUE FILE NAME
                    |--------------------------------------------------------------------------
                    */

                    if (!$errors) {

                        $extension = $allowedMimeTypes[$mimeType];

                        $uploadedImageName =
                            "artwork_" .
                            bin2hex(random_bytes(16)) .
                            "." .
                            $extension;

                        $imageDestination =
                            $imageDirectory .
                            "/" .
                            $uploadedImageName;


                        if (
                            !move_uploaded_file(
                                $_FILES["image"]["tmp_name"],
                                $imageDestination
                            )
                        ) {
                            $errors[] = "Unable to save the uploaded artwork image.";
                            $uploadedImageName = null;
                        }
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | INSERT ARTWORK
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            try {

                $insertArtwork = $pdo->prepare("
                    INSERT INTO artworks (
                        exhibition_id,
                        artist_id,
                        title,
                        medium,
                        year_created,
                        dimensions,
                        description,
                        price,
                        image,
                        status,
                        created_at
                    )
                    VALUES (
                        :exhibition_id,
                        :artist_id,
                        :title,
                        :medium,
                        :year_created,
                        :dimensions,
                        :description,
                        :price,
                        :image,
                        'pending',
                        NOW()
                    )
                ");

                $insertArtwork->execute([
                    ":exhibition_id" => $exhibitionId,
                    ":artist_id" => $artistId,
                    ":title" => $title,
                    ":medium" => $medium,
                    ":year_created" => $yearCreated,
                    ":dimensions" => $dimensions,
                    ":description" => $description,
                    ":price" => ($price === "" ? null : $price),
                    ":image" => $uploadedImageName
                ]);


                /*
                |--------------------------------------------------------------------------
                | SUCCESS
                |--------------------------------------------------------------------------
                */

                header(
                    "Location: exhibition-artworks.php?id=" .
                    urlencode($exhibitionId) .
                    "&success=artwork_added"
                );

                exit;

            } catch (PDOException $e) {

                /*
                |--------------------------------------------------------------------------
                | DELETE UPLOADED IMAGE IF DATABASE INSERT FAILS
                |--------------------------------------------------------------------------
                */

                if ($uploadedImageName) {

                    $uploadedImagePath =
                        __DIR__ .
                        "/Images/" .
                        $uploadedImageName;

                    if (file_exists($uploadedImagePath)) {
                        unlink($uploadedImagePath);
                    }
                }

                $errors[] = "Unable to save the artwork. Please try again.";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE ARTWORK
    |--------------------------------------------------------------------------
    */

    elseif ($action === "remove_artwork") {

        $artworkId = filter_input(
            INPUT_POST,
            "artwork_id",
            FILTER_VALIDATE_INT
        );

        if (!$artworkId) {
            die("Invalid Artwork ID.");
        }


        /*
        |--------------------------------------------------------------------------
        | VERIFY ARTWORK BELONGS TO USER'S EXHIBITION
        |--------------------------------------------------------------------------
        */

        $artworkCheck = $pdo->prepare("
            SELECT
                a.id,
                a.image
            FROM artworks a
            INNER JOIN exhibitions e
                ON e.id = a.exhibition_id
            WHERE a.id = :artwork_id
              AND a.exhibition_id = :exhibition_id
              AND e.organizer_id = :user_id
            LIMIT 1
        ");

        $artworkCheck->execute([
            ":artwork_id" => $artworkId,
            ":exhibition_id" => $exhibitionId,
            ":user_id" => $userId
        ]);

        $artwork = $artworkCheck->fetch(PDO::FETCH_ASSOC);

        if (!$artwork) {
            die("Artwork not found or you do not have permission to remove it.");
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE ARTWORK
        |--------------------------------------------------------------------------
        */

        try {

            $deleteArtwork = $pdo->prepare("
                DELETE FROM artworks
                WHERE id = :artwork_id
            ");

            $deleteArtwork->execute([
                ":artwork_id" => $artworkId
            ]);


            /*
            |--------------------------------------------------------------------------
            | DELETE IMAGE FILE
            |--------------------------------------------------------------------------
            */

            if (!empty($artwork["image"])) {

                $imagePath =
                    __DIR__ .
                    "/Images/" .
                    basename($artwork["image"]);

                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            header(
                "Location: exhibition-artworks.php?id=" .
                urlencode($exhibitionId) .
                "&success=artwork_removed"
            );

            exit;

        } catch (PDOException $e) {

            $errors[] = "Unable to remove the artwork. Please try again.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

if (isset($_GET["success"])) {

    if ($_GET["success"] === "artwork_added") {
        $successMessage =
            "Artwork added successfully. It is now waiting for admin approval.";
    }

    elseif ($_GET["success"] === "artwork_removed") {
        $successMessage =
            "Artwork removed successfully.";
    }
}


/*
|--------------------------------------------------------------------------
| GET ASSIGNED ARTISTS
|--------------------------------------------------------------------------
*/

$artistStmt = $pdo->prepare("
    SELECT
        a.id,
        a.name
    FROM exhibition_artists ea
    INNER JOIN artists a
        ON a.id = ea.artist_id
    WHERE ea.exhibition_id = :exhibition_id
    ORDER BY a.name ASC
");

$artistStmt->execute([
    ":exhibition_id" => $exhibitionId
]);

$assignedArtists = $artistStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| GET ARTWORKS
|--------------------------------------------------------------------------
*/

$artworkStmt = $pdo->prepare("
    SELECT
        a.id,
        a.title,
        a.medium,
        a.year_created,
        a.dimensions,
        a.description,
        a.price,
        a.image,
        a.status,
        a.admin_note,
        a.created_at,
        ar.name AS artist_name
    FROM artworks a
    INNER JOIN artists ar
        ON ar.id = a.artist_id
    WHERE a.exhibition_id = :exhibition_id
    ORDER BY a.created_at DESC, a.id DESC
");

$artworkStmt->execute([
    ":exhibition_id" => $exhibitionId
]);

$artworks = $artworkStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include "includes/header.php"; ?>

<link rel="stylesheet" href="style.css">
<main class="exhibition-artworks-page">

    <!-- =====================================================
         HERO
         ===================================================== -->

    <section class="exhibition-artworks-hero">

        <div class="exhibition-artworks-heading">

            <p class="section-label">
                <?php echo htmlspecialchars(
                    $exhibition["title"],
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>
            </p>

            <h1>
                Exhibition Artworks
            </h1>

            <p class="exhibition-artworks-intro">
                Add and manage the artworks that will be presented
                in your exhibition. Each artwork will be reviewed
                by the gallery administrator before publication.
            </p>

        </div>

    </section>


    <!-- =====================================================
         MESSAGES
         ===================================================== -->

    <?php if ($successMessage || $errors): ?>

        <section class="artwork-message-section">

            <?php if ($successMessage): ?>

                <div class="artwork-success">
                    <?php echo htmlspecialchars(
                        $successMessage,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>
                </div>

            <?php endif; ?>


            <?php if ($errors): ?>

                <?php foreach ($errors as $error): ?>

                    <div class="artwork-error">

                        <?php echo htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    <?php endif; ?>


    <!-- =====================================================
         ADD ARTWORK
         ===================================================== -->

    <section class="add-artwork-section">

        <div class="artwork-section-heading">

            <p class="section-label">
                Add Artwork
            </p>

            <h2>
                Submit a New Artwork
            </h2>

        </div>


        <?php if (!$assignedArtists): ?>

            <div class="no-artworks-message">

                <h3>
                    No Artists Assigned
                </h3>

                <p>
                    You need to assign at least one artist to this
                    exhibition before adding an artwork.
                </p>

                <a
                    href="exhibition-artists.php?id=<?php echo urlencode($exhibitionId); ?>"
                    class="outline-button"
                >
                    Manage Artists
                </a>

            </div>

        <?php else: ?>

            <form
                method="POST"
                enctype="multipart/form-data"
                class="add-artwork-form"
            >

                <?php echo csrf_field(); ?>

                <input
                    type="hidden"
                    name="action"
                    value="add_artwork"
                >


                <!-- TITLE -->

                <div class="artwork-form-group">

                    <label for="title">
                        Artwork Title
                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="255"
                        required
                        value="<?php echo htmlspecialchars(
                            $_POST["title"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                    >

                </div>


                <!-- ARTIST -->

                <div class="artwork-form-group">

                    <label for="artist_id">
                        Artist
                    </label>

                    <select
                        id="artist_id"
                        name="artist_id"
                        required
                    >

                        <option value="">
                            Select Artist
                        </option>

                        <?php foreach ($assignedArtists as $artist): ?>

                            <option
                                value="<?php echo (int)$artist["id"]; ?>"
                                <?php
                                echo (
                                    isset($_POST["artist_id"]) &&
                                    (int)$_POST["artist_id"] === (int)$artist["id"]
                                )
                                ? "selected"
                                : "";
                                ?>
                            >

                                <?php echo htmlspecialchars(
                                    $artist["name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- MEDIUM -->

                <div class="artwork-form-group">

                    <label for="medium">
                        Medium
                    </label>

                    <input
                        type="text"
                        id="medium"
                        name="medium"
                        maxlength="255"
                        placeholder="e.g. Oil on canvas"
                        required
                        value="<?php echo htmlspecialchars(
                            $_POST["medium"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                    >

                </div>


                <!-- YEAR -->

                <div class="artwork-form-group">

                    <label for="year_created">
                        Year Created
                    </label>

                    <input
                        type="text"
                        id="year_created"
                        name="year_created"
                        maxlength="4"
                        pattern="\d{4}"
                        placeholder="e.g. 2026"
                        required
                        value="<?php echo htmlspecialchars(
                            $_POST["year_created"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                    >

                </div>


                <!-- DIMENSIONS -->

                <div class="artwork-form-group">

                    <label for="dimensions">
                        Dimensions
                    </label>

                    <input
                        type="text"
                        id="dimensions"
                        name="dimensions"
                        maxlength="255"
                        placeholder="e.g. 24 × 36 inches"
                        required
                        value="<?php echo htmlspecialchars(
                            $_POST["dimensions"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                    >

                </div>


                <!-- PRICE -->

                <div class="artwork-form-group">

                    <label for="price">
                        Price
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        placeholder="Optional"
                        value="<?php echo htmlspecialchars(
                            $_POST["price"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                    >

                    <small>
                        Leave blank if the artwork is not for sale.
                    </small>

                </div>


                <!-- DESCRIPTION -->

                <div class="artwork-form-group artwork-full-width">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="6"
                        required
                    ><?php echo htmlspecialchars(
                        $_POST["description"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?></textarea>

                </div>


                <!-- IMAGE -->

                <div class="artwork-form-group artwork-full-width">

                    <label for="image">
                        Artwork Image
                    </label>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <small>
                        JPG, PNG, or WEBP. Maximum file size: 5MB.
                    </small>

                </div>


                <!-- SUBMIT -->

                <div class="artwork-form-submit">

                    <button
                        type="submit"
                        class="orange-button"
                    >
                        Add Artwork
                    </button>

                </div>

            </form>

        <?php endif; ?>

    </section>


    <!-- =====================================================
         CURRENT ARTWORKS
         ===================================================== -->

    <section class="current-artworks-section">

        <div class="artwork-section-heading">

            <p class="section-label">
                Current Artworks
            </p>

            <h2>
                Exhibition Collection
            </h2>

        </div>


        <?php if (!$artworks): ?>

            <div class="no-artworks-message">

                <h3>
                    No Artworks Yet
                </h3>

                <p>
                    No artworks have been submitted for this exhibition.
                </p>

            </div>

        <?php else: ?>

            <div class="exhibition-artwork-grid">

                <?php foreach ($artworks as $artwork): ?>

                    <article class="exhibition-artwork-card">


                        <!-- IMAGE -->

                        <div class="exhibition-artwork-image">

                            <?php if (!empty($artwork["image"])): ?>

                                <img
                                    src="Images/<?php echo htmlspecialchars(
                                        basename($artwork["image"]),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"
                                    alt="<?php echo htmlspecialchars(
                                        $artwork["title"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"
                                >

                            <?php else: ?>

                                <div class="artwork-no-image">
                                    NO IMAGE
                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- INFO -->

                        <div class="exhibition-artwork-info">

                            <p class="artwork-artist-name">

                                <?php echo htmlspecialchars(
                                    $artwork["artist_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>

                            </p>


                            <h3>

                                <?php echo htmlspecialchars(
                                    $artwork["title"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>

                            </h3>


                            <p>
                                <strong>Medium:</strong>

                                <?php echo htmlspecialchars(
                                    $artwork["medium"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>

                            </p>


                            <p>
                                <strong>Year:</strong>

                                <?php echo htmlspecialchars(
                                    $artwork["year_created"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>

                            </p>


                            <p>
                                <strong>Dimensions:</strong>

                                <?php echo htmlspecialchars(
                                    $artwork["dimensions"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>

                            </p>


                            <?php if (
                                $artwork["price"] !== null &&
                                $artwork["price"] !== ""
                            ): ?>

                                <p>

                                    <strong>Price:</strong>

                                    ₱<?php echo number_format(
                                        (float)$artwork["price"],
                                        2
                                    ); ?>

                                </p>

                            <?php endif; ?>


                            <p class="artwork-description">

                                <?php echo htmlspecialchars(
                                    $artwork["description"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>

                            </p>


                            <!-- STATUS -->

                            <div class="artwork-status">

                                <?php if ($artwork["status"] === "pending"): ?>

                                    <span class="artwork-status-pending">
                                        PENDING REVIEW
                                    </span>

                                <?php elseif ($artwork["status"] === "approved"): ?>

                                    <span class="artwork-status-approved">
                                        APPROVED
                                    </span>

                                <?php elseif ($artwork["status"] === "rejected"): ?>

                                    <span class="artwork-status-rejected">
                                        REJECTED
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- ADMIN NOTE -->

                            <?php if (!empty($artwork["admin_note"])): ?>

                                <p class="artwork-admin-note">

                                    <strong>
                                        Admin Note:
                                    </strong>

                                    <?php echo htmlspecialchars(
                                        $artwork["admin_note"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>

                                </p>

                            <?php endif; ?>


                            <!-- REMOVE -->

                            <form
                                method="POST"
                                class="remove-artwork-form"
                                onsubmit="return confirm('Are you sure you want to remove this artwork?');"
                            >

                                <?php echo csrf_field(); ?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="remove_artwork"
                                >

                                <input
                                    type="hidden"
                                    name="artwork_id"
                                    value="<?php echo (int)$artwork["id"]; ?>"
                                >

                                <button
                                    type="submit"
                                    class="remove-artwork-button"
                                >
                                    Remove Artwork
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>



    <section class="artworks-next-section">

        <p class="section-label">
            Exhibition Management
        </p>

        <h2>
            Continue Managing Your Exhibition
        </h2>

        <p>
            You can manage the artists assigned to this exhibition
            or return to your dashboard.
        </p>


        <div class="artwork-navigation-buttons">

            <a
                href="exhibition-artists.php?id=<?php echo urlencode($exhibitionId); ?>"
                class="outline-button"
            >
                Manage Artists
            </a>

            <a
                href="dashboard.php"
                class="outline-button"
            >
                Back to Dashboard
            </a>

        </div>

    </section>

</main>


<?php include "includes/footer.php"; ?>
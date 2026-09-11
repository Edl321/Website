<?php

session_start();

require_once "../database/config.php";
require_once "../security/shield.php";
require_once "../security/authorize.php";


// --------------------------------------------------
// ADMIN ACCESS
// --------------------------------------------------

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit;
}


// --------------------------------------------------
// VARIABLES
// --------------------------------------------------

$errors = [];

$title = "";
$description = "";
$start_date = "";
$end_date = "";


// --------------------------------------------------
// FORM SUBMISSION
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ------------------------------
    // CSRF CHECK
    // ------------------------------

    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token. Please try again.";
    }


    // ------------------------------
    // GET FORM VALUES
    // ------------------------------

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $start_date = $_POST["start_date"] ?? "";
    $end_date = $_POST["end_date"] ?? "";


    // ------------------------------
    // VALIDATION
    // ------------------------------

    if ($title === "") {
        $errors[] = "Exhibition title is required.";
    } elseif (strlen($title) > 255) {
        $errors[] = "Exhibition title must not exceed 255 characters.";
    }


    if ($description === "") {
        $errors[] = "Exhibition description is required.";
    }


    if ($start_date === "") {
        $errors[] = "Start date is required.";
    }


    if ($end_date === "") {
        $errors[] = "End date is required.";
    }


    // ------------------------------
    // CHECK DATE ORDER
    // ------------------------------

    if ($start_date !== "" && $end_date !== "") {

        if ($end_date < $start_date) {
            $errors[] = "End date cannot be earlier than the start date.";
        }
    }


    // --------------------------------------------------
    // IMAGE UPLOAD
    // --------------------------------------------------

    $imagePath = null;

    if (
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

            $errors[] = "There was a problem uploading the image.";

        } else {

            $maxFileSize = 5 * 1024 * 1024; // 5 MB

            if ($_FILES["image"]["size"] > $maxFileSize) {

                $errors[] = "Image must not exceed 5 MB.";

            } else {

                $tmpFile = $_FILES["image"]["tmp_name"];

                // Check actual MIME type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpFile);
                finfo_close($finfo);

                $allowedTypes = [
                    "image/jpeg" => "jpg",
                    "image/png"  => "png",
                    "image/webp" => "webp"
                ];

                if (!array_key_exists($mimeType, $allowedTypes)) {

                    $errors[] = "Only JPG, PNG, and WEBP images are allowed.";

                } else {

                    // Create upload folder if it does not exist
                    $uploadDirectory = "../Images/exhibitions/";

                    if (!is_dir($uploadDirectory)) {
                        mkdir($uploadDirectory, 0755, true);
                    }

                    // Generate random filename
                    $extension = $allowedTypes[$mimeType];

                    $newFileName =
                        bin2hex(random_bytes(16)) .
                        "." .
                        $extension;

                    $destination =
                        $uploadDirectory .
                        $newFileName;

                    if (move_uploaded_file($tmpFile, $destination)) {

                        $imagePath =
                            "Images/exhibitions/" .
                            $newFileName;

                    } else {

                        $errors[] =
                            "The image could not be saved.";
                    }
                }
            }
        }
    }


    // --------------------------------------------------
    // INSERT EXHIBITION
    // --------------------------------------------------

    if (empty($errors)) {

        try {

            $sql = "
                INSERT INTO exhibitions
                (
                    inquiry_id,
                    organizer_id,
                    title,
                    description,
                    start_date,
                    end_date,
                    image,
                    status
                )
                VALUES
                (
                    NULL,
                    :organizer_id,
                    :title,
                    :description,
                    :start_date,
                    :end_date,
                    :image,
                    'draft'
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->bindValue(
                ":organizer_id",
                $_SESSION["user_id"],
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ":title",
                $title
            );

            $stmt->bindValue(
                ":description",
                $description
            );

            $stmt->bindValue(
                ":start_date",
                $start_date
            );

            $stmt->bindValue(
                ":end_date",
                $end_date
            );

            $stmt->bindValue(
                ":image",
                $imagePath
            );

            $stmt->execute();


            // Redirect to exhibition management
            header("Location: exhibitions.php?created=1");
            exit;


        } catch (PDOException $e) {

            $errors[] =
                "The exhibition could not be created.";

            // Delete uploaded image if database insert fails
            if ($imagePath !== null) {

                $uploadedFile =
                    "../" . $imagePath;

                if (file_exists($uploadedFile)) {
                    unlink($uploadedFile);
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Exhibition | EDL Gallery</title>

    <link
        rel="stylesheet"
        href="exhibition-create.css"
    >

</head>

<body>

<div class="admin-page">

    <!-- ----------------------------------------- -->
    <!-- PAGE HEADER -->
    <!-- ----------------------------------------- -->

    <div class="admin-page-header">

        <div>

            <p class="eyebrow">
                EDL GALLERY ADMIN
            </p>

            <h1>
                Create Exhibition
            </h1>

            <p class="page-description">
                Create a new exhibition and save it as a draft.
            </p>

        </div>

        <a
            href="exhibitions.php"
            class="back-button"
        >
            ← Back to Exhibitions
        </a>

    </div>


    <!-- ----------------------------------------- -->
    <!-- ERROR MESSAGES -->
    <!-- ----------------------------------------- -->

    <?php if (!empty($errors)): ?>

        <div class="error-box">

            <?php foreach ($errors as $error): ?>

                <p>
                    <?php echo htmlspecialchars($error); ?>
                </p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- ----------------------------------------- -->
    <!-- CREATE FORM -->
    <!-- ----------------------------------------- -->

    <section class="form-card">

        <form
            method="POST"
            action=""
            enctype="multipart/form-data"
        >

            <?php echo csrf_field(); ?>


            <!-- TITLE -->

            <div class="form-group">

                <label for="title">
                    Exhibition Title
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="255"
                    value="<?php echo htmlspecialchars($title); ?>"
                    placeholder="Enter exhibition title"
                    required
                >

            </div>


            <!-- DESCRIPTION -->

            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="7"
                    placeholder="Describe the exhibition..."
                    required
                ><?php echo htmlspecialchars($description); ?></textarea>

            </div>


            <!-- DATES -->

            <div class="date-row">

                <div class="form-group">

                    <label for="start_date">
                        Start Date
                    </label>

                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="<?php echo htmlspecialchars($start_date); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="end_date">
                        End Date
                    </label>

                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="<?php echo htmlspecialchars($end_date); ?>"
                        required
                    >

                </div>

            </div>


            <!-- IMAGE -->

            <div class="form-group">

                <label for="image">
                    Exhibition Image
                </label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <p class="field-help">
                    Optional. JPG, PNG, or WEBP. Maximum size: 5 MB.
                </p>

            </div>


            <!-- STATUS -->

            <div class="form-group">

                <label>
                    Initial Status
                </label>

                <div class="status-display">
                    DRAFT
                </div>

                <p class="field-help">
                    New exhibitions are saved as drafts.
                    You can publish them later.
                </p>

            </div>


            <!-- BUTTONS -->

            <div class="form-actions">

                <a
                    href="exhibitions.php"
                    class="cancel-button"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="save-button"
                >
                    Create Exhibition
                </button>

            </div>

        </form>

    </section>

</div>

</body>

</html>
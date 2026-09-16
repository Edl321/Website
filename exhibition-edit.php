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


$exhibitionId = $_GET["id"] ?? "";

if (!ctype_digit((string)$exhibitionId)) {

    header("Location: my-exhibitions.php");
    exit;

}

$exhibitionId = (int)$exhibitionId;


$sql = "SELECT
            e.*,
            ei.exhibition_type AS planned_exhibition_type
        FROM exhibitions e
        LEFT JOIN exhibition_inquiries ei
            ON ei.id = e.inquiry_id
        WHERE e.id = :id
          AND e.organizer_id = :organizer_id";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $exhibitionId, PDO::PARAM_INT);
$stmt->bindValue(":organizer_id", $userId, PDO::PARAM_INT);
$stmt->execute();

$exhibition = $stmt->fetch();

if (!$exhibition) {

    header("Location: my-exhibitions.php");
    exit;

}

$isEditable = in_array($exhibition["status"], ["draft", "artwork_submission"], true);

$canChangeType = $isEditable && !empty($exhibition["inquiry_id"]);


$errors   = [];
$feedback = "";

$title       = $exhibition["title"];
$description = $exhibition["description"];
$start_date  = $exhibition["start_date"];
$end_date    = $exhibition["end_date"];
$exhibitionType = $exhibition["planned_exhibition_type"] ?? "";

$typeOptions = [
    "Solo Exhibition",
    "Group Exhibition",
    "Community Exhibition",
    "Corporate / Brand Exhibition",
    "Other"
];


if ($_SERVER["REQUEST_METHOD"] === "POST" && $isEditable) {

    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token. Please try again.";
    }

    $title       = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $start_date  = trim($_POST["start_date"] ?? "");
    $end_date    = trim($_POST["end_date"] ?? "");

    $newExhibitionType = trim($_POST["exhibition_type"] ?? $exhibitionType);

    if (empty($title)) {
        $errors[] = "Exhibition title is required.";
    }

    if (empty($description)) {
        $errors[] = "Exhibition description is required.";
    }

    if (empty($start_date)) {
        $errors[] = "Start date is required.";
    }

    if (empty($end_date)) {
        $errors[] = "End date is required.";
    }

    if (
        !empty($start_date) &&
        !empty($end_date) &&
        strtotime($end_date) < strtotime($start_date)
    ) {
        $errors[] = "End date cannot be before the start date.";
    }


    /*
    |--------------------------------------------------------------------------
    | EXHIBITION TYPE CHANGE VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $canChangeType &&
        $newExhibitionType !== $exhibitionType &&
        in_array($newExhibitionType, $typeOptions, true)
    ) {

        if ($newExhibitionType === "Solo Exhibition") {

            $artistCountStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM exhibition_artists
                WHERE exhibition_id = :exhibition_id
            ");
            $artistCountStmt->bindValue(":exhibition_id", $exhibitionId, PDO::PARAM_INT);
            $artistCountStmt->execute();

            $attachedArtists = (int)$artistCountStmt->fetchColumn();

            if ($attachedArtists > 1) {

                $errors[] = "You currently have " . $attachedArtists .
                    " artists attached. Remove extras before switching to a Solo Exhibition.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | OPTIONAL IMAGE UPLOAD
    |--------------------------------------------------------------------------
    */

    $imagePath = $exhibition["image"];
    $newImageUploaded = false;

    if (!empty($_FILES["image"]["name"])) {

        $allowedTypes = ["image/jpeg", "image/png", "image/webp"];
        $maxSizeBytes = 5 * 1024 * 1024;

        $fileTmpPath = $_FILES["image"]["tmp_name"];
        $fileType    = mime_content_type($fileTmpPath);
        $fileSize    = $_FILES["image"]["size"];

        if (!in_array($fileType, $allowedTypes, true)) {

            $errors[] = "Exhibition image must be a JPG, PNG, or WEBP file.";

        } elseif ($fileSize > $maxSizeBytes) {

            $errors[] = "Exhibition image must be smaller than 5MB.";

        } else {

            $extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $newFileName = "exhibition_" . $exhibitionId . "_" . uniqid() . "." . $extension;

            $uploadDir = "Images/exhibitions/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destination)) {

                $imagePath = "Images/exhibitions/" . $newFileName;
                $newImageUploaded = true;

            } else {

                $errors[] = "There was a problem uploading your image. Please try again.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $newStatus = ($exhibition["status"] === "draft")
            ? "artwork_submission"
            : $exhibition["status"];

        $updateSql = "UPDATE exhibitions
                    SET title = :title,
                        description = :description,
                        start_date = :start_date,
                        end_date = :end_date,
                        image = :image,
                        status = :status
                    WHERE id = :id AND organizer_id = :organizer_id";

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindValue(":title", $title);
        $updateStmt->bindValue(":description", $description);
        $updateStmt->bindValue(":start_date", $start_date);
        $updateStmt->bindValue(":end_date", $end_date);
        $updateStmt->bindValue(":image", $imagePath);
        $updateStmt->bindValue(":status", $newStatus);
        $updateStmt->bindValue(":id", $exhibitionId, PDO::PARAM_INT);
        $updateStmt->bindValue(":organizer_id", $userId, PDO::PARAM_INT);
        $updateStmt->execute();


        /*
        |--------------------------------------------------------------------------
        | ALSO UPDATE THE INQUIRY TYPE (if linked and changed)
        |--------------------------------------------------------------------------
        */

        if (
            $canChangeType &&
            $newExhibitionType !== $exhibitionType &&
            in_array($newExhibitionType, $typeOptions, true)
        ) {

            $updateType = $pdo->prepare("
                UPDATE exhibition_inquiries
                SET exhibition_type = :exhibition_type
                WHERE id = :inquiry_id
            ");

            $updateType->bindValue(":exhibition_type", $newExhibitionType);
            $updateType->bindValue(":inquiry_id", (int)$exhibition["inquiry_id"], PDO::PARAM_INT);
            $updateType->execute();

            $exhibitionType = $newExhibitionType;

        }


        if ($newImageUploaded) {

            $oldImage = (string)$exhibition["image"];

            $deletePrefixes = [
                "Images/exhibitions/exhibition_",
                "uploads/exhibitions/exhibition_",
            ];

            foreach ($deletePrefixes as $prefix) {

                if (
                    $oldImage !== "" &&
                    strpos($oldImage, $prefix) === 0 &&
                    strpos($oldImage, "..") === false &&
                    is_file($oldImage)
                ) {
                    @unlink($oldImage);
                    break;
                }

            }

        }

        $exhibition["title"]       = $title;
        $exhibition["description"] = $description;
        $exhibition["start_date"]  = $start_date;
        $exhibition["end_date"]    = $end_date;
        $exhibition["image"]       = $imagePath;
        $exhibition["status"]      = $newStatus;

        $isEditable = in_array($newStatus, ["draft", "artwork_submission"], true);

        $feedback = "Your exhibition details have been saved.";

    }

}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exhibition | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php require_once "includes/header.php"; ?>


<section class="dashboard-content dashboard-content--top-spaced">

    <a href="my-exhibitions.php" class="back-dashboard-link">
        &larr; Back to My Exhibitions
    </a>

    <div class="dashboard-heading">
        <p>EXHIBITION SETUP</p>
        <h2><?php echo htmlspecialchars($exhibition["title"]); ?></h2>
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


    <?php if (!$isEditable): ?>

        <div class="form-errors form-errors--neutral">
            <p>
                This exhibition is currently
                "<?php echo htmlspecialchars(strtoupper($exhibition["status"])); ?>"
                and can no longer be edited here.
            </p>
        </div>

    <?php endif; ?>


    <form
        class="inquiry-form"
        method="POST"
        action="exhibition-edit.php?id=<?php echo $exhibitionId; ?>"
        enctype="multipart/form-data"
    >

        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label for="title">EXHIBITION TITLE</label>
            <input
                type="text"
                id="title"
                name="title"
                value="<?php echo htmlspecialchars($title); ?>"
                <?php echo $isEditable ? "required" : "disabled"; ?>
            >
        </div>


        <?php if ($canChangeType): ?>

            <div class="form-group">

                <label for="exhibition_type">EXHIBITION TYPE</label>

                <select
                    id="exhibition_type"
                    name="exhibition_type"
                    <?php echo $isEditable ? "" : "disabled"; ?>
                >
                    <?php foreach ($typeOptions as $typeOpt): ?>

                        <option
                            value="<?php echo htmlspecialchars($typeOpt); ?>"
                            <?php echo ($exhibitionType === $typeOpt) ? "selected" : ""; ?>
                        >
                            <?php echo htmlspecialchars($typeOpt); ?>
                        </option>

                    <?php endforeach; ?>
                </select>

                <small class="field-help">
                    Changing to <strong>Solo Exhibition</strong> requires
                    having at most one artist attached.
                </small>

            </div>

        <?php endif; ?>


        <div class="form-row">

            <div class="form-group">
                <label for="start_date">START DATE</label>
                <input
                    type="date"
                    id="start_date"
                    name="start_date"
                    value="<?php echo htmlspecialchars($start_date); ?>"
                    <?php echo $isEditable ? "required" : "disabled"; ?>
                >
            </div>

            <div class="form-group">
                <label for="end_date">END DATE</label>
                <input
                    type="date"
                    id="end_date"
                    name="end_date"
                    value="<?php echo htmlspecialchars($end_date); ?>"
                    <?php echo $isEditable ? "required" : "disabled"; ?>
                >
            </div>

        </div>

        <div class="form-group">
            <label for="description">DESCRIPTION</label>
            <textarea
                id="description"
                name="description"
                rows="6"
                <?php echo $isEditable ? "required" : "disabled"; ?>
            ><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <div class="form-group">

            <label for="image">EXHIBITION IMAGE (OPTIONAL)</label>

            <?php if (!empty($exhibition["image"])): ?>

                <img src="<?php echo htmlspecialchars($exhibition['image']); ?>" alt="Current exhibition image" class="exhibition-edit-preview-img">

            <?php endif; ?>

            <?php if ($isEditable): ?>

                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">

            <?php endif; ?>

        </div>

        <?php if ($isEditable): ?>
            <button type="submit" class="submit-button"> SAVE EXHIBITION DETAILS </button>
        <?php endif; ?>

    </form>

</section>


<?php require_once "includes/footer.php"; ?>

</body>
</html>
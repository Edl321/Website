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


// ---- GET THE EXHIBITION ID FROM THE URL ----

$exhibitionId = $_GET["id"] ?? "";

if (!ctype_digit((string)$exhibitionId)) {

    header("Location: my-exhibitions.php");
    exit;

}

$exhibitionId = (int)$exhibitionId;


// ---- LOAD THE EXHIBITION, MAKE SURE IT BELONGS TO THIS USER ----
// (We check organizer_id = the logged-in user's session ID so nobody
// can edit someone else's exhibition just by changing the URL.)

$sql = "SELECT * FROM exhibitions
        WHERE id = :id AND organizer_id = :organizer_id";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $exhibitionId, PDO::PARAM_INT);
$stmt->bindValue(":organizer_id", $userId, PDO::PARAM_INT);
$stmt->execute();

$exhibition = $stmt->fetch();

if (!$exhibition) {

    header("Location: my-exhibitions.php");
    exit;

}


// Once an exhibition is published, completed, or cancelled,
// the organizer should no longer edit the core details.
$isEditable = in_array($exhibition["status"], ["draft", "artwork_submission"], true);


$errors   = [];
$feedback = "";

// Pre-fill values from the current exhibition record.
$title       = $exhibition["title"];
$description = $exhibition["description"];
$start_date  = $exhibition["start_date"];
$end_date    = $exhibition["end_date"];


if ($_SERVER["REQUEST_METHOD"] === "POST" && $isEditable) {

    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token. Please try again.";
    }

    $title       = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $start_date  = trim($_POST["start_date"] ?? "");
    $end_date    = trim($_POST["end_date"] ?? "");


    // ---- VALIDATION ----

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


    // ---- OPTIONAL IMAGE UPLOAD ----

    $imagePath = $exhibition["image"]; // keep the existing image unless replaced

    if (!empty($_FILES["image"]["name"])) {

        $allowedTypes = ["image/jpeg", "image/png", "image/webp"];
        $maxSizeBytes = 5 * 1024 * 1024; // 5 MB

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

            $uploadDir = "uploads/exhibitions/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $imagePath = $destination;
            } else {
                $errors[] = "There was a problem uploading your image. Please try again.";
            }

        }

    }


    // ---- SAVE ----

    if (empty($errors)) {

        // Completing the details for the first time moves the
        // exhibition from "draft" into "artwork_submission", so the
        // organizer's next step is adding artists and artworks.
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

        // Refresh $exhibition so the page below shows the saved data.
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
    <link rel="icon" type="image/x-icon" href="image/logo.png">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php require_once "includes/header.php"; ?>


<section class="dashboard-content" style="padding-top: 60px;">

    <a href="my-exhibitions.php" class="back-dashboard-link">
        &larr;Back to My Exhibitions
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

        <div class="form-errors" style="border-left-color:#888; background-color:#f2f2f2;">
            <p style="color:#555;">
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

                <img
                    src="<?php echo htmlspecialchars($exhibition['image']); ?>"
                    alt="Current exhibition image"
                    style="max-width:220px; display:block; margin-bottom:12px;"
                >

            <?php endif; ?>

            <?php if ($isEditable): ?>

                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">

            <?php endif; ?>

        </div>


        <?php if ($isEditable): ?>

            <button type="submit" class="submit-button">
                SAVE EXHIBITION DETAILS
            </button>

        <?php endif; ?>

    </form>

</section>


<?php require_once "includes/footer.php"; ?>

</body>
</html>

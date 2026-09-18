<?php

$basePath = "";

require_once "database/config.php";
require_once "security/authorize.php";
require_once "security/shield.php";

if (!isUser()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];

$errors = [];

$exhibition_title       = "";
$exhibition_description = "";
$preferred_start_date   = "";
$preferred_end_date     = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token. Please try again.";
    }

    $exhibition_title       = trim($_POST["exhibition_title"] ?? "");
    $exhibition_description = trim($_POST["exhibition_description"] ?? "");
    $preferred_start_date   = trim($_POST["preferred_start_date"] ?? "");
    $preferred_end_date     = trim($_POST["preferred_end_date"] ?? "");

    if ($exhibition_title === "") {
        $errors[] = "Exhibition title is required.";
    }

    if ($exhibition_description === "") {
        $errors[] = "Exhibition description is required.";
    }

    if ($preferred_start_date === "") {
        $errors[] = "Start date is required.";
    }

    if ($preferred_end_date === "") {
        $errors[] = "End date is required.";
    }

    if (
        $preferred_start_date !== "" &&
        $preferred_end_date !== "" &&
        strtotime($preferred_end_date) < strtotime($preferred_start_date)
    ) {
        $errors[] = "End date cannot be before the start date.";
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            INSERT INTO rental_applications
                (user_id, exhibition_title, exhibition_description,
                 preferred_start_date, preferred_end_date, status, created_at)
            VALUES
                (:user_id, :title, :description,
                 :start_date, :end_date, 'pending', NOW())
        ");

        $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
        $stmt->bindValue(":title", $exhibition_title);
        $stmt->bindValue(":description", $exhibition_description);
        $stmt->bindValue(":start_date", $preferred_start_date);
        $stmt->bindValue(":end_date", $preferred_end_date);
        $stmt->execute();

        header("Location: my-rentals.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Rental | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php require_once "includes/header.php"; ?>

<section class="dashboard-hero dashboard-hero--submit-inquiry">
    <div class="dashboard-hero-content">
        <p class="dashboard-label">EDL GALLERY</p>
        <h1>Apply to <span>Rent the Gallery</span></h1>
        <p class="dashboard-description">
            Tell us about your exhibition and the dates you'd like to book the space.
            The gallery will review and respond with a rental fee if approved.
        </p>
    </div>
</section>

<section class="dashboard-content">

    <a href="rent-space.php" class="back-dashboard-link">
        &larr; Back to Rent Our Space
    </a>

    <div class="dashboard-heading">
        <p>RENTAL APPLICATION</p>
        <h2>Your Booking Request</h2>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="form-errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="inquiry-form" method="POST" action="apply-for-rental.php">

        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label for="exhibition_title">EXHIBITION TITLE</label>
            <input
                type="text"
                id="exhibition_title"
                name="exhibition_title"
                value="<?php echo htmlspecialchars($exhibition_title); ?>"
                maxlength="255"
                placeholder="Title of your proposed exhibition"
                required
            >
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="preferred_start_date">PREFERRED START DATE</label>
                <input
                    type="date"
                    id="preferred_start_date"
                    name="preferred_start_date"
                    min="<?php echo date('Y-m-d'); ?>"
                    value="<?php echo htmlspecialchars($preferred_start_date); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="preferred_end_date">PREFERRED END DATE</label>
                <input
                    type="date"
                    id="preferred_end_date"
                    name="preferred_end_date"
                    min="<?php echo date('Y-m-d'); ?>"
                    value="<?php echo htmlspecialchars($preferred_end_date); ?>"
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label for="exhibition_description">EXHIBITION DESCRIPTION</label>
            <textarea
                id="exhibition_description"
                name="exhibition_description"
                rows="6"
                maxlength="5000"
                placeholder="Describe your exhibition, the artists involved, and how you'll use the space..."
                required
            ><?php echo htmlspecialchars($exhibition_description); ?></textarea>
        </div>

        <button type="submit" class="submit-button">
            SUBMIT RENTAL APPLICATION
        </button>

    </form>

</section>

<?php require_once "includes/footer.php"; ?>

</body>
</html>
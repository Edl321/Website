<?php

session_start();
$basePath = "";

require_once "database/config.php";
require_once "security/authorize.php";
require_once "security/shield.php";


// Only logged-in users may submit an inquiry.
if (!isLoggedIn()) {

    header("Location: login.php");
    exit;

}


// The user's identity always comes from the session, never from the form.
$userId = $_SESSION["user_id"];


$errors = [];

// Values used to re-fill the form if validation fails.
$organization_name    = "";
$phone                = "";
$exhibition_title     = "";
$exhibition_type      = "";
$proposed_start_date  = "";
$proposed_end_date    = "";
$artist_count         = "";
$artwork_count        = "";
$expected_visitors    = "";
$description          = "";
$special_requirements = "";
$additional_notes     = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Check CSRF token
    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token. Please try again.";
    }


    // Get and trim form values
    $organization_name    = trim($_POST["organization_name"] ?? "");
    $phone                = trim($_POST["phone"] ?? "");
    $exhibition_title     = trim($_POST["exhibition_title"] ?? "");
    $exhibition_type      = trim($_POST["exhibition_type"] ?? "");
    $proposed_start_date  = trim($_POST["proposed_start_date"] ?? "");
    $proposed_end_date    = trim($_POST["proposed_end_date"] ?? "");
    $artist_count         = trim($_POST["artist_count"] ?? "");
    $artwork_count        = trim($_POST["artwork_count"] ?? "");
    $expected_visitors    = trim($_POST["expected_visitors"] ?? "");
    $description          = trim($_POST["description"] ?? "");
    $special_requirements = trim($_POST["special_requirements"] ?? "");
    $additional_notes     = trim($_POST["additional_notes"] ?? "");


    // ---- VALIDATION ----

    if (empty($organization_name)) {
        $errors[] = "Organization / Artist name is required.";
    }

    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }

    if (empty($exhibition_title)) {
        $errors[] = "Exhibition title is required.";
    }

    if (empty($exhibition_type)) {
        $errors[] = "Please select an exhibition type.";
    }

    if (empty($proposed_start_date)) {
        $errors[] = "Proposed start date is required.";
    }

    if (empty($proposed_end_date)) {
        $errors[] = "Proposed end date is required.";
    }

    // Only compare dates if both were actually provided
    if (
        !empty($proposed_start_date) &&
        !empty($proposed_end_date) &&
        strtotime($proposed_end_date) < strtotime($proposed_start_date)
    ) {
        $errors[] = "End date cannot be before the start date.";
    }

    if (empty($description)) {
        $errors[] = "Exhibition description is required.";
    }

    // Numbers must be whole numbers of at least 1
    if (
        $artist_count === "" ||
        !ctype_digit($artist_count) ||
        (int)$artist_count < 1
    ) {
        $errors[] = "Please enter a valid number of artists.";
    }

    if (
        $artwork_count === "" ||
        !ctype_digit($artwork_count) ||
        (int)$artwork_count < 1
    ) {
        $errors[] = "Please enter a valid expected number of artworks.";
    }

    if (
        $expected_visitors === "" ||
        !ctype_digit($expected_visitors) ||
        (int)$expected_visitors < 1
    ) {
        $errors[] = "Please enter a valid expected number of visitors.";
    }


    // ---- SAVE TO DATABASE ----

    if (empty($errors)) {

        $sql = "INSERT INTO exhibition_inquiries
                (user_id, organization_name, phone, exhibition_title,
                exhibition_type, proposed_start_date, proposed_end_date,
                artist_count, artwork_count, expected_visitors,
                description, special_requirements, additional_notes,
                status, created_at)
                VALUES
                (:user_id, :organization_name, :phone, :exhibition_title,
                :exhibition_type, :proposed_start_date, :proposed_end_date,
                :artist_count, :artwork_count, :expected_visitors,
                :description, :special_requirements, :additional_notes,
                'pending', NOW())";

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
        $stmt->bindValue(":organization_name", $organization_name);
        $stmt->bindValue(":phone", $phone);
        $stmt->bindValue(":exhibition_title", $exhibition_title);
        $stmt->bindValue(":exhibition_type", $exhibition_type);
        $stmt->bindValue(":proposed_start_date", $proposed_start_date);
        $stmt->bindValue(":proposed_end_date", $proposed_end_date);
        $stmt->bindValue(":artist_count", (int)$artist_count, PDO::PARAM_INT);
        $stmt->bindValue(":artwork_count", (int)$artwork_count, PDO::PARAM_INT);
        $stmt->bindValue(":expected_visitors", (int)$expected_visitors, PDO::PARAM_INT);
        $stmt->bindValue(":description", $description);
        $stmt->bindValue(":special_requirements", $special_requirements);
        $stmt->bindValue(":additional_notes", $additional_notes);

        $stmt->execute();

        // Send the user to their inquiries list
        header("Location: my-inquiries.php");
        exit;

    }

}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Submit an Inquiry | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php require_once "includes/header.php"; ?>


<!-- =========================================
     PAGE HERO
========================================= -->

<section class="dashboard-hero" style="background-image:
    linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)),
    url('image/background-1.jpg');">

    <div class="dashboard-hero-content">

        <p class="dashboard-label">EDL GALLERY</p>

        <h1>
            Submit Your <span>Exhibition Inquiry</span>
        </h1>

        <p class="dashboard-description">
            Tell us about your proposed exhibition. Our team will
            review your request and get back to you.
        </p>

    </div>

</section>


<!-- =========================================
     INQUIRY FORM
========================================= -->

<section class="dashboard-content">

    <div class="dashboard-heading">
        <p>EXHIBITION INQUIRY</p>
        <h2>Tell Us About Your Exhibition</h2>
    </div>


    <!-- ERROR MESSAGES -->

    <?php if (!empty($errors)): ?>

        <div class="form-errors">

            <?php foreach ($errors as $error): ?>

                <p><?php echo htmlspecialchars($error); ?></p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <form class="inquiry-form" method="POST" action="submit-inquiry.php">

        <!-- CSRF SECURITY TOKEN -->
        <?php echo csrf_field(); ?>


        <div class="form-row">

            <div class="form-group">

                <label for="organization_name">
                    ORGANIZATION / ARTIST NAME
                </label>

                <input
                    type="text"
                    id="organization_name"
                    name="organization_name"
                    value="<?php echo htmlspecialchars($organization_name); ?>"
                    placeholder="e.g. Juan Dela Cruz / ABC Art Collective"
                    required
                >

            </div>


            <div class="form-group">

                <label for="phone">
                    PHONE NUMBER
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="<?php echo htmlspecialchars($phone); ?>"
                    placeholder="Your phone number"
                    required
                >

            </div>

        </div>


        <div class="form-group">

            <label for="exhibition_title">
                EXHIBITION TITLE
            </label>

            <input
                type="text"
                id="exhibition_title"
                name="exhibition_title"
                value="<?php echo htmlspecialchars($exhibition_title); ?>"
                placeholder="Title of your proposed exhibition"
                required
            >

        </div>


        <div class="form-row">

            <div class="form-group">

                <label for="exhibition_type">
                    EXHIBITION TYPE
                </label>

                <select id="exhibition_type" name="exhibition_type" required>

                    <option value="">Select exhibition type</option>

                    <?php
                    $types = [
                        "Solo Exhibition",
                        "Group Exhibition",
                        "Community Exhibition",
                        "Corporate / Brand Exhibition",
                        "Other"
                    ];

                    foreach ($types as $type):
                        $selected = ($exhibition_type === $type) ? "selected" : "";
                    ?>

                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label for="expected_visitors">
                    EXPECTED VISITORS
                </label>

                <input
                    type="number"
                    id="expected_visitors"
                    name="expected_visitors"
                    min="1"
                    value="<?php echo htmlspecialchars($expected_visitors); ?>"
                    placeholder="e.g. 100"
                    required
                >

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label for="proposed_start_date">
                    PROPOSED START DATE
                </label>

                <input
                    type="date"
                    id="proposed_start_date"
                    name="proposed_start_date"
                    min="<?php echo date('Y-m-d'); ?>"
                    value="<?php echo htmlspecialchars($proposed_start_date); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="proposed_end_date">
                    PROPOSED END DATE
                </label>

                <input
                    type="date"
                    id="proposed_end_date"
                    name="proposed_end_date"
                    min="<?php echo date('Y-m-d'); ?>"
                    value="<?php echo htmlspecialchars($proposed_end_date); ?>"
                    required
                >

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label for="artist_count">
                    NUMBER OF ARTISTS
                </label>

                <input
                    type="number"
                    id="artist_count"
                    name="artist_count"
                    min="1"
                    value="<?php echo htmlspecialchars($artist_count); ?>"
                    placeholder="e.g. 5"
                    required
                >

            </div>


            <div class="form-group">

                <label for="artwork_count">
                    EXPECTED NUMBER OF ARTWORKS
                </label>

                <input
                    type="number"
                    id="artwork_count"
                    name="artwork_count"
                    min="1"
                    value="<?php echo htmlspecialchars($artwork_count); ?>"
                    placeholder="e.g. 20"
                    required
                >

            </div>

        </div>


        <div class="form-group">

            <label for="description">
                EXHIBITION DESCRIPTION
            </label>

            <textarea
                id="description"
                name="description"
                rows="6"
                placeholder="Describe your exhibition concept..."
                required
            ><?php echo htmlspecialchars($description); ?></textarea>

        </div>


        <div class="form-group">

            <label for="special_requirements">
                SPECIAL REQUIREMENTS (OPTIONAL)
            </label>

            <textarea
                id="special_requirements"
                name="special_requirements"
                rows="4"
                placeholder="e.g. specific lighting, extra tables, sound equipment..."
            ><?php echo htmlspecialchars($special_requirements); ?></textarea>

        </div>


        <div class="form-group">

            <label for="additional_notes">
                ADDITIONAL NOTES (OPTIONAL)
            </label>

            <textarea
                id="additional_notes"
                name="additional_notes"
                rows="4"
                placeholder="Anything else you'd like us to know..."
            ><?php echo htmlspecialchars($additional_notes); ?></textarea>

        </div>


        <button type="submit" class="submit-button">
            SUBMIT INQUIRY
        </button>

    </form>

</section>


<?php require_once "includes/footer.php"; ?>

</body>
</html>

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

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // CSRF protection
    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token. Please try again.";
    }

    // Get form values
    $exhibition_title = trim($_POST["exhibition_title"] ?? "");
    $exhibition_description = trim($_POST["exhibition_description"] ?? "");
    $preferred_start_date = $_POST["preferred_start_date"] ?? "";
    $preferred_end_date = $_POST["preferred_end_date"] ?? "";

    // Validate exhibition title
    if (empty($exhibition_title)) {
        $errors[] = "Exhibition title is required.";
    } elseif (strlen($exhibition_title) > 150) {
        $errors[] = "Exhibition title is too long.";
    }

    // Validate description
    if (empty($exhibition_description)) {
        $errors[] = "Exhibition description is required.";
    }

    // Validate start date
    if (empty($preferred_start_date)) {
        $errors[] = "Preferred start date is required.";
    }

    // Validate end date
    if (empty($preferred_end_date)) {
        $errors[] = "Preferred end date is required.";
    }

    // Check date order
    if (
        !empty($preferred_start_date) &&
        !empty($preferred_end_date) &&
        $preferred_end_date < $preferred_start_date
    ) {
        $errors[] = "End date cannot be earlier than the start date.";
    }

    // Save application if there are no errors
    if (empty($errors)) {

        $sql = "INSERT INTO rental_applications
                (
                    user_id,
                    exhibition_title,
                    exhibition_description,
                    preferred_start_date,
                    preferred_end_date,
                    status
                )
                VALUES
                (
                    :user_id,
                    :exhibition_title,
                    :exhibition_description,
                    :preferred_start_date,
                    :preferred_end_date,
                    'pending'
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(
            ":user_id",
            $_SESSION["user_id"],
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ":exhibition_title",
            $exhibition_title
        );

        $stmt->bindValue(
            ":exhibition_description",
            $exhibition_description
        );

        $stmt->bindValue(
            ":preferred_start_date",
            $preferred_start_date
        );

        $stmt->bindValue(
            ":preferred_end_date",
            $preferred_end_date
        );

        $stmt->execute();

        $success = "Your rental application has been submitted successfully.";

        // Clear form fields after successful submission
        $exhibition_title = "";
        $exhibition_description = "";
        $preferred_start_date = "";
        $preferred_end_date = "";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rent Our Space | EDL Gallery</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<?php require_once "includes/header.php"; ?>


<main class="rent-space-page">

    <section class="rent-space-header">

        <p>EDL GALLERY / RENT OUR SPACE</p>

        <h1>Rent Our <span>Space</span></h1>

        <p>
            Submit your exhibition proposal and let EDL Gallery
            help bring your creative vision to life.
        </p>

    </section>


    <section class="rental-application-section">

        <div class="rental-application-container">

            <div class="rental-application-intro">

                <p>EXHIBITION RENTAL</p>

                <h2>Start Your<br>Application</h2>

                <p>
                    Tell us about the exhibition you would like
                    to host at EDL Gallery. Your application will
                    be reviewed by our gallery administrator.
                </p>

            </div>


            <div class="rental-form-container">

                <?php if (!empty($errors)): ?>

                    <div class="rental-errors">

                        <?php foreach ($errors as $error): ?>

                            <p>
                                <?php echo htmlspecialchars($error); ?>
                            </p>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <?php if (!empty($success)): ?>

                    <div class="rental-success">

                        <p>
                            <?php echo htmlspecialchars($success); ?>
                        </p>

                        <a href="dashboard.php">
                            Return to Dashboard →
                        </a>

                    </div>

                <?php endif; ?>


                <?php if (empty($success)): ?>

                    <form
                        method="POST"
                        action="rent-space.php"
                        class="rental-form"
                    >

                        <?php echo csrf_field(); ?>


                        <!-- EXHIBITION TITLE -->

                        <div class="rental-form-group">

                            <label for="exhibition_title">
                                Exhibition Title
                            </label>

                            <input
                                type="text"
                                id="exhibition_title"
                                name="exhibition_title"
                                maxlength="150"
                                value="<?php echo htmlspecialchars($exhibition_title ?? ""); ?>"
                                required
                            >

                        </div>


                        <!-- DESCRIPTION -->

                        <div class="rental-form-group">

                            <label for="exhibition_description">
                                Exhibition Description
                            </label>

                            <textarea
                                id="exhibition_description"
                                name="exhibition_description"
                                rows="7"
                                required
                            ><?php echo htmlspecialchars($exhibition_description ?? ""); ?></textarea>

                        </div>


                        <!-- DATES -->

                        <div class="rental-date-row">

                            <div class="rental-form-group">

                                <label for="preferred_start_date">
                                    Preferred Start Date
                                </label>

                                <input
                                    type="date"
                                    id="preferred_start_date"
                                    name="preferred_start_date"
                                    value="<?php echo htmlspecialchars($preferred_start_date ?? ""); ?>"
                                    required
                                >

                            </div>


                            <div class="rental-form-group">

                                <label for="preferred_end_date">
                                    Preferred End Date
                                </label>

                                <input
                                    type="date"
                                    id="preferred_end_date"
                                    name="preferred_end_date"
                                    value="<?php echo htmlspecialchars($preferred_end_date ?? ""); ?>"
                                    required
                                >

                            </div>

                        </div>


                        <!-- SUBMIT -->

                        <button
                            type="submit"
                            class="rental-submit-button"
                        >
                            Submit Application →
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>


<?php require_once "includes/footer.php"; ?>

</body>

</html>
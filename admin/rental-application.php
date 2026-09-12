<?php

session_start();
$basePath = "../";

define('EDL_ADMIN', true);

require_once "../database/config.php";
require_once "../security/shield.php";
require_once "../security/authorize.php";
require_once "admin-includes/helpers.php";


// --------------------------------------------------
// ADMIN ACCESS PROTECTION
// --------------------------------------------------

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$errors  = [];
$success = "";


// --------------------------------------------------
// UPDATE APPLICATION STATUS
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {

        $errors[] = "Invalid security token. Please try again.";

    } else {

        $applicationId = $_POST["application_id"] ?? "";
        $newStatus     = $_POST["status"] ?? "";
        $adminMessage  = trim($_POST["admin_message"] ?? "");

        $allowedStatuses = [
            "pending",
            "approved",
            "rejected",
            "cancelled"
        ];

        if (!ctype_digit((string)$applicationId)) {
            $errors[] = "Invalid rental application.";
        }

        if (!in_array($newStatus, $allowedStatuses, true)) {
            $errors[] = "Invalid application status.";
        }

        if (empty($errors)) {

            // Check the application exists and grab the fields we need
            $checkStmt = $pdo->prepare("
                SELECT id, user_id, exhibition_title
                FROM rental_applications
                WHERE id = :id
            ");
            $checkStmt->bindValue(":id", (int)$applicationId, PDO::PARAM_INT);
            $checkStmt->execute();

            $application = $checkStmt->fetch();

            if (!$application) {

                $errors[] = "Rental application not found.";

            } else {

                try {

                    $pdo->beginTransaction();

                    // 1. Update application
                    $updateStmt = $pdo->prepare("
                        UPDATE rental_applications
                        SET
                            status = :status,
                            admin_message = :admin_message
                        WHERE id = :id
                    ");

                    $updateStmt->bindValue(":status", $newStatus);

                    $updateStmt->bindValue(
                        ":admin_message",
                        $adminMessage !== "" ? $adminMessage : null,
                        $adminMessage !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL
                    );

                    $updateStmt->bindValue(":id", (int)$applicationId, PDO::PARAM_INT);
                    $updateStmt->execute();


                    // 2. Notify the user (savepoint so a notification
                    //    failure doesn't roll back the status change)
                    $pdo->exec("SAVEPOINT before_notify");

                    try {

                        $statusText = ucwords(str_replace("_", " ", $newStatus));

                        $notificationTitle   = "Rental Application Updated";
                        $notificationMessage =
                            "Your rental application for \"" .
                            $application["exhibition_title"] .
                            "\" has been updated to " .
                            $statusText .
                            ".";

                        if ($adminMessage !== "") {
                            $notificationMessage .= " Admin message: " . $adminMessage;
                        }

                        $notificationStmt = $pdo->prepare("
                            INSERT INTO notifications
                            (user_id, title, message, is_read, created_at)
                            VALUES
                            (:user_id, :title, :message, 0, NOW())
                        ");

                        $notificationStmt->bindValue(":user_id", $application["user_id"], PDO::PARAM_INT);
                        $notificationStmt->bindValue(":title", $notificationTitle);
                        $notificationStmt->bindValue(":message", $notificationMessage);
                        $notificationStmt->execute();

                    } catch (PDOException $e) {

                        // Roll back just the notification, keep the status change
                        $pdo->exec("ROLLBACK TO SAVEPOINT before_notify");

                    }

                    $pdo->commit();

                    $success = "Rental application updated successfully.";

                } catch (Exception $e) {

                    $pdo->rollBack();

                    $errors[] = "Something went wrong while saving your changes. Please try again.";

                }

            }

        }

    }

}


// --------------------------------------------------
// FILTER
// --------------------------------------------------

$statusFilter = $_GET["status"] ?? "";

$allowedFilters = [
    "pending",
    "approved",
    "rejected",
    "cancelled"
];

if (!in_array($statusFilter, $allowedFilters, true)) {
    $statusFilter = "";
}


// --------------------------------------------------
// GET RENTAL APPLICATIONS
// --------------------------------------------------

if ($statusFilter !== "") {

    $stmt = $pdo->prepare("
        SELECT
            rental_applications.*,
            users.first_name,
            users.last_name,
            users.email
        FROM rental_applications
        INNER JOIN users
            ON rental_applications.user_id = users.id
        WHERE rental_applications.status = :status
        ORDER BY rental_applications.created_at DESC
    ");

    $stmt->bindValue(":status", $statusFilter);
    $stmt->execute();

} else {

    $stmt = $pdo->query("
        SELECT
            rental_applications.*,
            users.first_name,
            users.last_name,
            users.email
        FROM rental_applications
        INNER JOIN users
            ON rental_applications.user_id = users.id
        ORDER BY rental_applications.created_at DESC
    ");

}

$applications = $stmt->fetchAll();

$pageTitle   = "Rental Applications";
$activePage  = "rental-applications";
$extraStyles = ["rental-application.css"];
require_once "admin-head.php";
?>

<section class="admin-page">

    <div class="admin-header">

        <div>

            <p class="admin-label">ADMIN</p>

            <h1>Rental Applications</h1>

            <p class="admin-subtext">
                Review and manage exhibition space rental applications
                submitted by gallery users.
            </p>

        </div>

    </div>


    <!-- SUCCESS MESSAGE -->

    <?php if (!empty($success)): ?>

        <div class="inquiry-success-note">
            <p><?php echo htmlspecialchars($success); ?></p>
        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGES -->

    <?php if (!empty($errors)): ?>

        <div class="form-errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>


    <!-- FILTERS -->

    <div class="admin-tabs">

        <a
            href="rental-application.php"
            class="admin-tab <?php echo ($statusFilter === "") ? "active" : ""; ?>"
        >
            ALL
        </a>

        <a
            href="rental-application.php?status=pending"
            class="admin-tab <?php echo ($statusFilter === "pending") ? "active" : ""; ?>"
        >
            PENDING
        </a>

        <a
            href="rental-application.php?status=approved"
            class="admin-tab <?php echo ($statusFilter === "approved") ? "active" : ""; ?>"
        >
            APPROVED
        </a>

        <a
            href="rental-application.php?status=rejected"
            class="admin-tab <?php echo ($statusFilter === "rejected") ? "active" : ""; ?>"
        >
            REJECTED
        </a>

        <a
            href="rental-application.php?status=cancelled"
            class="admin-tab <?php echo ($statusFilter === "cancelled") ? "active" : ""; ?>"
        >
            CANCELLED
        </a>

    </div>


    <!-- APPLICATIONS -->

    <?php if (empty($applications)): ?>

        <div class="empty-dashboard">
            <h3>No Rental Applications</h3>
            <p>There are currently no rental applications matching this filter.</p>
        </div>

    <?php else: ?>

        <?php foreach ($applications as $application): ?>

            <article class="rental-application-card">

                <!-- TITLE + STATUS -->

                <div class="rental-application-top">

                    <div class="rental-application-title">

                        <h2>
                            <?php echo htmlspecialchars($application["exhibition_title"]); ?>
                        </h2>

                        <p>
                            Application submitted on
                            <?php
                            echo date(
                                "M j, Y",
                                strtotime($application["created_at"])
                            );
                            ?>
                        </p>

                    </div>

                    <span class="rental-status <?php echo htmlspecialchars($application["status"]); ?>">
                        <?php
                        echo htmlspecialchars(
                            strtoupper(str_replace("_", " ", $application["status"]))
                        );
                        ?>
                    </span>

                </div>


                <!-- APPLICANT INFORMATION -->

                <div class="rental-application-info">

                    <div class="rental-info-item">
                        <strong>Applicant</strong>
                        <span>
                            <?php
                            echo htmlspecialchars(
                                $application["first_name"] . " " . $application["last_name"]
                            );
                            ?>
                        </span>
                    </div>

                    <div class="rental-info-item">
                        <strong>Email</strong>
                        <span>
                            <?php echo htmlspecialchars($application["email"]); ?>
                        </span>
                    </div>

                    <div class="rental-info-item">
                        <strong>Requested Dates</strong>
                        <span>
                            <?php
                            echo date("M j, Y", strtotime($application["preferred_start_date"]));
                            ?>
                            –
                            <?php
                            echo date("M j, Y", strtotime($application["preferred_end_date"]));
                            ?>
                        </span>
                    </div>

                </div>


                <!-- DESCRIPTION -->

                <div class="rental-description">

                    <h3>Exhibition Description</h3>

                    <p><?php echo htmlspecialchars($application["exhibition_description"]); ?></p>

                </div>


                <!-- EXISTING ADMIN MESSAGE -->

                <?php if (!empty($application["admin_message"])): ?>

                    <div class="rental-admin-message">

                        <h3>Previous Admin Message</h3>

                        <p><?php echo htmlspecialchars($application["admin_message"]); ?></p>

                    </div>

                <?php endif; ?>


                <!-- UPDATE FORM -->

                <form
                    method="POST"
                    action=""
                    class="rental-update-form"
                >

                    <?php echo csrf_field(); ?>

                    <input
                        type="hidden"
                        name="application_id"
                        value="<?php echo (int)$application["id"]; ?>"
                    >

                    <label for="status-<?php echo (int)$application["id"]; ?>">
                        Application Status
                    </label>

                    <select
                        id="status-<?php echo (int)$application["id"]; ?>"
                        name="status"
                        required
                    >
                        <option
                            value="pending"
                            <?php echo $application["status"] === "pending" ? "selected" : ""; ?>
                        >
                            Pending
                        </option>

                        <option
                            value="approved"
                            <?php echo $application["status"] === "approved" ? "selected" : ""; ?>
                        >
                            Approved
                        </option>

                        <option
                            value="rejected"
                            <?php echo $application["status"] === "rejected" ? "selected" : ""; ?>
                        >
                            Rejected
                        </option>

                        <option
                            value="cancelled"
                            <?php echo $application["status"] === "cancelled" ? "selected" : ""; ?>
                        >
                            Cancelled
                        </option>

                    </select>


                    <label for="message-<?php echo (int)$application["id"]; ?>">
                        Message to Applicant
                    </label>

                    <textarea
                        id="message-<?php echo (int)$application["id"]; ?>"
                        name="admin_message"
                        placeholder="Enter a message for the applicant..."
                    ><?php echo htmlspecialchars($application["admin_message"] ?? ""); ?></textarea>


                    <button type="submit" class="rental-update-button">
                        Update Application
                    </button>

                </form>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</section>

<?php require_once "admin-foot.php"; ?>
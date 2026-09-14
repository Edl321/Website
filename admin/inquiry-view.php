<?php

session_start();
$basePath = "../";

define('EDL_ADMIN', true);

require_once "../database/config.php";
require_once "../security/authorize.php";
require_once "../security/shield.php";
require_once "admin-includes/helpers.php";


if (!isLoggedIn() || !isAdmin()) {

    header("Location: ../login.php");
    exit;

}

$adminId = $_SESSION["user_id"];

$inquiryId = $_GET["id"] ?? "";

if (!ctype_digit((string)$inquiryId)) {

    header("Location: inquiries.php");
    exit;

}

$inquiryId = (int)$inquiryId;


$feedback = "";
$errors   = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {

        $errors[] = "Invalid security token. Please try again.";

    } else {

        $action    = $_POST["action"] ?? "";
        $adminNote = trim($_POST["admin_note"] ?? "");

        $sql = "SELECT * FROM exhibition_inquiries WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":id", $inquiryId, PDO::PARAM_INT);
        $stmt->execute();
        $current = $stmt->fetch();

        if (!$current) {

            $errors[] = "This inquiry no longer exists.";

        } elseif ($current["status"] !== "pending") {

            $errors[] = "This inquiry has already been reviewed.";

        } elseif ((int)$current["user_id"] === (int)$adminId) {

            $errors[] = "You cannot review your own inquiry.";

        } elseif ($action === "reject" && $adminNote === "") {

            $errors[] = "Please provide a reason before rejecting an inquiry.";

        } elseif (in_array($action, ["approve", "reject"], true)) {

            try {

                $pdo->beginTransaction();

                $newStatus = ($action === "approve") ? "approved" : "rejected";

                // 1. Update the inquiry itself
                $updateSql = "UPDATE exhibition_inquiries
                            SET status = :status,
                                admin_note = :admin_note,
                                reviewed_at = NOW()
                            WHERE id = :id";

                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->bindValue(":status", $newStatus);
                $updateStmt->bindValue(":admin_note", $adminNote !== "" ? $adminNote : null);
                $updateStmt->bindValue(":id", $inquiryId, PDO::PARAM_INT);
                $updateStmt->execute();


                // 2. If approved, create the exhibition record for the organizer
                if ($action === "approve") {

                    // Guard against empty / null values that would fail the NOT NULL columns
                    $exhibitionDescription = trim((string)($current["description"] ?? ""));
                    if ($exhibitionDescription === "") {
                        $exhibitionDescription = "No description provided.";
                    }

                    $exhibitionSql = "INSERT INTO exhibitions
                                    (inquiry_id, organizer_id, title, description,
                                        start_date, end_date, status, created_at)
                                    VALUES
                                    (:inquiry_id, :organizer_id, :title, :description,
                                        :start_date, :end_date, 'draft', NOW())";

                    $exhibitionStmt = $pdo->prepare($exhibitionSql);
                    $exhibitionStmt->bindValue(":inquiry_id", $inquiryId, PDO::PARAM_INT);
                    $exhibitionStmt->bindValue(":organizer_id", $current["user_id"], PDO::PARAM_INT);
                    $exhibitionStmt->bindValue(":title", $current["exhibition_title"]);
                    $exhibitionStmt->bindValue(":description", $exhibitionDescription);
                    $exhibitionStmt->bindValue(":start_date", $current["proposed_start_date"]);
                    $exhibitionStmt->bindValue(":end_date", $current["proposed_end_date"]);
                    $exhibitionStmt->execute();

                }

                $notifTitle = ($action === "approve")
                    ? "Your exhibition inquiry was approved"
                    : "Your exhibition inquiry was not approved";

                $notifMessage = ($action === "approve")
                    ? "Your inquiry \"" . $current["exhibition_title"] . "\" has been approved. You may now complete your exhibition details."
                    : "Your inquiry \"" . $current["exhibition_title"] . "\" was not approved."
                    . ($adminNote !== "" ? " Reason: " . $adminNote : "");

                $notifSql = "INSERT INTO notifications
                            (user_id, title, message, is_read, created_at)
                            VALUES
                            (:user_id, :title, :message, 0, NOW())";

                $notifStmt = $pdo->prepare($notifSql);
                $notifStmt->bindValue(":user_id", $current["user_id"], PDO::PARAM_INT);
                $notifStmt->bindValue(":title", $notifTitle);
                $notifStmt->bindValue(":message", $notifMessage);
                $notifStmt->execute();


                $pdo->commit();

                $feedback = ($action === "approve")
                    ? "Inquiry approved. An exhibition has been created for the organizer."
                    : "Inquiry rejected.";

            } catch (Exception $e) {

                $pdo->rollBack();

                $errors[] = "DEBUG: " . $e->getMessage();

            }

        } else {

            $errors[] = "Unknown action.";

        }

    }

}

$sql = "SELECT ei.*, u.first_name, u.last_name, u.email
        FROM exhibition_inquiries ei
        JOIN users u ON u.id = ei.user_id
        WHERE ei.id = :id";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $inquiryId, PDO::PARAM_INT);
$stmt->execute();

$inquiry = $stmt->fetch();

if (!$inquiry) {
    header("Location: inquiries.php");
    exit;
}

$isOwnInquiry = ((int)$inquiry["user_id"] === (int)$adminId);

$pageTitle  = "Inquiry Details";
$activePage = "inquiries";
require_once "admin-head.php";
?>

<section class="admin-page">

    <a href="inquiries.php" class="admin-back-link">
        &larr; Back to Inquiries
    </a>

    <div class="admin-header">

        <p class="admin-label">ADMIN</p>

        <h1><?php echo htmlspecialchars($inquiry["exhibition_title"]); ?></h1>

        <span class="status-label">STATUS</span>

        <?php if ($inquiry["status"] === "pending"): ?>
            <strong class="status-pending">PENDING</strong>
        <?php elseif ($inquiry["status"] === "approved"): ?>
            <strong class="status-approved">APPROVED</strong>
        <?php elseif ($inquiry["status"] === "rejected"): ?>
            <strong class="status-rejected">REJECTED</strong>
        <?php endif; ?>

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

    <div class="account-information">

        <div class="account-row">
            <span>REQUESTED BY</span>

            <strong>
                <?php echo htmlspecialchars($inquiry["first_name"] . " " . $inquiry["last_name"]);?>
                (<?php echo htmlspecialchars($inquiry["email"]); ?>)
            </strong>

        </div>

        <div class="account-row">
            <span>ORGANIZATION / ARTIST</span>
            <strong><?php echo htmlspecialchars($inquiry["organization_name"]); ?></strong>
        </div>

        <div class="account-row">
            <span>PHONE</span>
            <strong><?php echo htmlspecialchars($inquiry["phone"]); ?></strong>
        </div>

        <div class="account-row">
            <span>EXHIBITION TYPE</span>
            <strong><?php echo htmlspecialchars($inquiry["exhibition_type"]); ?></strong>
        </div>

        <div class="account-row">
            <span>PROPOSED DATES</span>
            <strong>
                <?php
                echo date("F j, Y", strtotime($inquiry["proposed_start_date"]));
                echo " &ndash; ";
                echo date("F j, Y", strtotime($inquiry["proposed_end_date"]));
                ?>
            </strong>
        </div>

        <div class="account-row">
            <span>NUMBER OF ARTISTS</span>
            <strong><?php echo (int)$inquiry["artist_count"]; ?></strong>
        </div>

        <div class="account-row">
            <span>EXPECTED ARTWORKS</span>
            <strong><?php echo (int)$inquiry["artwork_count"]; ?></strong>
        </div>

        <div class="account-row">
            <span>EXPECTED VISITORS</span>
            <strong><?php echo (int)$inquiry["expected_visitors"]; ?></strong>
        </div>

        <div class="account-row">
            <span>SUBMITTED ON</span>
            <strong><?php echo date("F j, Y g:i A", strtotime($inquiry["created_at"])); ?></strong>
        </div>

        <?php if (!empty($inquiry["reviewed_at"])): ?>

            <div class="account-row">
                <span>REVIEWED ON</span>
                <strong><?php echo date("F j, Y g:i A", strtotime($inquiry["reviewed_at"])); ?></strong>
            </div>

        <?php endif; ?>

    </div>

    <div class="admin-text-block">
        <h4>DESCRIPTION</h4>
        <p><?php echo nl2br(htmlspecialchars($inquiry["description"])); ?></p>
    </div>

    <?php if (!empty($inquiry["special_requirements"])): ?>

        <div class="admin-text-block">
            <h4>SPECIAL REQUIREMENTS</h4>
            <p><?php echo nl2br(htmlspecialchars($inquiry["special_requirements"])); ?></p>
        </div>

    <?php endif; ?>

    <?php if (!empty($inquiry["additional_notes"])): ?>

        <div class="admin-text-block">
            <h4>ADDITIONAL NOTES</h4>
            <p><?php echo nl2br(htmlspecialchars($inquiry["additional_notes"])); ?></p>
        </div>

    <?php endif; ?>

    <?php if (!empty($inquiry["admin_note"])): ?>

        <div class="admin-text-block">
            <h4>ADMIN NOTE</h4>
            <p><?php echo nl2br(htmlspecialchars($inquiry["admin_note"])); ?></p>
        </div>

    <?php endif; ?>

    <?php if ($inquiry["status"] === "pending" && !$isOwnInquiry): ?>

        <div class="admin-actions">

            <div class="admin-action-box">

                <h4>APPROVE THIS INQUIRY</h4>

                <p>
                    Approving will create a draft exhibition for the
                    organizer so they can complete the details.
                </p>

                <form method="POST" action="inquiry-view.php?id=<?php echo $inquiryId; ?>">

                    <?php echo csrf_field(); ?>

                    <input type="hidden" name="action" value="approve">

                    <div class="form-group">
                        <label for="approve_note">NOTE (OPTIONAL)</label>
                        <textarea
                            id="approve_note"
                            name="admin_note"
                            rows="3"
                            placeholder="Optional note for the organizer..."
                        ></textarea>
                    </div>

                    <button type="submit" class="approve-button">
                        APPROVE INQUIRY
                    </button>

                </form>

            </div>


            <div class="admin-action-box">

                <h4>REJECT THIS INQUIRY</h4>

                <p>
                    Please explain why this inquiry is being rejected.
                    The organizer will see this note.
                </p>

                <form method="POST" action="inquiry-view.php?id=<?php echo $inquiryId; ?>">

                    <?php echo csrf_field(); ?>

                    <input type="hidden" name="action" value="reject">

                    <div class="form-group">
                        <label for="reject_note">REASON FOR REJECTION</label>
                        <textarea
                            id="reject_note"
                            name="admin_note"
                            rows="3"
                            placeholder="Explain why this inquiry is being rejected..."
                            required
                        ></textarea>
                    </div>

                    <button type="submit" class="reject-button">
                        REJECT INQUIRY
                    </button>

                </form>

            </div>

        </div>

    <?php elseif ($inquiry["status"] === "pending" && $isOwnInquiry): ?>

        <div class="form-errors">
            <p>This is your own inquiry, so you cannot approve or reject it.</p>
        </div>

    <?php endif; ?>

</section>

<?php require_once "admin-foot.php"; ?>
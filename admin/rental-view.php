<?php

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

$rentalId = $_GET["id"] ?? "";
if (!ctype_digit((string)$rentalId)) {
    header("Location: rentals.php");
    exit;
}
$rentalId = (int)$rentalId;

$errors   = [];
$feedback = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token.";
    } else {

        $action = $_POST["action"] ?? "";

        // ---------------- APPROVE / REJECT ----------------
        if ($action === "approve" || $action === "reject") {

            $message = trim($_POST["admin_message"] ?? "");

            if ($action === "reject" && $message === "") {
                $errors[] = "Please provide a reason for rejection.";
            } else {

                $newStatus = $action === "approve" ? "approved" : "rejected";

                $pdo->beginTransaction();

                try {
                    $stmt = $pdo->prepare("
                        UPDATE rental_applications
                        SET status = :status, admin_message = :message, reviewed_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->bindValue(":status", $newStatus);
                    $stmt->bindValue(":message", $message !== "" ? $message : null);
                    $stmt->bindValue(":id", $rentalId, PDO::PARAM_INT);
                    $stmt->execute();

                    // If approved AND no payment row yet, we wait for admin to add fee separately
                    // If approved and admin provided a fee, create the payment row
                    if ($action === "approve") {

                        $fee = trim($_POST["amount"] ?? "");
                        $dueDate = trim($_POST["due_date"] ?? "");

                        if ($fee !== "" && is_numeric($fee) && (float)$fee >= 0) {

                            // Check if payment already exists
                            $check = $pdo->prepare("SELECT id FROM rental_payments WHERE rental_application_id = :id");
                            $check->bindValue(":id", $rentalId, PDO::PARAM_INT);
                            $check->execute();

                            if ($check->fetch()) {
                                $up = $pdo->prepare("
                                    UPDATE rental_payments
                                    SET amount = :amount, due_date = :due
                                    WHERE rental_application_id = :id
                                ");
                                $up->bindValue(":amount", (float)$fee);
                                $up->bindValue(":due", $dueDate !== "" ? $dueDate : null);
                                $up->bindValue(":id", $rentalId, PDO::PARAM_INT);
                                $up->execute();
                            } else {
                                $ins = $pdo->prepare("
                                    INSERT INTO rental_payments
                                        (rental_application_id, amount, due_date, status, created_at)
                                    VALUES
                                        (:id, :amount, :due, 'unpaid', NOW())
                                ");
                                $ins->bindValue(":id", $rentalId, PDO::PARAM_INT);
                                $ins->bindValue(":amount", (float)$fee);
                                $ins->bindValue(":due", $dueDate !== "" ? $dueDate : null);
                                $ins->execute();
                            }
                        }
                    }

                    // Fetch user + title for notification
                    $info = $pdo->prepare("
                        SELECT user_id, exhibition_title
                        FROM rental_applications
                        WHERE id = :id
                    ");
                    $info->bindValue(":id", $rentalId, PDO::PARAM_INT);
                    $info->execute();
                    $row = $info->fetch();

                    if ($row) {
                        $title = $action === "approve"
                            ? "Your rental application was approved"
                            : "Your rental application was not approved";

                        $msg = $action === "approve"
                            ? "Your rental for \"" . $row["exhibition_title"] . "\" has been approved. Check your dashboard for payment details."
                            : "Your rental for \"" . $row["exhibition_title"] . "\" was not approved."
                              . ($message !== "" ? " Reason: " . $message : "");

                        $notif = $pdo->prepare("
                            INSERT INTO notifications (user_id, title, message, is_read, created_at)
                            VALUES (:user_id, :title, :message, 0, NOW())
                        ");
                        $notif->bindValue(":user_id", $row["user_id"], PDO::PARAM_INT);
                        $notif->bindValue(":title", $title);
                        $notif->bindValue(":message", $msg);
                        $notif->execute();
                    }

                    $pdo->commit();
                    $feedback = "Rental application " . $newStatus . ".";

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = "Something went wrong: " . $e->getMessage();
                }
            }
        }

        // ---------------- MARK PAYMENT PAID / REFUNDED ----------------
        elseif ($action === "mark_paid" || $action === "mark_refunded") {

            $newPayStatus = $action === "mark_paid" ? "paid" : "refunded";
            $method    = trim($_POST["method"] ?? "");
            $reference = trim($_POST["reference"] ?? "");
            $notes     = trim($_POST["notes"] ?? "");

            $upd = $pdo->prepare("
                UPDATE rental_payments
                SET status = :status,
                    method = :method,
                    reference = :reference,
                    notes = :notes,
                    paid_at = NOW()
                WHERE rental_application_id = :id
            ");
            $upd->bindValue(":status", $newPayStatus);
            $upd->bindValue(":method", $method !== "" ? $method : null);
            $upd->bindValue(":reference", $reference !== "" ? $reference : null);
            $upd->bindValue(":notes", $notes !== "" ? $notes : null);
            $upd->bindValue(":id", $rentalId, PDO::PARAM_INT);
            $upd->execute();

            $feedback = "Payment marked as " . $newPayStatus . ".";
        }

        // ---------------- CANCEL APPLICATION ----------------
        elseif ($action === "cancel") {

            $upd = $pdo->prepare("UPDATE rental_applications SET status = 'cancelled' WHERE id = :id");
            $upd->bindValue(":id", $rentalId, PDO::PARAM_INT);
            $upd->execute();

            $feedback = "Application cancelled.";
        }
    }
}

// Load the application
$stmt = $pdo->prepare("
    SELECT ra.*, u.first_name, u.last_name, u.email,
           rp.amount, rp.due_date, rp.status AS payment_status,
           rp.method, rp.reference, rp.notes AS payment_notes, rp.paid_at
    FROM rental_applications ra
    JOIN users u ON u.id = ra.user_id
    LEFT JOIN rental_payments rp ON rp.rental_application_id = ra.id
    WHERE ra.id = :id
");
$stmt->bindValue(":id", $rentalId, PDO::PARAM_INT);
$stmt->execute();
$app = $stmt->fetch();

if (!$app) {
    header("Location: rentals.php");
    exit;
}

$pageTitle  = "Rental Details";
$activePage = "rentals";
require_once "admin-head.php";
?>

<section class="admin-page">

    <a href="rentals.php" class="admin-back-link">&larr; Back to Rentals</a>

    <div class="admin-header">
        <p class="admin-label">ADMIN</p>
        <h1><?php echo htmlspecialchars($app["exhibition_title"]); ?></h1>
        <span class="status-label">STATUS</span>
        <strong class="status-<?php echo htmlspecialchars($app["status"]); ?>">
            <?php echo strtoupper($app["status"]); ?>
        </strong>
    </div>

    <?php if (!empty($feedback)): ?>
        <div class="inquiry-success-note"><p><?php echo htmlspecialchars($feedback); ?></p></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="form-errors">
            <?php foreach ($errors as $e): ?><p><?php echo htmlspecialchars($e); ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="account-information">

        <div class="account-row">
            <span>REQUESTED BY</span>
            <strong><?php echo htmlspecialchars($app["first_name"] . " " . $app["last_name"]); ?>
                (<?php echo htmlspecialchars($app["email"]); ?>)</strong>
        </div>

        <div class="account-row">
            <span>REQUESTED DATES</span>
            <strong>
                <?php echo date("F j, Y", strtotime($app["preferred_start_date"])); ?>
                &ndash;
                <?php echo date("F j, Y", strtotime($app["preferred_end_date"])); ?>
            </strong>
        </div>

        <div class="account-row">
            <span>SUBMITTED ON</span>
            <strong><?php echo date("F j, Y g:i A", strtotime($app["created_at"])); ?></strong>
        </div>

    </div>

    <div class="admin-text-block">
        <h4>EXHIBITION DESCRIPTION</h4>
        <p><?php echo nl2br(htmlspecialchars($app["exhibition_description"])); ?></p>
    </div>

    <?php if ($app["amount"] !== null): ?>
        <div class="admin-text-block">
            <h4>PAYMENT DETAILS</h4>

            <div class="account-information" style="margin:0;">
                <div class="account-row">
                    <span>AMOUNT</span>
                    <strong>₱<?php echo number_format((float)$app["amount"], 2); ?></strong>
                </div>
                <?php if ($app["due_date"]): ?>
                    <div class="account-row">
                        <span>DUE DATE</span>
                        <strong><?php echo date("F j, Y", strtotime($app["due_date"])); ?></strong>
                    </div>
                <?php endif; ?>
                <div class="account-row">
                    <span>PAYMENT STATUS</span>
                    <strong class="status-<?php echo $app["payment_status"] === "paid" ? "approved" : ($app["payment_status"] === "overdue" ? "rejected" : "pending"); ?>">
                        <?php echo strtoupper($app["payment_status"]); ?>
                    </strong>
                </div>
                <?php if ($app["paid_at"]): ?>
                    <div class="account-row">
                        <span>PAID ON</span>
                        <strong><?php echo date("F j, Y", strtotime($app["paid_at"])); ?></strong>
                    </div>
                <?php endif; ?>
                <?php if ($app["method"]): ?>
                    <div class="account-row">
                        <span>METHOD</span>
                        <strong><?php echo htmlspecialchars($app["method"]); ?></strong>
                    </div>
                <?php endif; ?>
                <?php if ($app["reference"]): ?>
                    <div class="account-row">
                        <span>REFERENCE</span>
                        <strong><?php echo htmlspecialchars($app["reference"]); ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($app["payment_status"] === "unpaid" || $app["payment_status"] === "overdue"): ?>
                <div class="admin-action-box" style="margin-top:25px;">
                    <h4>RECORD PAYMENT</h4>
                    <p>Use this when the applicant has paid outside the system (cash, bank, GCash).</p>
                    <form method="POST" action="rental-view.php?id=<?php echo $rentalId; ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="mark_paid">

                        <div class="form-group">
                            <label>METHOD</label>
                            <input type="text" name="method" placeholder="Cash / Bank Transfer / GCash" required>
                        </div>

                        <div class="form-group">
                            <label>REFERENCE (OPTIONAL)</label>
                            <input type="text" name="reference" placeholder="Reference or receipt number">
                        </div>

                        <div class="form-group">
                            <label>NOTES (OPTIONAL)</label>
                            <textarea name="notes" rows="2"></textarea>
                        </div>

                        <button type="submit" class="approve-button">MARK AS PAID</button>
                    </form>
                </div>
            <?php elseif ($app["payment_status"] === "paid"): ?>
                <form method="POST" action="rental-view.php?id=<?php echo $rentalId; ?>" style="margin-top:20px;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="mark_refunded">
                    <button type="submit" class="reject-button"
                            onclick="return confirm('Mark this payment as refunded?');">
                        MARK AS REFUNDED
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- APPROVE / REJECT (only if pending) -->
    <?php if ($app["status"] === "pending"): ?>

        <div class="admin-actions">

            <div class="admin-action-box">
                <h4>APPROVE THIS RENTAL</h4>
                <p>Set the rental fee and due date so the applicant can pay.</p>

                <form method="POST" action="rental-view.php?id=<?php echo $rentalId; ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="approve">

                    <div class="form-group">
                        <label>RENTAL FEE (₱)</label>
                        <input type="text" name="amount" placeholder="e.g. 5000.00" required>
                    </div>

                    <div class="form-group">
                        <label>DUE DATE</label>
                        <input type="date" name="due_date" min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label>NOTE (OPTIONAL)</label>
                        <textarea name="admin_message" rows="3"></textarea>
                    </div>

                    <button type="submit" class="approve-button">APPROVE RENTAL</button>
                </form>
            </div>

            <div class="admin-action-box">
                <h4>REJECT THIS RENTAL</h4>
                <p>Explain why the rental is being rejected. The applicant will see this note.</p>

                <form method="POST" action="rental-view.php?id=<?php echo $rentalId; ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="reject">

                    <div class="form-group">
                        <label>REASON FOR REJECTION</label>
                        <textarea name="admin_message" rows="3" required></textarea>
                    </div>

                    <button type="submit" class="reject-button">REJECT RENTAL</button>
                </form>
            </div>

        </div>

    <?php elseif ($app["status"] !== "cancelled"): ?>

        <div class="admin-action-box">
            <h4>CANCEL APPLICATION</h4>
            <p>Cancel this rental application. This cannot be undone.</p>
            <form method="POST" action="rental-view.php?id=<?php echo $rentalId; ?>"
                onsubmit="return confirm('Cancel this rental application?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="reject-button">CANCEL APPLICATION</button>
            </form>
        </div>

    <?php endif; ?>

</section>

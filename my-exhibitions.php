<?php

$basePath = "";

require_once "database/config.php";
require_once "security/shield.php";
require_once "security/authorize.php";


if (!isUser()) {
    header("Location: login.php");
    exit;
}


$userId = $_SESSION["user_id"];

$successMessage = "";
$errorMessage   = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {
        die("Invalid CSRF token.");
    }

    $action = $_POST["action"] ?? "";
    $exhibitionId = filter_input(
        INPUT_POST,
        "exhibition_id",
        FILTER_VALIDATE_INT
    );

    /*
    |--------------------------------------------------------------------------
    | READY TO PUBLISH
    |--------------------------------------------------------------------------
    */

    if ($action === "ready_to_publish" && $exhibitionId) {

        $ownershipSql = "
            SELECT id, status
            FROM exhibitions
            WHERE id = :exhibition_id
            AND organizer_id = :organizer_id
            LIMIT 1
        ";

        $ownershipStmt = $pdo->prepare($ownershipSql);
        $ownershipStmt->bindValue(":exhibition_id", $exhibitionId, PDO::PARAM_INT);
        $ownershipStmt->bindValue(":organizer_id", $userId, PDO::PARAM_INT);
        $ownershipStmt->execute();

        $exhibition = $ownershipStmt->fetch();

        if (!$exhibition) {

            $errorMessage =
                "You are not authorized to manage this exhibition.";

        } elseif ($exhibition["status"] !== "artwork_submission") {

            $errorMessage =
                "This exhibition is not currently in the artwork submission stage.";

        } else {

            $artworkSql = "
                SELECT
                    COUNT(*) AS total_artworks,

                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_artworks,
                    SUM(CASE WHEN status = 'pending'  THEN 1 ELSE 0 END) AS pending_artworks,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_artworks

                FROM artworks

                WHERE exhibition_id = :exhibition_id
            ";

            $artworkStmt = $pdo->prepare($artworkSql);
            $artworkStmt->bindValue(":exhibition_id", $exhibitionId, PDO::PARAM_INT);
            $artworkStmt->execute();

            $artworkStatus = $artworkStmt->fetch();

            $totalArtworks    = (int)($artworkStatus["total_artworks"] ?? 0);
            $approvedArtworks = (int)($artworkStatus["approved_artworks"] ?? 0);
            $pendingArtworks  = (int)($artworkStatus["pending_artworks"] ?? 0);
            $rejectedArtworks = (int)($artworkStatus["rejected_artworks"] ?? 0);

            if ($totalArtworks === 0) {

                $errorMessage =
                    "You must submit at least one artwork before marking the exhibition as ready to publish.";

            } elseif ($pendingArtworks > 0) {

                $errorMessage =
                    "You still have pending artworks waiting for admin approval.";

            } elseif ($rejectedArtworks > 0) {

                $errorMessage =
                    "You have rejected artworks. Please review and replace or remove them before continuing.";

            } elseif ($approvedArtworks !== $totalArtworks) {

                $errorMessage =
                    "Not all artworks have been approved yet.";

            } else {

                $updateSql = "
                    UPDATE exhibitions
                    SET status = 'ready_to_publish'
                    WHERE id = :exhibition_id
                    AND organizer_id = :organizer_id
                    AND status = 'artwork_submission'
                ";

                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->bindValue(":exhibition_id", $exhibitionId, PDO::PARAM_INT);
                $updateStmt->bindValue(":organizer_id", $userId, PDO::PARAM_INT);
                $updateStmt->execute();

                if ($updateStmt->rowCount() > 0) {

                    $successMessage =
                        "Your exhibition is now ready to publish. An administrator can review and publish it.";

                } else {

                    $errorMessage =
                        "The exhibition status could not be updated.";

                }

            }

        }

    }

    /*
    |--------------------------------------------------------------------------
    | CHANGE EXHIBITION TYPE (Solo <-> Group)
    |--------------------------------------------------------------------------
    */

    elseif ($action === "change_exhibition_type" && $exhibitionId) {

        $newType = trim($_POST["new_exhibition_type"] ?? "");

        $allowedTypes = [
            "Solo Exhibition",
            "Group Exhibition",
            "Community Exhibition",
            "Corporate / Brand Exhibition",
            "Other"
        ];

        if (!in_array($newType, $allowedTypes, true)) {

            $errorMessage = "Invalid exhibition type.";

        } else {

            $ownStmt = $pdo->prepare("
                SELECT
                    e.id,
                    e.status,
                    e.inquiry_id
                FROM exhibitions e
                WHERE e.id = :exhibition_id
                  AND e.organizer_id = :organizer_id
                LIMIT 1
            ");

            $ownStmt->bindValue(":exhibition_id", $exhibitionId, PDO::PARAM_INT);
            $ownStmt->bindValue(":organizer_id", $userId, PDO::PARAM_INT);
            $ownStmt->execute();

            $own = $ownStmt->fetch();

            if (!$own) {

                $errorMessage = "You are not authorized to manage this exhibition.";

            } elseif (!in_array($own["status"], ["draft", "artwork_submission"], true)) {

                $errorMessage = "This exhibition can no longer be changed.";

            } elseif (empty($own["inquiry_id"])) {

                $errorMessage = "This exhibition is not linked to an inquiry, so its type cannot be changed here.";

            } else {

                if ($newType === "Solo Exhibition") {

                    $artistCountStmt = $pdo->prepare("
                        SELECT COUNT(*)
                        FROM exhibition_artists
                        WHERE exhibition_id = :exhibition_id
                    ");
                    $artistCountStmt->bindValue(":exhibition_id", $exhibitionId, PDO::PARAM_INT);
                    $artistCountStmt->execute();

                    $attachedArtists = (int)$artistCountStmt->fetchColumn();

                    if ($attachedArtists > 1) {

                        $errorMessage = "You currently have " . $attachedArtists .
                            " artists attached. Remove extras before switching to a Solo Exhibition.";

                    } else {

                        $updateType = $pdo->prepare("
                            UPDATE exhibition_inquiries
                            SET exhibition_type = :exhibition_type
                            WHERE id = :inquiry_id
                        ");

                        $updateType->bindValue(":exhibition_type", $newType);
                        $updateType->bindValue(":inquiry_id", (int)$own["inquiry_id"], PDO::PARAM_INT);
                        $updateType->execute();

                        $successMessage = "Exhibition type updated to " . htmlspecialchars($newType) . ".";

                    }

                } else {

                    $updateType = $pdo->prepare("
                        UPDATE exhibition_inquiries
                        SET exhibition_type = :exhibition_type
                        WHERE id = :inquiry_id
                    ");

                    $updateType->bindValue(":exhibition_type", $newType);
                    $updateType->bindValue(":inquiry_id", (int)$own["inquiry_id"], PDO::PARAM_INT);
                    $updateType->execute();

                    $successMessage = "Exhibition type updated to " . htmlspecialchars($newType) . ".";

                }

            }

        }

    }

    else {

        $errorMessage =
            "Invalid exhibition action.";

    }

}


$allowedFilters = [
    "all",
    "active",
    "draft",
    "artwork_submission",
    "ready_to_publish",
    "published",
    "completed",
    "cancelled",
];

$statusFilter = $_GET["status"] ?? "all";

if (!in_array($statusFilter, $allowedFilters, true)) {
    $statusFilter = "all";
}

$baseSql = "
    SELECT
        e.*,

        ei.exhibition_type AS planned_exhibition_type,

        COUNT(a.id) AS total_artworks,

        SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) AS approved_artworks,
        SUM(CASE WHEN a.status = 'pending'  THEN 1 ELSE 0 END) AS pending_artworks,
        SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_artworks

    FROM exhibitions e

    LEFT JOIN exhibition_inquiries ei
        ON ei.id = e.inquiry_id

    LEFT JOIN artworks a
        ON a.exhibition_id = e.id

    WHERE e.organizer_id = :organizer_id
";

if ($statusFilter === "all") {

    $sql = $baseSql . " GROUP BY e.id ORDER BY e.created_at DESC";

} elseif ($statusFilter === "active") {

    $sql = $baseSql . "
        AND e.status IN ('draft', 'artwork_submission', 'ready_to_publish')
        GROUP BY e.id
        ORDER BY e.created_at DESC
    ";

} else {

    $sql = $baseSql . "
        AND e.status = :status
        GROUP BY e.id
        ORDER BY e.created_at DESC
    ";

}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":organizer_id", $userId, PDO::PARAM_INT);

if ($statusFilter !== "all" && $statusFilter !== "active") {
    $stmt->bindValue(":status", $statusFilter);
}

$stmt->execute();

$exhibitions = $stmt->fetchAll();

$countStmt = $pdo->prepare("
    SELECT status, COUNT(*) AS total
    FROM exhibitions
    WHERE organizer_id = :organizer_id
    GROUP BY status
");

$countStmt->bindValue(":organizer_id", $userId, PDO::PARAM_INT);
$countStmt->execute();

$counts = [
    "draft"              => 0,
    "artwork_submission" => 0,
    "ready_to_publish"   => 0,
    "published"          => 0,
    "completed"          => 0,
    "cancelled"          => 0,
];

foreach ($countStmt->fetchAll() as $row) {
    $counts[$row["status"]] = (int)$row["total"];
}

$totalCount = array_sum($counts);

$activeCount =
    $counts["draft"] +
    $counts["artwork_submission"] +
    $counts["ready_to_publish"];

function formatStatusLabel($status)
{
    $labels = [
        "draft"              => "DRAFT",
        "artwork_submission" => "ARTWORK SUBMISSION",
        "ready_to_publish"   => "READY TO PUBLISH",
        "published"          => "PUBLISHED",
        "completed"          => "COMPLETED",
        "cancelled"          => "CANCELLED",
    ];

    return $labels[$status] ?? strtoupper($status);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> My Exhibitions | EDL Gallery </title>
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">

</head>

<body>


<?php require_once "includes/header.php"; ?>


<section class="dashboard-hero dashboard-hero--exhibitions">

    <div class="dashboard-hero-content">

        <p class="dashboard-label">
            EDL GALLERY
        </p>

        <h1>
            My <span>Exhibitions</span>
        </h1>

        <p class="dashboard-description">
            Manage the exhibitions you have been approved to hold
            at EDL Gallery.
        </p>

    </div>

</section>


<section class="dashboard-content">


    <div class="dashboard-heading">

        <p>
            YOUR EXHIBITIONS
        </p>

        <h2>
            Manage Your Exhibitions
        </h2>

    </div>


    <?php if (!empty($successMessage)): ?>

        <div class="exhibition-success-message">

            <?php echo htmlspecialchars($successMessage, ENT_QUOTES, "UTF-8"); ?>

        </div>

    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>

        <div class="exhibition-error-message">

            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8"); ?>

        </div>

    <?php endif; ?>

    <div class="user-tabs">

        <a
            href="my-exhibitions.php?status=all"
            class="user-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>"
        >
            ALL (<?php echo $totalCount; ?>)
        </a>

        <a
            href="my-exhibitions.php?status=active"
            class="user-tab <?php echo $statusFilter === 'active' ? 'active' : ''; ?>"
        >
            ACTIVE (<?php echo $activeCount; ?>)
        </a>

        <a
            href="my-exhibitions.php?status=draft"
            class="user-tab <?php echo $statusFilter === 'draft' ? 'active' : ''; ?>"
        >
            DRAFT (<?php echo $counts['draft']; ?>)
        </a>

        <a
            href="my-exhibitions.php?status=artwork_submission"
            class="user-tab <?php echo $statusFilter === 'artwork_submission' ? 'active' : ''; ?>"
        >
            ARTWORK SUBMISSION (<?php echo $counts['artwork_submission']; ?>)
        </a>

        <a
            href="my-exhibitions.php?status=ready_to_publish"
            class="user-tab <?php echo $statusFilter === 'ready_to_publish' ? 'active' : ''; ?>"
        >
            READY TO PUBLISH (<?php echo $counts['ready_to_publish']; ?>)
        </a>

        <a
            href="my-exhibitions.php?status=published"
            class="user-tab <?php echo $statusFilter === 'published' ? 'active' : ''; ?>"
        >
            PUBLISHED (<?php echo $counts['published']; ?>)
        </a>

        <a
            href="my-exhibitions.php?status=completed"
            class="user-tab <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>"
        >
            COMPLETED (<?php echo $counts['completed']; ?>)
        </a>

        <a
            href="my-exhibitions.php?status=cancelled"
            class="user-tab <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>"
        >
            CANCELLED (<?php echo $counts['cancelled']; ?>)
        </a>

    </div>



    <?php if (empty($exhibitions)): ?>


        <div class="empty-dashboard">

            <h3>
                Nothing Here Yet
            </h3>

            <p>
                You have no exhibitions in this category.
            </p>

        </div>


    <?php else: ?>


        <?php foreach ($exhibitions as $exhibition): ?>


            <?php

            $totalArtworks    = (int)($exhibition["total_artworks"] ?? 0);
            $approvedArtworks = (int)($exhibition["approved_artworks"] ?? 0);
            $pendingArtworks  = (int)($exhibition["pending_artworks"] ?? 0);
            $rejectedArtworks = (int)($exhibition["rejected_artworks"] ?? 0);

            $artworksReady =
                $totalArtworks > 0 &&
                $pendingArtworks === 0 &&
                $rejectedArtworks === 0 &&
                $approvedArtworks === $totalArtworks;

            $currentType = $exhibition["planned_exhibition_type"] ?? "Other";

            $typeOptions = [
                "Solo Exhibition",
                "Group Exhibition",
                "Community Exhibition",
                "Corporate / Brand Exhibition",
                "Other"
            ];

            ?>


            <div class="inquiry-card">

                <div class="inquiry-card-row">

                    <div class="inquiry-main">

                        <h3>

                            <?php echo htmlspecialchars($exhibition["title"], ENT_QUOTES, "UTF-8"); ?>

                        </h3>


                        <p class="inquiry-meta">

                            <?php

                            echo date("F j, Y", strtotime($exhibition["start_date"]));
                            echo " &ndash; ";
                            echo date("F j, Y", strtotime($exhibition["end_date"]));

                            ?>

                        </p>


                        <?php if ($exhibition["status"] === "artwork_submission"): ?>

                            <p class="exhibition-artwork-progress">

                                Artworks:
                                <strong><?php echo $approvedArtworks; ?></strong>
                                approved /
                                <strong><?php echo $totalArtworks; ?></strong>
                                submitted

                            </p>

                        <?php endif; ?>


                    </div>

                    <div class="inquiry-status">

                        <span class="status-label">
                            STATUS
                        </span>

                        <strong
                            class="status-<?php echo htmlspecialchars($exhibition["status"], ENT_QUOTES, "UTF-8"); ?>"
                        >
                            <?php echo formatStatusLabel($exhibition["status"]); ?>
                        </strong>

                        <?php if ($exhibition["status"] === "ready_to_publish"): ?>

                            <p class="exhibition-meta-note">
                                Your exhibition is ready. An administrator
                                will review and publish it soon.
                            </p>

                        <?php elseif ($exhibition["status"] === "published"): ?>

                            <p class="exhibition-meta-note">
                                This exhibition is live. Contact the gallery
                                if you need changes.
                            </p>

                        <?php elseif ($exhibition["status"] === "completed"): ?>

                            <p class="exhibition-meta-note">
                                This exhibition has ended. Thank you for
                                exhibiting with EDL Gallery.
                            </p>

                        <?php elseif ($exhibition["status"] === "cancelled"): ?>

                            <p class="exhibition-meta-note exhibition-meta-note-cancelled">
                                This exhibition was cancelled and can no
                                longer be managed here.
                            </p>

                        <?php endif; ?>

                        <?php if ($exhibition["status"] === "draft"): ?>

                            <div class="exhibition-actions">

                                <a
                                    href="exhibition-edit.php?id=<?php echo (int)$exhibition["id"]; ?>"
                                    class="exhibition-action-button exhibition-manage-button"
                                >
                                    MANAGE
                                </a>

                                <a
                                    href="exhibition-artists.php?id=<?php echo (int)$exhibition["id"]; ?>"
                                    class="exhibition-action-button exhibition-artists-button"
                                >
                                    MANAGE ARTISTS
                                </a>

                                <a
                                    href="exhibition-artworks.php?id=<?php echo (int)$exhibition["id"]; ?>"
                                    class="exhibition-action-button exhibition-submit-button"
                                >
                                    SUBMIT ARTWORKS
                                </a>

                            </div>

                            <?php if (!empty($exhibition["inquiry_id"])): ?>

                                <form method="POST" class="type-switch-form">

                                    <?php echo csrf_field(); ?>

                                    <input type="hidden" name="action" value="change_exhibition_type">

                                    <input type="hidden" name="exhibition_id" value="<?php echo (int)$exhibition["id"]; ?>">

                                    <label for="type-<?php echo (int)$exhibition["id"]; ?>" class="type-switch-label">
                                        EXHIBITION TYPE
                                    </label>

                                    <select
                                        id="type-<?php echo (int)$exhibition["id"]; ?>"
                                        name="new_exhibition_type"
                                        class="type-switch-select"
                                        onchange="this.form.submit()"
                                    >

                                        <?php foreach ($typeOptions as $typeOpt): ?>

                                            <option
                                                value="<?php echo htmlspecialchars($typeOpt); ?>"
                                                <?php echo ($currentType === $typeOpt) ? "selected" : ""; ?>
                                            >
                                                <?php echo htmlspecialchars($typeOpt); ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </form>

                            <?php endif; ?>

                        <?php elseif ($exhibition["status"] === "artwork_submission"): ?>

                            <div class="exhibition-actions">

                                <a
                                    href="exhibition-artists.php?id=<?php echo (int)$exhibition["id"]; ?>"
                                    class="exhibition-action-button exhibition-artists-button"
                                >
                                    MANAGE ARTISTS
                                </a>

                                <a
                                    href="exhibition-artworks.php?id=<?php echo (int)$exhibition["id"]; ?>"
                                    class="exhibition-action-button exhibition-submit-button"
                                >
                                    SUBMIT ARTWORKS
                                </a>

                                <?php if ($artworksReady): ?>

                                    <form
                                        method="POST"
                                        class="ready-publish-form"
                                    >

                                        <?php echo csrf_field(); ?>

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="ready_to_publish"
                                        >

                                        <input
                                            type="hidden"
                                            name="exhibition_id"
                                            value="<?php echo (int)$exhibition["id"]; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="exhibition-action-button exhibition-ready-button"
                                            onclick="return confirm('Are you sure you want to mark this exhibition as ready to publish?');"
                                        >
                                            READY TO PUBLISH
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span class="exhibition-action-disabled">
                                        WAITING FOR ARTWORK APPROVAL
                                    </span>

                                <?php endif; ?>

                            </div>

                            <?php if (!empty($exhibition["inquiry_id"])): ?>

                                <form method="POST" class="type-switch-form">

                                    <?php echo csrf_field(); ?>

                                    <input type="hidden" name="action" value="change_exhibition_type">

                                    <input type="hidden" name="exhibition_id" value="<?php echo (int)$exhibition["id"]; ?>">

                                    <label for="type-<?php echo (int)$exhibition["id"]; ?>" class="type-switch-label">
                                        EXHIBITION TYPE
                                    </label>

                                    <select
                                        id="type-<?php echo (int)$exhibition["id"]; ?>"
                                        name="new_exhibition_type"
                                        class="type-switch-select"
                                        onchange="this.form.submit()"
                                    >

                                        <?php foreach ($typeOptions as $typeOpt): ?>

                                            <option
                                                value="<?php echo htmlspecialchars($typeOpt); ?>"
                                                <?php echo ($currentType === $typeOpt) ? "selected" : ""; ?>
                                            >
                                                <?php echo htmlspecialchars($typeOpt); ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </form>

                            <?php endif; ?>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


        <?php endforeach; ?>


    <?php endif; ?>


</section>


<?php require_once "includes/footer.php"; ?>


    </body>

</html>
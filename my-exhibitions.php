<?php

session_start();
$basePath = "";

require_once "database/config.php";
require_once "security/shield.php";
require_once "security/authorize.php";


if (!isUser()) {
    header("Location: login.php");
    exit;
}


$userId = $_SESSION["user_id"];


// =========================================================
// SUCCESS / ERROR MESSAGE
// =========================================================

$successMessage = "";
$errorMessage = "";


// =========================================================
// READY TO PUBLISH ACTION
// =========================================================

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


    if (
        $action === "ready_to_publish" &&
        $exhibitionId
    ) {

        // -----------------------------------------------
        // Verify ownership
        // -----------------------------------------------

        $ownershipSql = "
            SELECT id, status
            FROM exhibitions
            WHERE id = :exhibition_id
              AND organizer_id = :organizer_id
            LIMIT 1
        ";

        $ownershipStmt = $pdo->prepare($ownershipSql);

        $ownershipStmt->bindValue(
            ":exhibition_id",
            $exhibitionId,
            PDO::PARAM_INT
        );

        $ownershipStmt->bindValue(
            ":organizer_id",
            $userId,
            PDO::PARAM_INT
        );

        $ownershipStmt->execute();

        $exhibition = $ownershipStmt->fetch();


        if (!$exhibition) {

            $errorMessage =
                "You are not authorized to manage this exhibition.";

        } elseif ($exhibition["status"] !== "artwork_submission") {

            $errorMessage =
                "This exhibition is not currently in the artwork submission stage.";

        } else {

            // -----------------------------------------------
            // Check artwork status
            // -----------------------------------------------

            $artworkSql = "
                SELECT
                    COUNT(*) AS total_artworks,

                    SUM(
                        CASE
                            WHEN status = 'approved'
                            THEN 1
                            ELSE 0
                        END
                    ) AS approved_artworks,

                    SUM(
                        CASE
                            WHEN status = 'pending'
                            THEN 1
                            ELSE 0
                        END
                    ) AS pending_artworks,

                    SUM(
                        CASE
                            WHEN status = 'rejected'
                            THEN 1
                            ELSE 0
                        END
                    ) AS rejected_artworks

                FROM artworks

                WHERE exhibition_id = :exhibition_id
            ";

            $artworkStmt = $pdo->prepare($artworkSql);

            $artworkStmt->bindValue(
                ":exhibition_id",
                $exhibitionId,
                PDO::PARAM_INT
            );

            $artworkStmt->execute();

            $artworkStatus = $artworkStmt->fetch();


            $totalArtworks =
                (int)($artworkStatus["total_artworks"] ?? 0);

            $approvedArtworks =
                (int)($artworkStatus["approved_artworks"] ?? 0);

            $pendingArtworks =
                (int)($artworkStatus["pending_artworks"] ?? 0);

            $rejectedArtworks =
                (int)($artworkStatus["rejected_artworks"] ?? 0);


            // -----------------------------------------------
            // Validate artwork completion
            // -----------------------------------------------

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

                // -------------------------------------------
                // Change exhibition status
                // -------------------------------------------

                $updateSql = "
                    UPDATE exhibitions

                    SET status = 'ready_to_publish'

                    WHERE id = :exhibition_id
                      AND organizer_id = :organizer_id
                      AND status = 'artwork_submission'
                ";

                $updateStmt = $pdo->prepare($updateSql);

                $updateStmt->bindValue(
                    ":exhibition_id",
                    $exhibitionId,
                    PDO::PARAM_INT
                );

                $updateStmt->bindValue(
                    ":organizer_id",
                    $userId,
                    PDO::PARAM_INT
                );

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

    } else {

        $errorMessage =
            "Invalid exhibition action.";

    }

}


// =========================================================
// GET USER'S EXHIBITIONS
// =========================================================

$sql = "
    SELECT
        e.*,

        COUNT(a.id) AS total_artworks,

        SUM(
            CASE
                WHEN a.status = 'approved'
                THEN 1
                ELSE 0
            END
        ) AS approved_artworks,

        SUM(
            CASE
                WHEN a.status = 'pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_artworks,

        SUM(
            CASE
                WHEN a.status = 'rejected'
                THEN 1
                ELSE 0
            END
        ) AS rejected_artworks

    FROM exhibitions e

    LEFT JOIN artworks a
        ON a.exhibition_id = e.id

    WHERE e.organizer_id = :organizer_id

    GROUP BY e.id

    ORDER BY e.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->bindValue(
    ":organizer_id",
    $userId,
    PDO::PARAM_INT
);

$stmt->execute();

$exhibitions = $stmt->fetchAll();


// =========================================================
// STATUS LABEL
// =========================================================

function formatStatusLabel($status)
{

    $labels = [

        "draft" =>
            "DRAFT",

        "artwork_submission" =>
            "ARTWORK SUBMISSION",

        "ready_to_publish" =>
            "READY TO PUBLISH",

        "published" =>
            "PUBLISHED",

        "completed" =>
            "COMPLETED",

        "cancelled" =>
            "CANCELLED",

    ];

    return $labels[$status] ?? strtoupper($status);
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

    <title>
        My Exhibitions | EDL Gallery
    </title>

    <link
        rel="icon"
        type="image/x-icon"
        href="Images/logo.png"
    >

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<?php require_once "includes/header.php"; ?>


<!-- =========================================================
     HERO
     ========================================================= -->

<section
    class="dashboard-hero"
    style="
        background-image:
        linear-gradient(
            rgba(0,0,0,0.65),
            rgba(0,0,0,0.65)
        ),
        url('Images/background-2.jpg');
    "
>

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


<!-- =========================================================
     EXHIBITIONS
     ========================================================= -->

<section class="dashboard-content">


    <div class="dashboard-heading">

        <p>
            YOUR EXHIBITIONS
        </p>

        <h2>
            Manage Your Exhibitions
        </h2>

    </div>


    <!-- =====================================================
         SUCCESS MESSAGE
         ===================================================== -->

    <?php if (!empty($successMessage)): ?>

        <div class="exhibition-success-message">

            <?php
            echo htmlspecialchars(
                $successMessage,
                ENT_QUOTES,
                "UTF-8"
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ERROR MESSAGE
         ===================================================== -->

    <?php if (!empty($errorMessage)): ?>

        <div class="exhibition-error-message">

            <?php
            echo htmlspecialchars(
                $errorMessage,
                ENT_QUOTES,
                "UTF-8"
            );
            ?>

        </div>

    <?php endif; ?>


    <?php if (empty($exhibitions)): ?>


        <!-- =================================================
             NO EXHIBITIONS
             ================================================= -->

        <div class="empty-dashboard">

            <h3>
                No Exhibitions Yet
            </h3>

            <p>
                Exhibitions appear here once an admin approves one
                of your exhibition inquiries.
            </p>


            <a
                href="my-inquiries.php"
                class="dashboard-outline-button"
            >
                VIEW MY INQUIRIES
            </a>


            <a
                href="rent-space.php"
                class="dashboard-outline-button"
            >
                RENT OUR SPACE
            </a>

        </div>


    <?php else: ?>


        <!-- =================================================
             EXHIBITION LIST
             ================================================= -->

        <?php foreach ($exhibitions as $exhibition): ?>


            <?php

            $totalArtworks =
                (int)($exhibition["total_artworks"] ?? 0);

            $approvedArtworks =
                (int)($exhibition["approved_artworks"] ?? 0);

            $pendingArtworks =
                (int)($exhibition["pending_artworks"] ?? 0);

            $rejectedArtworks =
                (int)($exhibition["rejected_artworks"] ?? 0);


            $artworksReady =
                $totalArtworks > 0 &&
                $pendingArtworks === 0 &&
                $rejectedArtworks === 0 &&
                $approvedArtworks === $totalArtworks;

            ?>


            <div class="inquiry-card">

                <div class="inquiry-card-row">


                    <!-- =====================================
                         EXHIBITION INFORMATION
                         ===================================== -->

                    <div class="inquiry-main">

                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $exhibition["title"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </h3>


                        <p class="inquiry-meta">

                            <?php

                            echo date(
                                "F j, Y",
                                strtotime(
                                    $exhibition["start_date"]
                                )
                            );

                            echo " &ndash; ";

                            echo date(
                                "F j, Y",
                                strtotime(
                                    $exhibition["end_date"]
                                )
                            );

                            ?>

                        </p>


                        <?php if (
                            $exhibition["status"] ===
                            "artwork_submission"
                        ): ?>

                            <p class="exhibition-artwork-progress">

                                Artworks:
                                <strong>
                                    <?php echo $approvedArtworks; ?>
                                </strong>
                                approved /
                                <strong>
                                    <?php echo $totalArtworks; ?>
                                </strong>
                                submitted

                            </p>

                        <?php endif; ?>


                    </div>


                    <!-- =====================================
                         STATUS + ACTIONS
                         ===================================== -->

                    <div class="inquiry-status">


                        <span class="status-label">
                            STATUS
                        </span>


                        <strong
                            class="status-<?php echo htmlspecialchars(
                                $exhibition["status"],
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                        >

                            <?php
                            echo formatStatusLabel(
                                $exhibition["status"]
                            );
                            ?>

                        </strong>


                        <!-- =================================
                             EXHIBITION ACTIONS
                             ================================= -->

                        <div class="exhibition-actions">


                            <!-- MANAGE EXHIBITION -->

                            <a
                                href="exhibition-edit.php?id=<?php echo (int)$exhibition["id"]; ?>"
                                class="exhibition-action-button exhibition-manage-button"
                            >
                                MANAGE
                            </a>


                            <!-- MANAGE ARTISTS -->

                            <a
                                href="exhibition-artists.php?id=<?php echo (int)$exhibition["id"]; ?>"
                                class="exhibition-action-button exhibition-artists-button"
                            >
                                MANAGE ARTISTS
                            </a>


                            <!-- SUBMIT ARTWORKS -->

                            <a
                                href="exhibition-artworks.php?id=<?php echo (int)$exhibition["id"]; ?>"
                                class="exhibition-action-button exhibition-submit-button"
                            >
                                SUBMIT ARTWORKS
                            </a>


                            <!-- =================================
                                 READY TO PUBLISH
                                 ================================= -->

                            <?php if (
                                $exhibition["status"] ===
                                "artwork_submission"
                            ): ?>


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


                                    <span
                                        class="exhibition-action-disabled"
                                    >
                                        WAITING FOR ARTWORK APPROVAL
                                    </span>


                                <?php endif; ?>


                            <?php endif; ?>


                        </div>


                    </div>


                </div>

            </div>


        <?php endforeach; ?>


    <?php endif; ?>


</section>


<?php require_once "includes/footer.php"; ?>


</body>

</html>
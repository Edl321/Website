<?php

require_once "database/config.php";

$basePath = "";


// =========================================================
// GET EXHIBITION ID
// =========================================================

$exhibitionId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


// =========================================================
// INVALID ID
// =========================================================

if (!$exhibitionId) {

    http_response_code(404);

    $exhibition = null;

} else {
   
    $sql = "
        SELECT *
        FROM exhibitions
        WHERE id = :exhibition_id
        AND status = 'published'
        AND end_date >= CURDATE()
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(
        ":exhibition_id",
        $exhibitionId,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $exhibition = $stmt->fetch();

}


// =========================================================
// EXHIBITION NOT FOUND
// =========================================================

if (!$exhibition) {

    http_response_code(404);

}


// =========================================================
// HELPER FOR IMAGE PATHS
// =========================================================

function exhibitionImagePath($image)
{

    if (empty($image)) {
        return "";
    }


    // If the database already contains Images/
    if (
        str_starts_with($image, "Images/") ||
        str_starts_with($image, "images/")
    ) {

        return $image;

    }


    // If it is already an external image
    if (
        str_starts_with($image, "http://") ||
        str_starts_with($image, "https://")
    ) {

        return $image;

    }


    return "Images/" . basename($image);
}


// =========================================================
// DEFAULT ARRAYS
// =========================================================

$artists = [];
$artworks = [];


// =========================================================
// GET ARTISTS + ARTWORKS
// =========================================================

if ($exhibition) {


    // =====================================================
    // ASSIGNED ARTISTS
    // =====================================================

    $artistSql = "
        SELECT
            a.id,
            a.name,
            a.biography,
            a.image

        FROM exhibition_artists ea

        INNER JOIN artists a
            ON a.id = ea.artist_id

        WHERE ea.exhibition_id = :exhibition_id

        ORDER BY a.name ASC
    ";

    $artistStmt = $pdo->prepare($artistSql);

    $artistStmt->bindValue(
        ":exhibition_id",
        $exhibitionId,
        PDO::PARAM_INT
    );

    $artistStmt->execute();

    $artists = $artistStmt->fetchAll();


    // =====================================================
    // APPROVED ARTWORKS ONLY
    // =====================================================

    $artworkSql = "
        SELECT
            aw.id,
            aw.title,
            aw.medium,
            aw.year_created,
            aw.dimensions,
            aw.description,
            aw.price,
            aw.image,
            ar.name AS artist_name

        FROM artworks aw

        INNER JOIN artists ar
            ON ar.id = aw.artist_id

        WHERE aw.exhibition_id = :exhibition_id
          AND aw.status = 'approved'

        ORDER BY aw.created_at ASC, aw.id ASC
    ";

    $artworkStmt = $pdo->prepare($artworkSql);

    $artworkStmt->bindValue(
        ":exhibition_id",
        $exhibitionId,
        PDO::PARAM_INT
    );

    $artworkStmt->execute();

    $artworks = $artworkStmt->fetchAll();

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

        <?php if ($exhibition): ?>

            <?php echo htmlspecialchars(
                $exhibition["title"],
                ENT_QUOTES,
                "UTF-8"
            ); ?>

            |

        <?php endif; ?>

        EDL Gallery

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


<?php if (!$exhibition): ?>


    <!-- =====================================================
         EXHIBITION NOT FOUND
         ===================================================== -->

    <section class="exhibition-not-found">

        <div class="exhibition-not-found-content">

            <p class="exhibition-details-label">
                EDL GALLERY
            </p>

            <h1>
                EXHIBITION NOT FOUND
            </h1>

            <p>
                The exhibition you are looking for is no longer
                available or has not been published.
            </p>

            <a
                href="exhibition.php"
                class="exhibition-back-link"
            >
                &larr; BACK TO EXHIBITIONS
            </a>

        </div>

    </section>


<?php else: ?>


    <section class="exhibition-details-hero">

        <?php if (!empty($exhibition["image"])): ?>

            <div class="exhibition-details-hero-image">

                <img
                    src="<?php echo htmlspecialchars(
                        exhibitionImagePath(
                            $exhibition["image"]
                        ),
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                    alt="<?php echo htmlspecialchars(
                        $exhibition["title"],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                >

            </div>

        <?php endif; ?>


        <div class="exhibition-details-hero-content">

            <p class="exhibition-details-label">
                EDL GALLERY
            </p>

            <h1>

                <?php echo htmlspecialchars(
                    $exhibition["title"],
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>

            </h1>


            <div class="exhibition-details-date">

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

            </div>

        </div>

    </section>


    <!-- =====================================================
         EXHIBITION INFORMATION
         ===================================================== -->

    <section class="exhibition-details-section">


        <div class="exhibition-details-container">


            <!-- BACK BUTTON -->

            <a
                href="exhibition.php"
                class="exhibition-back-link"
            >
                &larr; BACK TO EXHIBITIONS
            </a>


            <!-- DESCRIPTION -->

            <div class="exhibition-description-block">

                <p class="exhibition-section-label">
                    ABOUT THE EXHIBITION
                </p>


                <h2>

                    <?php echo htmlspecialchars(
                        $exhibition["title"],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>

                </h2>


                <?php if (
                    !empty($exhibition["description"])
                ): ?>

                    <p class="exhibition-full-description">

                        <?php echo nl2br(
                            htmlspecialchars(
                                $exhibition["description"],
                                ENT_QUOTES,
                                "UTF-8"
                            )
                        ); ?>

                    </p>

                <?php else: ?>

                    <p class="exhibition-full-description">

                        More information about this exhibition
                        will be available soon.

                    </p>

                <?php endif; ?>


            </div>


        </div>

    </section>


    <!-- =====================================================
         FEATURED ARTISTS
         ===================================================== -->

    <section class="exhibition-artists-section">


        <div class="exhibition-details-container">


            <div class="exhibition-section-heading">

                <p class="exhibition-section-label">
                    THE ARTISTS
                </p>

                <h2>
                    Featured Artists
                </h2>

            </div>


            <?php if (empty($artists)): ?>


                <div class="exhibition-empty-section">

                    <p>
                        Artist information will be available soon.
                    </p>

                </div>


            <?php else: ?>


                <div class="exhibition-artists-grid">


                    <?php foreach ($artists as $artist): ?>


                        <article class="exhibition-artist-card">


                            <?php if (
                                !empty($artist["image"])
                            ): ?>

                                <div class="exhibition-artist-image">

                                    <img
                                        src="<?php echo htmlspecialchars(
                                            exhibitionImagePath(
                                                $artist["image"]
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                        alt="<?php echo htmlspecialchars(
                                            $artist["name"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                    >

                                </div>

                            <?php endif; ?>


                            <div class="exhibition-artist-info">

                                <h3>

                                    <?php echo htmlspecialchars(
                                        $artist["name"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>

                                </h3>


                                <?php if (
                                    !empty($artist["biography"])
                                ): ?>

                                    <p>

                                        <?php echo nl2br(
                                            htmlspecialchars(
                                                $artist["biography"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            )
                                        ); ?>

                                    </p>

                                <?php else: ?>

                                    <p>
                                        Artist biography coming soon.
                                    </p>

                                <?php endif; ?>

                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </div>

    </section>


    <section class="exhibition-artworks-section">


        <div class="exhibition-details-container">


            <div class="exhibition-section-heading">

                <p class="exhibition-section-label">
                    THE COLLECTION
                </p>

                <h2>
                    Featured Artworks
                </h2>

            </div>


            <?php if (empty($artworks)): ?>


                <div class="exhibition-empty-section">

                    <p>
                        Artwork information will be available soon.
                    </p>

                </div>


            <?php else: ?>


                <div class="exhibition-artworks-grid">


                    <?php foreach ($artworks as $artwork): ?>

<article class="exhibition-artwork-card">

    <a
        href="exhibition-artwork-details.php?id=<?php echo (int)$artwork["id"]; ?>"
        class="exhibition-artwork-link"
    >


        <?php if (!empty($artwork["image"])): ?>

            <div class="exhibition-artwork-image">

                <img
                    src="<?php echo htmlspecialchars(
                        exhibitionImagePath(
                            $artwork["image"]
                        ),
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                    alt="<?php echo htmlspecialchars(
                        $artwork["title"],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                >

            </div>

        <?php endif; ?>


        <div class="exhibition-artwork-info">


            <p class="exhibition-artwork-artist">

                <?php echo htmlspecialchars(
                    $artwork["artist_name"],
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>

            </p>


            <h3>

                <?php echo htmlspecialchars(
                    $artwork["title"],
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>

            </h3>


            <?php if (!empty($artwork["medium"])): ?>

                <p>

                    <strong>
                        Medium:
                    </strong>

                    <?php echo htmlspecialchars(
                        $artwork["medium"],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>

                </p>

            <?php endif; ?>


            <?php if (!empty($artwork["year_created"])): ?>

                <p>

                    <strong>
                        Year:
                    </strong>

                    <?php echo htmlspecialchars(
                        $artwork["year_created"],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>

                </p>

            <?php endif; ?>


            <?php if (!empty($artwork["dimensions"])): ?>

                <p>

                    <strong>
                        Dimensions:
                    </strong>

                    <?php echo htmlspecialchars(
                        $artwork["dimensions"],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>

                </p>

            <?php endif; ?>


            <?php if (!empty($artwork["description"])): ?>

                <p class="exhibition-artwork-description">

                    <?php echo nl2br(
                        htmlspecialchars(
                            $artwork["description"],
                            ENT_QUOTES,
                            "UTF-8"
                        )
                    ); ?>

                </p>

            <?php endif; ?>


            <?php if (
                $artwork["price"] !== null &&
                $artwork["price"] !== ""
            ): ?>

                <p class="exhibition-artwork-price">

                    ₱<?php echo number_format(
                        (float)$artwork["price"],
                        2
                    ); ?>

                </p>

            <?php else: ?>

                <p class="exhibition-artwork-price">

                    PRICE ON REQUEST

                </p>

            <?php endif; ?>


            <span class="exhibition-artwork-view">

                VIEW ARTWORK
                <span>→</span>

            </span>


        </div>


    </a>

</article>  


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </div>

    </section>


<?php endif; ?>


<?php require_once "includes/footer.php"; ?>


</body>

</html>
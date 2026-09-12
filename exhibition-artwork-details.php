<?php

$basePath = "";
require_once "database/config.php";



$artworkId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

$artwork = null;

if ($artworkId) {

    $query = $pdo->prepare("
        SELECT
            aw.id,
            aw.title,
            aw.medium,
            aw.year_created,
            aw.dimensions,
            aw.description,
            aw.price,
            aw.image,

            ar.id AS artist_id,
            ar.name AS artist_name,
            ar.biography AS artist_biography,
            ar.image AS artist_image,

            e.id AS exhibition_id,
            e.title AS exhibition_title,
            e.start_date,
            e.end_date

        FROM artworks aw

        INNER JOIN artists ar
            ON ar.id = aw.artist_id

        INNER JOIN exhibitions e
            ON e.id = aw.exhibition_id

        WHERE aw.id = :artwork_id
        AND aw.status = 'approved'
        AND e.status = 'published'
        AND e.end_date >= CURDATE()

        LIMIT 1
    ");

    $query->execute([
        ":artwork_id" => $artworkId
    ]);

    $artwork = $query->fetch(PDO::FETCH_ASSOC);
}



function artworkImagePath($image)
{
    if (empty($image)) {
        return "";
    }

    if (
        str_starts_with($image, "Images/") ||
        str_starts_with($image, "images/")
    ) {
        return $image;
    }

    if (
        str_starts_with($image, "http://") ||
        str_starts_with($image, "https://")
    ) {
        return $image;
    }

    return "Images/" . basename($image);
}

?>

<?php require_once "includes/header.php"; ?>


<?php if (!$artwork): ?>

    <main class="exhibition-not-found">

        <div class="exhibition-not-found-content">

            <span class="exhibition-details-label">
                ARTWORK
            </span>

            <h1>
                Artwork Not Available
            </h1>

            <p>
                This artwork is no longer available or has not been
                approved for public viewing.
            </p>

            <a
                href="exhibition.php"
                class="exhibition-back-link"
            >
                &larr; BACK TO EXHIBITIONS
            </a>

        </div>

    </main>


<?php else: ?>

    <link rel="stylesheet" href="style.css">
    <main class="exhibition-artwork-details-page">

        <section class="artwork-details-hero">

            <?php if (!empty($artwork["image"])): ?>

                <div class="artwork-details-hero-image">

                    <img
                        src="<?php echo htmlspecialchars(
                            artworkImagePath($artwork["image"]),
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


            <div class="artwork-details-hero-overlay"></div>


            <div class="artwork-details-hero-content">

                <p class="artwork-details-label">
                    ARTWORK
                </p>

                <h1>
                    <?php echo htmlspecialchars(
                        $artwork["title"],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>
                </h1>

                <p class="artwork-details-artist">
                    BY
                    <?php echo htmlspecialchars(
                        $artwork["artist_name"],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>
                </p>

            </div>

        </section>

        <div class="artwork-details-container">


            <!-- BACK TO EXHIBITION -->

            <a
                href="exhibition-details.php?id=<?php echo (int)$artwork["exhibition_id"]; ?>"
                class="exhibition-back-link"
            >
                &larr;
                BACK TO
                <?php echo htmlspecialchars(
                    $artwork["exhibition_title"],
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>
            </a>


            <section class="artwork-details-main">

                <div class="artwork-details-information">


                    <p class="artwork-details-section-label">
                        ARTWORK INFORMATION
                    </p>


                    <h2>
                        <?php echo htmlspecialchars(
                            $artwork["title"],
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>
                    </h2>


                    <p class="artwork-details-byline">

                        By
                        <strong>
                            <?php echo htmlspecialchars(
                                $artwork["artist_name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>
                        </strong>

                    </p>

                    <div class="artwork-details-meta">


                        <?php if (!empty($artwork["medium"])): ?>

                            <div class="artwork-meta-item">

                                <span>
                                    MEDIUM
                                </span>

                                <strong>
                                    <?php echo htmlspecialchars(
                                        $artwork["medium"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </strong>

                            </div>

                        <?php endif; ?>


                        <?php if (!empty($artwork["year_created"])): ?>

                            <div class="artwork-meta-item">

                                <span>
                                    YEAR
                                </span>

                                <strong>
                                    <?php echo htmlspecialchars(
                                        $artwork["year_created"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </strong>

                            </div>

                        <?php endif; ?>


                        <?php if (!empty($artwork["dimensions"])): ?>

                            <div class="artwork-meta-item">

                                <span>
                                    DIMENSIONS
                                </span>

                                <strong>
                                    <?php echo htmlspecialchars(
                                        $artwork["dimensions"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </strong>

                            </div>

                        <?php endif; ?>


                        <div class="artwork-meta-item">

                            <span>
                                EXHIBITION
                            </span>

                            <strong>
                                <?php echo htmlspecialchars(
                                    $artwork["exhibition_title"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>
                            </strong>

                        </div>


                    </div>



                    <?php if (!empty($artwork["description"])): ?>

                        <div class="artwork-details-description">

                            <p class="artwork-details-section-label">
                                ABOUT THE ARTWORK
                            </p>

                            <p>
                                <?php echo nl2br(
                                    htmlspecialchars(
                                        $artwork["description"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                ); ?>
                            </p>

                        </div>

                    <?php endif; ?>


                    <!-- PRICE -->

                    <div class="artwork-details-price">

                        <span>
                            PRICE
                        </span>

                        <?php if (
                            $artwork["price"] !== null &&
                            $artwork["price"] !== ""
                        ): ?>

                            <strong>
                                ₱<?php echo number_format(
                                    (float)$artwork["price"],
                                    2
                                ); ?>
                            </strong>

                        <?php else: ?>

                            <strong>
                                PRICE ON REQUEST
                            </strong>

                        <?php endif; ?>

                    </div>


                </div>

            </section>

            <section class="artwork-details-artist-section">


                <p class="artwork-details-section-label">
                    THE ARTIST
                </p>


                <div class="artwork-details-artist-card">


                    <?php if (!empty($artwork["artist_image"])): ?>

                        <div class="artwork-details-artist-image">

                            <img
                                src="<?php echo htmlspecialchars(
                                    artworkImagePath($artwork["artist_image"]),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                alt="<?php echo htmlspecialchars(
                                    $artwork["artist_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                            >

                        </div>

                    <?php endif; ?>


                    <div class="artwork-details-artist-information">

                        <h2>
                            <?php echo htmlspecialchars(
                                $artwork["artist_name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>
                        </h2>


                        <?php if (!empty($artwork["artist_biography"])): ?>

                            <p>
                                <?php echo nl2br(
                                    htmlspecialchars(
                                        $artwork["artist_biography"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                ); ?>
                            </p>

                        <?php else: ?>

                            <p>
                                Artist biography is currently unavailable.
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

            </section>

            <div class="artwork-details-bottom">

                <a
                    href="exhibition-details.php?id=<?php echo (int)$artwork["exhibition_id"]; ?>"
                    class="exhibition-back-link"
                >
                    &larr;
                    BACK TO EXHIBITION
                </a>

            </div>


        </div>

    </main>

<?php endif; ?>


<?php require_once "includes/footer.php"; ?>
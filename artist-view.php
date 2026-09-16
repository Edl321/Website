<?php

$basePath = "";
require_once "includes/function.php";
require_once "database/config.php";
require_once "security/authorize.php";


/*
|--------------------------------------------------------------------------
| GET ARTIST ID
|--------------------------------------------------------------------------
*/

$artistId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$artist = null;

if ($artistId) {

    $stmt = $pdo->prepare("
        SELECT id, name, biography, image
        FROM artists
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([":id" => $artistId]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);
}


/*
|--------------------------------------------------------------------------
| IF ARTIST FOUND, PULL RELATED DATA
|--------------------------------------------------------------------------
*/

$exhibitions = [];
$artworks    = [];

if ($artist) {

    /*
    |--------------------------------------------------------------------------
    | CURRENT + UPCOMING EXHIBITIONS FEATURING THIS ARTIST
    |--------------------------------------------------------------------------
    */

    $exStmt = $pdo->prepare("
        SELECT
            e.id,
            e.title,
            e.start_date,
            e.end_date,
            e.image
        FROM exhibition_artists ea
        INNER JOIN exhibitions e
            ON e.id = ea.exhibition_id
        WHERE ea.artist_id = :artist_id
          AND e.status = 'published'
          AND e.end_date >= CURDATE()
        ORDER BY e.start_date ASC
    ");

    $exStmt->execute([":artist_id" => $artistId]);
    $exhibitions = $exStmt->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | APPROVED ARTWORKS BY THIS ARTIST (in currently-visible exhibitions)
    |--------------------------------------------------------------------------
    */

    $awStmt = $pdo->prepare("
        SELECT
            aw.id,
            aw.title,
            aw.medium,
            aw.year_created,
            aw.image,
            e.title AS exhibition_title
        FROM artworks aw
        INNER JOIN exhibitions e
            ON e.id = aw.exhibition_id
        WHERE aw.artist_id = :artist_id
          AND aw.status = 'approved'
          AND e.status = 'published'
          AND e.end_date >= CURDATE()
        ORDER BY aw.created_at DESC
    ");

    $awStmt->execute([":artist_id" => $artistId]);
    $artworks = $awStmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>

        <?php if ($artist): ?>
            <?php echo htmlspecialchars($artist["name"], ENT_QUOTES, "UTF-8"); ?> |
        <?php endif; ?>

        EDL Gallery

    </title>

    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">

</head>

<body>

<?php require_once "includes/header.php"; ?>


<?php if (!$artist): ?>

    <!-- =========================================================
         ARTIST NOT FOUND
    ========================================================= -->

    <section class="exhibition-not-found">

        <div class="exhibition-not-found-content">

            <p class="exhibition-details-label">
                EDL GALLERY
            </p>

            <h1>
                ARTIST NOT FOUND
            </h1>

            <p>
                The artist you're looking for isn't available.
            </p>

            <a href="artist.php" class="exhibition-back-link">
                &larr; BACK TO ARTISTS
            </a>

        </div>

    </section>


<?php else: ?>


    <!-- =========================================================
         ARTIST HERO
    ========================================================= -->

    <section class="artist-view-hero">

        <div class="artist-view-hero-inner">

            <a href="artist.php" class="exhibition-back-link">
                &larr; BACK TO ARTISTS
            </a>

            <div class="artist-view-hero-layout">

                <?php if (!empty($artist["image"])): ?>

                    <div class="artist-view-hero-image">

                        <img
                            src="<?php echo htmlspecialchars(edlImagePath($artist["image"]), ENT_QUOTES, "UTF-8"); ?>"
                            alt="<?php echo htmlspecialchars($artist["name"], ENT_QUOTES, "UTF-8"); ?>"
                        >

                    </div>

                <?php endif; ?>

                <div class="artist-view-hero-info">

                    <p class="artist-view-label">
                        ARTIST
                    </p>

                    <h1>
                        <?php echo htmlspecialchars($artist["name"], ENT_QUOTES, "UTF-8"); ?>
                    </h1>

                    <?php if (!empty($artist["biography"])): ?>

                        <p class="artist-view-intro">
                            <?php echo nl2br(htmlspecialchars(
                                mb_strlen($artist["biography"]) > 200
                                    ? mb_substr($artist["biography"], 0, 200) . "…"
                                    : $artist["biography"],
                                ENT_QUOTES,
                                "UTF-8"
                            )); ?>
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>


    <!-- =========================================================
         FULL BIOGRAPHY
    ========================================================= -->

    <?php if (!empty($artist["biography"])): ?>

        <section class="artist-view-bio-section">

            <div class="artist-view-container">

                <p class="artist-view-section-label">
                    ABOUT THE ARTIST
                </p>

                <div class="artist-view-bio">

                    <?php echo nl2br(htmlspecialchars($artist["biography"], ENT_QUOTES, "UTF-8")); ?>

                </div>

            </div>

        </section>

    <?php endif; ?>


    <!-- =========================================================
         CURRENT / UPCOMING EXHIBITIONS
    ========================================================= -->

    <section class="artist-view-exhibitions">

        <div class="artist-view-container">

            <p class="artist-view-section-label">
                NOW SHOWING
            </p>

            <h2>
                Current &amp; Upcoming Exhibitions
            </h2>

            <?php if (empty($exhibitions)): ?>

                <div class="exhibition-empty-section">
                    <p>This artist currently has no upcoming exhibitions.</p>
                </div>

            <?php else: ?>

                <div class="artist-view-exhibition-grid">

                    <?php foreach ($exhibitions as $ex): ?>

                        <article class="artist-view-exhibition-card">

                            <a href="exhibition-details.php?id=<?php echo (int)$ex["id"]; ?>">

                                <?php if (!empty($ex["image"])): ?>

                                    <div class="artist-view-exhibition-image">

                                        <img
                                            src="<?php echo htmlspecialchars(edlImagePath($ex["image"]), ENT_QUOTES, "UTF-8"); ?>"
                                            alt="<?php echo htmlspecialchars($ex["title"], ENT_QUOTES, "UTF-8"); ?>"
                                        >

                                    </div>

                                <?php endif; ?>

                                <div class="artist-view-exhibition-info">

                                    <h3>
                                        <?php echo htmlspecialchars($ex["title"], ENT_QUOTES, "UTF-8"); ?>
                                    </h3>

                                    <p>
                                        <?php
                                        echo date("F j", strtotime($ex["start_date"]));
                                        echo " &ndash; ";
                                        echo date("F j, Y", strtotime($ex["end_date"]));
                                        ?>
                                    </p>

                                </div>

                            </a>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- =========================================================
         FEATURED ARTWORKS
    ========================================================= -->

    <section class="artist-view-artworks">

        <div class="artist-view-container">

            <p class="artist-view-section-label">
                THE COLLECTION
            </p>

            <h2>
                Featured Artworks
            </h2>

            <?php if (empty($artworks)): ?>

                <div class="exhibition-empty-section">
                    <p>No artworks from this artist are currently on view.</p>
                </div>

            <?php else: ?>

                <div class="artist-view-artwork-grid">

                    <?php foreach ($artworks as $aw): ?>

                        <article class="artist-view-artwork-card">

                            <a href="exhibition-artwork-details.php?id=<?php echo (int)$aw["id"]; ?>">

                                <?php if (!empty($aw["image"])): ?>

                                    <div class="artist-view-artwork-image">

                                        <img
                                            src="<?php echo htmlspecialchars(edlImagePath($aw["image"]), ENT_QUOTES, "UTF-8"); ?>"
                                            alt="<?php echo htmlspecialchars($aw["title"], ENT_QUOTES, "UTF-8"); ?>"
                                        >

                                    </div>

                                <?php endif; ?>

                                <div class="artist-view-artwork-info">

                                    <h3>
                                        <?php echo htmlspecialchars($aw["title"], ENT_QUOTES, "UTF-8"); ?>
                                    </h3>

                                    <?php if (!empty($aw["medium"])): ?>
                                        <p><strong>Medium:</strong> <?php echo htmlspecialchars($aw["medium"], ENT_QUOTES, "UTF-8"); ?></p>
                                    <?php endif; ?>

                                    <?php if (!empty($aw["year_created"])): ?>
                                        <p><strong>Year:</strong> <?php echo htmlspecialchars($aw["year_created"], ENT_QUOTES, "UTF-8"); ?></p>
                                    <?php endif; ?>

                                    <p class="artist-view-artwork-exhibition">
                                        In: <?php echo htmlspecialchars($aw["exhibition_title"], ENT_QUOTES, "UTF-8"); ?>
                                    </p>

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
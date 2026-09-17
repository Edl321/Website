<?php

$basePath = "";

require_once "includes/function.php";
require_once "database/config.php";
require_once "security/authorize.php";

$sql = "
    SELECT DISTINCT
        a.id,
        a.name,
        a.biography,
        a.image
    FROM artists a

    INNER JOIN exhibition_artists ea
        ON ea.artist_id = a.id

    INNER JOIN exhibitions e
        ON e.id = ea.exhibition_id

    WHERE e.status = 'published'
    AND e.end_date >= CURDATE()

    ORDER BY a.name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$artists = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artists | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">

</head>

<body>


<?php require_once "includes/header.php"; ?>

<section class="art-page-layout">

    <div class="artpage-text">

        <h1>
            Meet the <span>Artists</span>
        </h1>

        <p>
            Discover the creative minds<br>
            behind the art.
        </p>
    </div>

</section>


<section class="artist-section">

    <div class="artist-container">

        <?php if (empty($artists)): ?>

            <div class="no-artists">

                <h2>
                    NO ARTISTS AVAILABLE
                </h2>

                <p>
                    Artist information will be available soon.
                </p>

            </div>

        <?php else: ?>

            <?php foreach ($artists as $artist): ?>

                <?php
                $bio = trim((string)$artist["biography"]);
                $bioPreview = $bio;

                if (mb_strlen($bio) > 200) {
                    $bioPreview = mb_substr($bio, 0, 200);
                    $lastSpace  = mb_strrpos($bioPreview, " ");
                    if ($lastSpace !== false) {
                        $bioPreview = mb_substr($bioPreview, 0, $lastSpace);
                    }
                    $bioPreview .= "…";
                }
                ?>

                <article class="artist-card">

                    <?php if (!empty($artist["image"])): ?>

                        <img src="<?php echo htmlspecialchars(edlImagePath($artist["image"]),ENT_QUOTES,"UTF-8"); ?>"
                                alt="<?php echo htmlspecialchars($artist["name"],ENT_QUOTES,"UTF-8"); ?>"
                        >

                    <?php endif; ?>

                    <div class="artist-info">

                        <h2>
                            <?php echo htmlspecialchars($artist["name"],ENT_QUOTES,"UTF-8"); ?>
                        </h2>

                        <p>
                            <?php echo htmlspecialchars($bioPreview,ENT_QUOTES,"UTF-8"); ?>
                        </p>

                        <a
                            href="artist-view.php?id=<?php echo (int)$artist["id"]; ?>"
                            class="artist-view-link"
                        >
                            View Full Bio →
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</section>


<?php require_once "includes/footer.php"; ?>


</body>

</html>
<?php

require_once "includes/function.php";
require_once "database/config.php";

$basePath = "";


// =========================================================
// GET ALL UPCOMING EXHIBITIONS
// =========================================================

$sql = "
    SELECT *
    FROM exhibitions
    WHERE end_date >= CURDATE()
      AND status = 'published'
    ORDER BY start_date ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$exhibitions = $stmt->fetchAll(PDO::FETCH_ASSOC);


// =========================================================
// GET THE FIRST UPCOMING EXHIBITION
// =========================================================

$featuredExhibition = $exhibitions[0] ?? null;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Exhibitions | EDL Gallery</title>

    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">
</head>

<body>


<?php require_once "includes/header.php"; ?>


<section class="ex-page-layout">

    <div class="ex-text">

        <h1>EXHIBITIONS</h1>


        <?php if ($featuredExhibition): ?>

            <h2>

                <?php
                echo htmlspecialchars(
                    $featuredExhibition["title"],
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>

            </h2>


            <p>

                <?php
                echo date(
                    "F j",
                    strtotime($featuredExhibition["start_date"])
                );
                ?>

                -

                <?php
                echo date(
                    "F j, Y",
                    strtotime($featuredExhibition["end_date"])
                );
                ?>

            </p>


            <a
                href="exhibition-details.php?id=<?php echo (int)$featuredExhibition["id"]; ?>"
                class="exhibit-button"
            >
                VIEW EXHIBITION
            </a>


        <?php else: ?>

            <h2>
                NO UPCOMING EXHIBITIONS
            </h2>

            <p>
                Please check back soon for our next exhibition.
            </p>

        <?php endif; ?>

    </div>


    <?php if (
        $featuredExhibition &&
        !empty($featuredExhibition["image"])
    ): ?>

        <div class="ex-img">

            <img
                src="<?php echo htmlspecialchars(
                    edlImagePath($featuredExhibition["image"]),
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>"
                alt="<?php echo htmlspecialchars(
                    $featuredExhibition["title"],
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>"
            >

        </div>

    <?php endif; ?>

</section>



<!-- =========================================
     UPCOMING EXHIBITIONS
========================================= -->

<section
    class="upcoming-exhibition-layout"
    id="upcoming-exhibitions"
>

    <div class="upcoming-content">

        <h2 class="upcoming-title">
            UPCOMING EXHIBITIONS
        </h2>


        <?php if (empty($exhibitions)): ?>

            <div class="upcoming-exhibit-text">

                <p>
                    No upcoming exhibitions are available.
                </p>

            </div>


        <?php else: ?>


            <?php foreach ($exhibitions as $exhibition): ?>

                <!-- =========================================
                     EXHIBITION CARD
                ========================================= -->

                <article class="upcoming-card">


                    <?php if (!empty($exhibition["image"])): ?>

                        <div class="upcoming-image">

                            <img
                                src="<?php echo htmlspecialchars(
                                    edlImagePath($exhibition["image"]),
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


                    <div class="upcoming-info">

                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $exhibition["title"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </h3>


                        <p>

                            <?php
                            echo date(
                                "F j",
                                strtotime($exhibition["start_date"])
                            );
                            ?>

                            -

                            <?php
                            echo date(
                                "F j, Y",
                                strtotime($exhibition["end_date"])
                            );
                            ?>

                        </p>


                        <?php if (
                            !empty($exhibition["description"])
                        ): ?>

                            <p class="upcoming-description">

                                <?php
                                echo htmlspecialchars(
                                    $exhibition["description"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </p>

                        <?php endif; ?>


                        <!-- VIEW DETAILS BUTTON -->

                        <a
                            href="exhibition-details.php?id=<?php echo (int)$exhibition["id"]; ?>"
                            class="exhibition-details-button"
                        >
                            VIEW EXHIBITION
                            <span>→</span>
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
<?php

    require_once "database/config.php";
    $basePath = "";


// Get all exhibitions from the database
$sql = "SELECT *
        FROM exhibitions
        WHERE end_date >= CURDATE()
        ORDER BY start_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$exhibitions = $stmt->fetchAll();


// Get the first upcoming exhibition
$featuredExhibition = $exhibitions[0] ?? null;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Exhibitions | EDL Gallery</title>
    <link rel = "icon" type="image/x-icon" href = "Images/logo.png">
    <link rel="stylesheet" href="style.css">

</head>

<body>


<?php require_once "includes/header.php"; ?>


<!-- =========================================
     EXHIBITION HERO
========================================= -->

<section class="ex-page-layout">


    <div class="ex-text">

        <h1>EXHIBITIONS</h1>


        <?php if ($featuredExhibition): ?>

            <h2>
                <?php
                echo htmlspecialchars(
                    $featuredExhibition["title"]
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
                href="#upcoming-exhibitions"
                class="exhibit-button"
            >
                VIEW EXHIBITIONS
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
                    $featuredExhibition["image"]
                ); ?>"
                alt="<?php echo htmlspecialchars(
                    $featuredExhibition["title"]
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

                <article class="upcoming-card">


                    <?php if (!empty($exhibition["image"])): ?>

                        <div class="upcoming-image">

                            <img
                                src="<?php echo htmlspecialchars(
                                    $exhibition["image"]
                                ); ?>"
                                alt="<?php echo htmlspecialchars(
                                    $exhibition["title"]
                                ); ?>"
                            >

                        </div>

                    <?php endif; ?>


                    <div class="upcoming-info">

                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $exhibition["title"]
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
                                    $exhibition["description"]
                                );
                                ?>

                            </p>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>


        <?php endif; ?>

    </div>

</section>



<?php require_once "includes/footer.php"; ?>


</body>

</html>
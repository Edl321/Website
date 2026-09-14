<?php

session_start();
require_once "includes/function.php";
require_once "database/config.php";

$basePath = "";


$featuredExhibition = null;

$exhibitionStmt = $pdo->prepare("
    SELECT
        id,
        title,
        description,
        start_date,
        end_date,
        image
    FROM exhibitions
    WHERE status = 'published'
    AND end_date >= CURDATE()
    ORDER BY
        start_date ASC,
        id ASC
    LIMIT 1
");

$exhibitionStmt->execute();

$featuredExhibition = $exhibitionStmt->fetch(PDO::FETCH_ASSOC);


$upcomingExhibitionsStmt = $pdo->prepare("
    SELECT
        id,
        title,
        description,
        start_date,
        end_date,
        image
    FROM exhibitions
    WHERE status = 'published'
    AND end_date >= CURDATE()
    ORDER BY
        start_date ASC,
        id ASC
");

$upcomingExhibitionsStmt->execute();

$upcomingExhibitions = $upcomingExhibitionsStmt->fetchAll(PDO::FETCH_ASSOC);


$featuredArtists = [];

if ($featuredExhibition) {

    $artistStmt = $pdo->prepare("
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
    ");

    $artistStmt->bindValue(
        ":exhibition_id",
        $featuredExhibition["id"],
        PDO::PARAM_INT
    );

    $artistStmt->execute();

    $featuredArtists = $artistStmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDL Gallery</title>
    <link rel="icon"type="image/x-icon"href="Images/logo.png">
    <link rel="stylesheet"type="text/css" href="style.css">

</head>

<body>

<?php require_once "includes/header.php"; ?>

<section class="home">

    <p class="t1">
        Art
        <span>
            Inspires.<br>
        </span>

        We
        <span>
            Showcase.
        </span>
    </p>

    <div class="intro">

        <p class="intro-justified">
            EDL Gallery is an contemporary art space
            that is dedicated in showcasing exceptional artworks,
            support artists, and creating a meaning cultural experience.
        </p>

    </div>

</section>


<section class="upcoming" id="exhibition-id">

    <h2>
        UPCOMING EXHIBITION
    </h2>

    <?php if ($featuredExhibition): ?>

        <div class="exhibition-layout">

            <div class="exhibition-info">

                <h1>
                    <?php echo nl2br(htmlspecialchars($featuredExhibition["title"],ENT_QUOTES,"UTF-8"));?>
                </h1>

                <p>
                    <?php echo date("F j, Y",strtotime($featuredExhibition["start_date"]));?>
                    -
                    <?php echo date("F j, Y",strtotime($featuredExhibition["end_date"]));?>
                </p>

                <a href="exhibition-details.php?id=<?php echo (int)$featuredExhibition["id"]; ?>" class="exhibition-details">
                    View Exhibition Details
                </a>

        </div>

            <div class="exhibition-image">

                <?php if (!empty($featuredExhibition["image"])): ?>

                <img src="<?php echo htmlspecialchars(edlImagePath($featuredExhibition["image"]),ENT_QUOTES,"UTF-8");?>"
                    alt="<?php echo htmlspecialchars($featuredExhibition["title"],ENT_QUOTES,"UTF-8");?>">

                <?php else: ?>

                    <div class="exhibition-no-image">
                        NO IMAGE
                    </div>

                <?php endif; ?>

            </div>

        </div>

    <?php else: ?>

    <div class="exhibition-layout">

        <div class="exhibition-info">

            <h1>
                No Upcoming<br>
                Exhibitions Yet
            </h1>

            <p>Please check back soon for our next exhibition.</p>

            <a href="exhibition.php" class="exhibition-details">View Exhibitions</a>

        </div>

    </div>

        <?php endif; ?>

</section>


<section class="rent" id="rent-space-id">

    <h2>RENT OUR SPACE</h2>

    <div class="rent-layout">

        <div class="rent-image">

            <img src="Images/background-3.jpg" alt="EDL Gallery exhibition space">

        </div>


        <div class="rent-info">

            <h1>
                Your Exhibition.<br>
                Our Gallery
            </h1>


            <p class="intro-justified">

                EDL Gallery offers a professional and
                inspiring space for artists to present their
                works and connect to a wider audience.

            </p>

            <a href="rent-space.php" class="learn-more">Learn More</a>

        </div>

    </div>

</section>


<section class="artist" id="art-id">

    <h2>FEATURED ARTISTS</h2>

        <div class="artist-layout">

            <?php if (!empty($featuredArtists)): ?>
                <?php foreach ($featuredArtists as $artist): ?>


    <article class="artist-info">

        <div class="artist-image">
            <?php if (!empty($artist["image"])): ?>

                <img src="<?php echo htmlspecialchars(edlImagePath($artist["image"]),ENT_QUOTES,"UTF-8");?>"
                    alt="<?php echo htmlspecialchars($artist["name"],ENT_QUOTES,"UTF-8");?>">

                <?php else: ?>

                    <div class="artist-no-image">NO IMAGE</div>

                <?php endif; ?>

        </div>


        <div class="artist-name">
            <h3><?php echo htmlspecialchars($artist["name"],ENT_QUOTES,"UTF-8");?></h3>
        </div>

    </article>


        <?php endforeach; ?>

            <?php else: ?>

        <div class="artist-no-data">

            <h3>No Featured Artists</h3>

                <p>
                    Artist information will appear here
                    once artists are assigned to a published exhibition.
                </p>

        </div>

            <?php endif; ?>


    </div>

</section>

    <section class="about" id="about-us-id">

        <div class="about-layout">

            <div class="about-image">
                <img src="Images/background-4.jpg" alt="EDL Gallery">
            </div>

            <div class="about-info">
                <h2>ABOUT US</h2>

                <h1>
                    A Space for Art.<br>
                    A Home for Artists.
                </h1>

                <a
                    href="about-us.php" class="learn-more">
                    Learn More
                </a>

            </div>

        </div>

    </section>


<section class="info" id="info-id">

    <h2>VISIT OUR GALLERY</h2>

        <div class="info-layout">

            <div class="location">
                <img src="Images/gps-logo.jpg" alt="GPS icon">

                <h3>LOCATION:</h3>
    
                <p>
                Banilad, Dumaguete City,<br>
                Philippines
                </p>
            </div>


            <div class="time">

                <img src="Images/clock-icon.jpg" alt="Clock icon">

                <h3>OPENING HOURS:</h3>

                <p>
                    Tuesday-Friday<br>
                    10:00 am - 8:00 pm
                </p>

            </div>



            <div class="email">

                <img src="Images/mail-icon.jpg" alt="Email icon">

                <h3>
                    EMAIL:
                </h3>

                <p>
                    info@edlgallery.com
                </p>

            </div>



            <div class="contact">

                <img src="Images/phone-icon.jpg" alt="Phone icon">

                <h3>
                    CONTACT US:
                </h3>

                <p>
                    +63 456 7890
                </p>

            </div>


        </div>

</section>



<?php require_once "includes/footer.php"; ?>


<script src="javascript.js"></script>


</body>

</html>
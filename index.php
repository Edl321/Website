<?php
    session_start();
    $basePath = "";
    require_once "database/config.php";

  $stmt = $pdo->prepare("
    SELECT id, name, specialization, biography, image
    FROM artists
    ORDER BY id ASC
    LIMIT 4
");

$stmt->execute();

$featuredArtists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
    SELECT *
    FROM exhibitions
    WHERE status = 'published'
    AND start_date >= CURDATE()
    ORDER BY start_date ASC
    LIMIT 1
");

$stmt->execute();

$upcomingExhibition = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>EDL Gallery</title>
        <link rel = "icon" type="image/x-icon" href = "Images/logo.png">
        <link rel = "stylesheet" type = "text/css" href = "style.css">
    </head>
    <body>
        <?php require_once "includes/header.php"; ?>

        <section class="home">
                <p class="t1">Art <span>Inspires.<br></span>
                            We <span>Showcase.</span>
                </p>
            <div class="intro">
                <p style="text-align: justify;">EDL Gallery is an contemporary art space
                that is dedicated in showcasing exceptional artworks, support artists,
                and creating a meaning  cultural experience.
                </p>
            </div>
        </section>


        <section class="upcoming" id="exhibition-id">

    <h2>UPCOMING EXHIBITION</h2>

    <?php if ($upcomingExhibition): ?>

        <div class="exhibition-layout">

            <div class="exhibition-info">

                <h1>
                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $upcomingExhibition["title"],
                            ENT_QUOTES,
                            "UTF-8"
                        )
                    );
                    ?>
                </h1>

                <p>
                    <?php
                    echo date(
                        "F j, Y",
                        strtotime($upcomingExhibition["start_date"])
                    );
                    ?>

                    <?php if (!empty($upcomingExhibition["end_date"])): ?>

                        –
                        
                        <?php
                        echo date(
                            "F j, Y",
                            strtotime($upcomingExhibition["end_date"])
                        );
                        ?>

                    <?php endif; ?>
                </p>

                <a
                    href="exhibition-details.php?id=<?php echo (int)$upcomingExhibition["id"]; ?>"
                    class="exhibition-details"
                >
                    View Exhibition Details
                </a>

            </div>

            <div class="exhibition-image">

    <?php
    $exhibitionImage = $upcomingExhibition["image"] ?? "";

    if (!empty($exhibitionImage)) {

        // If database already contains Images/filename.jpg
        if (
            str_starts_with($exhibitionImage, "Images/") ||
            str_starts_with($exhibitionImage, "images/")
        ) {
            $imagePath = $exhibitionImage;
        }

        // If database contains a full URL
        elseif (
            str_starts_with($exhibitionImage, "http://") ||
            str_starts_with($exhibitionImage, "https://")
        ) {
            $imagePath = $exhibitionImage;
        }

        // If database contains only filename.jpg
        else {
            $imagePath = "Images/" . basename($exhibitionImage);
        }

    } else {

        // Fallback image
        $imagePath = "Images/background-2.jpg";
    }
    ?>

    <img
        src="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, "UTF-8"); ?>"
        alt="<?php echo htmlspecialchars(
            $upcomingExhibition["title"],
            ENT_QUOTES,
            "UTF-8"
        ); ?>"
    >

</div>

        </div>

    <?php else: ?>

        <div class="exhibition-layout">

            <div class="exhibition-info">

                <h1>
                    No Upcoming<br>Exhibition
                </h1>

                <p>
                    Please check back soon for our next exhibition.
                </p>

                <a
                    href="exhibition.php"
                    class="exhibition-details"
                >
                    View Exhibitions
                </a>

            </div>

            <div class="exhibition-image">

                <img
                    src="Images/background-2.jpg"
                    alt="EDL Gallery"
                >

            </div>

        </div>

    <?php endif; ?>

</section>


        <section class="rent" id="rent-space-id">
            <h2>RENT OUR SPACE</h2>
        <div class="rent-layout">
            <div class="rent-image">
                <img src="Images/background-3.jpg" alt="image"></div>
            <div class="rent-info">
                <h1>Your Exhibition.<br>Our Gallery</h1>
                <p style="text-align: justify;">EDL Gallery offers a professional and
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

        <?php if ($featuredArtists): ?>

            <?php foreach ($featuredArtists as $artist): ?>

                <article class="artist-info">

                    <div class="artist-image">

                        <?php if (!empty($artist["image"])): ?>

                            <?php
                            $artistImage = $artist["image"];

                            if (
                                str_starts_with($artistImage, "Images/") ||
                                str_starts_with($artistImage, "images/")
                            ) {
                                $artistImagePath = $artistImage;
                            } else {
                                $artistImagePath = "Images/" . basename($artistImage);
                            }
                            ?>

                            <img
                                src="<?php echo htmlspecialchars(
                                    $artistImagePath,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                alt="<?php echo htmlspecialchars(
                                    $artist["name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                            >

                        <?php else: ?>

                            <div class="artist-no-image">
                                NO IMAGE
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="artist-name">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $artist["name"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>
                        </h3>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                $artist["specialization"] ?? "Artist",
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>
                        </p>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="no-artists-message">
                <p>No artists available at the moment.</p>
            </div>

        <?php endif; ?>

    </div>

</section>


        <section class="about" id="about-us-id">
            <div class="about-layout">
                <div class="about-image">
                <img src="Images/background-4.jpg" alt="image"></div>
            <div class="about-info">
                <h2>ABOUT US</h2>
                <h1>A Space for Art.<br>A Home for Artists.</h1>
                <a href="about-us.php" class="learn-more">Learn More</a>
            </div>
        </div>
        </section>

        
        <section class="info" id="info-id">
            <h2>VISIT OUR GALLERY</h2>
            <div class="info-layout">
            
            <div class="location">
                <img src="Images/gps-logo.jpg" alt="gps-icon">
                <h3>LOCATION:</h3>
                <p>Banilad, Dumaguete City,<br> Philippines</p>
            </div>

            <div class="time">
                <img src="Images/clock-icon.jpg" alt="clock-icon">
                <h3>OPENING HOURS:</h3>
                <p>Tuesday-Friday<br>10:00 am - 8:00 pm</p>
            </div>

            <div class="email">
                <img src="Images/mail-icon.jpg" alt="email-icon">
                <h3>EMAIL:</h3>
                <p>info@edlgallery.com</p>
            </div>

            <div class="contact">
                <img src="Images/phone-icon.jpg" alt="phone-icon">
                <h3>CONTACT US:</h3>
                <p>+63 456 7890</p>
            </div>
        </div>
        </section>

        <?php require_once "includes/footer.php"; ?>

        <script src="javascript.js"></script>
            </body>
                </html>
    



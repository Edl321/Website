<?php
    session_start();
    $basePath ="";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">
    <title>Visit Us | EDL Gallery</title>
        <link rel = "icon" type="image/x-icon" href = "Images/logo.png">
        <link rel="stylesheet" href="style.css">

</head>


<body>

    <?php require_once "includes/header.php"; ?>

    <section class="visit-hero">


        <div class="visit-hero-text">

            <h1>VISIT EDL GALLERY</h1>

            <h2>
                Experience Art.<br>
                <span>Discover Expression.</span>
            </h2>

            <p>
                Step into a space where creativity,
                culture, and artistic expression come together.
            </p>

            

        </div>



        <div class="visit-hero-image">

            <img src="Images/rent-1.jpg"
                alt="Inside EDL Gallery">

        </div>


    </section>

    <section class="plan-visit"
            id="plan-visit">


        <div class="section-heading">

            <h4>PLAN YOUR VISIT</h4>

            <h2>
                Everything You Need<br>
                <span>to Know.</span>
            </h2>

        </div>



        <div class="visit-info-container">


            <!-- LOCATION -->

            <div class="visit-info-card">

                <div class="visit-icon">

                    <img src="Images/gps-logo.jpg"
                        alt="Location">

                </div>

                <h3>LOCATION</h3>

                <p>
                    Dumaguete City,<br>
                    Philippines
                </p>

            </div>



            <!-- HOURS -->

            <div class="visit-info-card">

                <div class="visit-icon">

                    <img src="Images/clock-icon.jpg"
                        alt="Opening Hours">

                </div>

                <h3>OPENING HOURS</h3>

                <p>
                    Tuesday – Sunday<br>
                    10:00 AM – 6:00 PM
                </p>

                <small>
                    Closed on Mondays
                </small>

            </div>



            <!-- CONTACT -->

            <div class="visit-info-card">

                <div class="visit-icon">

                    <img src="Images/phone-icon.jpg"
                        alt="Phone">

                </div>

                <h3>CONTACT</h3>

                <p>
                    +63 912 345 6789<br>
                    info@edlgallery.com
                </p>

            </div>


        </div>

    </section>

    <section class="gallery-visit">


        <div class="gallery-visit-text">

            <h4>OUR GALLERY</h4>

            <h2>
                A Space for<br>
                <span>Art & Expression.</span>
            </h2>

            <p>
                Explore our exhibitions and discover
                artworks created by talented artists.
                Every visit offers a new experience.
            </p>

        </div>



        <div class="gallery-visit-image">

            <img src="Images/gallery-outside.png"
                alt="EDL Gallery">

        </div>


    </section>

    <section class="find-us">


        <div class="find-us-heading">

            <h4>FIND US</h4>

            <h2>
                Come Visit<br>
                <span>EDL Gallery.</span>
            </h2>

            <p>
                We welcome art lovers, collectors,
                artists, and visitors to experience
                our gallery.
            </p>

        </div>



        <div class="map-area">

            <!--
                Temporary map image.

                Later you can replace this with
                Google Maps or another map API.
            -->

            <img src="Images/gallery-map.png"
                alt="EDL Gallery Map">

        </div>

    </section>


    <section class="visit-cta">


        <h2>
            We Look Forward<br>
            <span>to Seeing You.</span>
        </h2>


        <p>
            Whether you're visiting an exhibition,
            discovering a new artist, or simply
            enjoying the space, you're always welcome
            at EDL Gallery.
        </p>


        <div class="cta-buttons">

            <a href="contact.php "
                class="cta-contact">

                CONTACT US

            </a>


            <a href="exhibition.php"
                class="cta-exhibition">
                VIEW EXHIBITIONS
            </a>
        </div>
    </section>

    <?php require_once "includes/footer.php"; ?>

</body>

</html>
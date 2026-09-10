<?php
    session_start();
    $basePath = "";
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
        <div class="exhibition-layout">
            <div class="exhibition-info">
                <h1>Fragments of <br>Expression</h1>
                <p>August 26, 2026</p>
                <a href="exhibition.php" class="exhibition-details">View Exhibition Details</a>
            </div>
                <div class="exhibition-image">
                <img src="Images/background-2.jpg" alt="Fragments of Expression exhibition">
                </div>
        </div>
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

        <article class="artist-info">

            <div class="artist-image">
                
            <img src="Images/1.jpg"></div> 
            <div class="artist-name">   
            <h3>Erelah Zayn Sayson</h3>
            <p>Graphic Artist</p>
            </div>
        </article>

        <article class="artist-info">
            <div class="artist-image">
            <img src="Images/juan.jpg"></div>
            <div class="artist-name">
            <h3>Juan Dela Cruz</h3>
            <p>Painter</p>
            </div>
        </article>

        <article class="artist-info">
            <div class="artist-image">
                <img src="Images/andrea.jpg"></div>
            <div class="artist-name">
            <h3>Andrea Reyes</h3>
            <p>Contemporary Artist</p>
            </div>
        </article>

        <article class="artist-info">
            <div class="artist-image">
                <img src="Images/carlo.jpg"></div>
            <div class="artist-name">
            <h3>Carlo Mendoza</h3>
            <p>Sculptor</p>
            </div>
        </article>
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
    



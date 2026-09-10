<?php
    session_start();
    $basePath ="";    
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
<main>

    <section class="about-hero">

        <div class="about-hero-text">

            <h1>ABOUT US</h1>

            <h2>
                A Space for Art.<br>
                <span>A Home for Artists.</span>
            </h2>

            <p>
                Discover the story, vision, and people
                behind EDL Gallery.
            </p>

        </div>


        <div class="about-hero-image">

            <img src="Images/about.png" alt="EDL Gallery">

        </div>

    </section>




    <section class="about-story">

        <div class="story-image">

            <img src="Images/rent-6.jpg"
                alt="EDL Gallery Exhibition">

        </div>


        <div class="story-text">

            <h4>OUR STORY</h4>

            <h2>
                Where Art<br>
                <span>Comes to Life</span>
            </h2>

            <p>
                EDL Gallery is a space dedicated to
                showcasing artistic expression and
                connecting artists with the community.
            </p>

            <p>
                We provide a welcoming environment where
                artists can share their work, audiences can
                experience new perspectives, and creativity
                can flourish.
            </p>

        </div>

    </section>

    <section class="belief-section">

        <div class="belief-heading">

            <h4>WHAT WE BELIEVE</h4>

            <h2>
                Art Has the Power<br>
                <span>to Connect Us.</span>
            </h2>

        </div>


        <div class="belief-container">


            <div class="belief-card">

                <span>01</span>

                <h3>ART</h3>

                <p>
                    Art gives people a way to express
                    ideas, emotions, and experiences.
                </p>

            </div>


            <div class="belief-card">

                <span>02</span>

                <h3>COMMUNITY</h3>

                <p>
                    We believe galleries can bring
                    people together through creativity.
                </p>

            </div>


            <div class="belief-card">

                <span>03</span>

                <h3>CREATIVITY</h3>

                <p>
                    We encourage artists to explore
                    new ideas and creative possibilities.
                </p>

            </div>

        </div>

    </section>

    <section class="mission-section">

        <div class="mission-image">

            <img src="Images/mission.png"
                alt="Artwork at EDL Gallery">

        </div>


        <div class="mission-text">

            <h4>OUR MISSION</h4>

            <h2>
                Creating Space<br>
                for <span>Expression.</span>
            </h2>

            <p>
                Our mission is to create an accessible
                and inspiring space where artists can
                showcase their work and audiences can
                discover meaningful artistic experiences.
            </p>

        </div>

    </section>



    <section class="vision-section">

        <div class="vision-text">

            <h4>OUR VISION</h4>

            <h2>
                Inspiring People<br>
                Through <span>Art.</span>
            </h2>

            <p>
                We envision EDL Gallery as a place where
                artists, audiences, and communities come
                together to appreciate creativity and
                discover new perspectives.
            </p>

        </div>


        <div class="vision-image">

            <img src="Images/vision.png"
                alt="Artwork inside EDL Gallery">

        </div>

    </section>


    <section class="about-cta">

        <h2>
            Come Experience<br>
            <span>EDL Gallery.</span>
        </h2>

        <p>
            Step into a space where art,
            creativity, and community meet.
        </p>

        <a href="visit-us.php" class="about-cta-button">
            VISIT US
        </a>

    </section>
    <?php require_once "includes/footer.php"; ?>
</main>
    </body>
    </html>
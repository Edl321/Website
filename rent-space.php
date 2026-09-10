<?php

session_start();
$basePath = "";

require_once "database/config.php";
require_once "security/authorize.php";

// We only need to know IF someone is logged in here (not force login),
// because this page is public. isLoggedIn() just checks the session.
$loggedIn = isLoggedIn();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rent Our Space | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="image/logo.png">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php require_once "includes/header.php"; ?>


<section class="rent-hero">

    <div class="rent-hero-overlay"></div>

    <div class="rent-hero-content">

        <h1>RENT OUR SPACE</h1>

        <h2>
            Your Exhibition.<br>
            <span>Our Gallery.</span>
        </h2>

        <p>
            EDL Gallery offers exhibition space for artists and
            organizations. Every request is reviewed by our team
            before the exhibition is set up and published.
        </p>

        <div class="rent-hero-buttons">

            <?php if ($loggedIn): ?>

                <a href="submit-inquiry.php" class="rent-button">
                    START AN EXHIBITION INQUIRY
                </a>

            <?php else: ?>

                <a href="login.php" class="rent-button">
                    LOGIN
                </a>

                <a href="register.php" class="outline-button">
                    REGISTER
                </a>

            <?php endif; ?>

        </div>

    </div>

</section>


<section class="space-section">

    <div class="space-image">
        <img src="Images/rent-2.jpg" alt="EDL Gallery Interior">
    </div>

    <div class="space-content">

        <h4>OUR SPACE</h4>

        <h2>
            A Place for<br>
            <span>Your Exhibition</span>
        </h2>

        <p>
            EDL Gallery provides a professional and welcoming
            environment for artists and organizations who want
            to showcase their work to a wider audience.
        </p>

        <p>
            Once your inquiry is approved, you will be able to
            complete your exhibition details and submit your
            artists and artworks for review.
        </p>

    </div>

</section>


<section class="host-section">

    <div class="section-heading">

        <h4>WHAT YOU CAN HOST</h4>

        <h2>Exhibitions & Creative Events</h2>

        <p>
            Our gallery is designed to showcase different kinds
            of artistic work.
        </p>

    </div>


    <div class="host-container">

        <div class="host-card">
            <div class="host-number">01</div>
            <h3>Solo Exhibitions</h3>
            <p>
                Showcase a single artist's body of work in a
                dedicated gallery setting.
            </p>
        </div>

        <div class="host-card">
            <div class="host-number">02</div>
            <h3>Group Exhibitions</h3>
            <p>
                Bring together multiple artists around a shared
                theme or collection.
            </p>
        </div>

        <div class="host-card">
            <div class="host-number">03</div>
            <h3>Community & Organization Exhibitions</h3>
            <p>
                Perfect for organizations or groups presenting
                a collective creative project.
            </p>
        </div>

    </div>

</section>


<!-- =========================================
     HOW IT WORKS
========================================= -->

<section class="process-section">

    <div class="section-heading">

        <h4>OUR PROCESS</h4>

        <h2>How It Works</h2>

    </div>


    <div class="process-container">

        <div class="process-item">
            <span>01</span>
            <h3>Submit an Inquiry</h3>
            <p>
                Log in and tell us about your proposed exhibition.
            </p>
        </div>

        <div class="process-item">
            <span>02</span>
            <h3>Admin Review</h3>
            <p>
                Our team reviews your inquiry and responds with
                a decision.
            </p>
        </div>

        <div class="process-item">
            <span>03</span>
            <h3>Set Up Your Exhibition</h3>
            <p>
                Once approved, complete your exhibition details
                and submit your artworks.
            </p>
        </div>

        <div class="process-item">
            <span>04</span>
            <h3>Get Published</h3>
            <p>
                After artworks are reviewed, your exhibition goes
                live on the gallery site.
            </p>
        </div>

    </div>

</section>


<!-- =========================================
     GALLERY PREVIEW
========================================= -->

<section class="preview-section">

    <div class="section-heading">

        <h4>THE GALLERY</h4>

        <h2>Preview Our Space</h2>

    </div>


    <div class="preview-grid">

        <div class="preview-image large">
            <img src="Images/rent-1.jpg" alt="Gallery interior">
        </div>

        <div class="preview-image">
            <img src="Images/rent-6.jpg" alt="Gallery exhibition space">
        </div>

        <div class="preview-image">
            <img src="Images/rent-2.jpg" alt="Gallery event space">
        </div>

        <div class="preview-image large">
            <img src="Images/background-4.jpg" alt="EDL Gallery interior">
        </div>

    </div>

</section>


<!-- =========================================
     BEFORE YOU APPLY
========================================= -->

<section class="guideline-section">

    <div class="guideline-content">

        <h4>BEFORE YOU BOOK</h4>

        <h2>What to Have Ready</h2>

    </div>


    <div class="guideline-list">

        <p>✓ Your organization or artist name and phone number</p>
        <p>✓ Your proposed exhibition title and description</p>
        <p>✓ Proposed start and end dates</p>
        <p>✓ Estimated number of artists and artworks</p>
        <p>✓ Any special requirements for your exhibition</p>

    </div>

</section>


<section class="rent-cta">

    <h2>
        Ready to Bring Your<br>
        <span>Exhibition to Life?</span>
    </h2>

    <p>
        Log in to start an exhibition inquiry, or create an
        account if you're new to EDL Gallery.
    </p>

    <div class="rent-cta-buttons">

        <?php if ($loggedIn): ?>

            <a href="submit-inquiry.php" class="cta-button">
                START AN EXHIBITION INQUIRY
            </a>

        <?php else: ?>

            <a href="login.php" class="cta-button">
                LOGIN
            </a>

            <a href="register.php" class="cta-button-outline">
                REGISTER
            </a>

        <?php endif; ?>

    </div>

</section>


<?php require_once "includes/footer.php"; ?>

</body>
</html>

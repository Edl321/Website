<?php

session_start();

$basePath = "";

require_once "database/config.php";
require_once "security/authorize.php";


// =========================================================
// GALLERY STATS (for the "About the space" section)
// =========================================================

$exhibitionCountStmt = $pdo->query("
    SELECT COUNT(*)
    FROM exhibitions
    WHERE status IN ('published', 'completed')
");

$exhibitionCount = (int)$exhibitionCountStmt->fetchColumn();


$artistCountStmt = $pdo->query("SELECT COUNT(*) FROM artists");
$artistCount = (int)$artistCountStmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rent Our Space | EDL Gallery</title>

    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" href="style.css">

</head>

<body>

<?php require_once "includes/header.php"; ?>


<main class="rent-space-page">

    <!-- =========================================
         HERO
    ========================================== -->

    <section class="rent-space-header">

        <p>EDL GALLERY / RENT OUR SPACE</p>

        <h1>Rent Our <span>Space</span></h1>

        <p>
            A dedicated art gallery for artists and groups
            to present creative work through meaningful
            exhibitions and experiences.
        </p>

    </section>


    <!-- =========================================
         THE SPACE
    ========================================== -->

    <section class="space-section">

        <div class="space-image">

            <img
                src="Images/background-3.jpg"
                alt="EDL Gallery exhibition space"
            >

        </div>

        <div class="space-content">

            <h4>THE SPACE</h4>

            <h2>
                Purpose-Built<br>
                for <span>Art.</span>
            </h2>

            <p>
                EDL Gallery provides a professional, intimate
                space designed specifically for art exhibitions,
                art and craft shows, and curated collections.
            </p>

            <p>
                With flexible lighting, ample wall space for
                hanging artwork, and a layout suited to small
                and medium-scale shows, the gallery is ideal
                for solo and group exhibitions.
            </p>

        </div>

    </section>


    <!-- =========================================
         ABOUT THE GALLERY
    ========================================== -->

    <section class="host-section">

        <div class="section-heading">

            <h4>ABOUT EDL GALLERY</h4>

            <h2>
                A Home for Artists<br>
                <span>&amp; Their Work.</span>
            </h2>

            <p>
                Since opening, EDL Gallery has hosted exhibitions
                from local and visiting artists. The gallery is
                committed to giving artists a well-presented
                space and a supportive audience.
            </p>

        </div>

        <div class="host-container">

            <div class="host-card">

                <div class="host-number">01</div>

                <h3>Artist-First</h3>

                <p>
                    Every decision — from lighting to hanging —
                    is made to present each artist's work at
                    its best.
                </p>

            </div>

            <div class="host-card">

                <div class="host-number">02</div>

                <h3>Central Location</h3>

                <p>
                    Located in Dumaguete City, easily reached by
                    visitors, collectors, and the local arts
                    community.
                </p>

            </div>

            <div class="host-card">

                <div class="host-number">03</div>

                <h3>Flexible Schedule</h3>

                <p>
                    Showings are available throughout the year,
                    with day and evening opening times to suit
                    your exhibition.
                </p>

            </div>

        </div>

    </section>


    <!-- =========================================
         WHAT'S INCLUDED
    ========================================== -->

    <section class="package-section">

        <div class="section-heading dark-heading">

            <h4>WHAT'S INCLUDED</h4>

            <h2>
                Everything You Need<br>
                <span>to Exhibit.</span>
            </h2>

            <p>
                Every rental includes the essentials for a
                successful exhibition — so you can focus on
                the work, not the logistics.
            </p>

        </div>

        <div class="package-container">

            <div class="package-card">

                <h3>The Space</h3>

                <div class="package-line"></div>

                <p class="package-description">
                    Full use of the gallery floor for the duration
                    of your exhibition.
                </p>

                <ul>
                    <li>Adjustable spot and ambient lighting</li>
                    <li>Wall-hanging hardware provided</li>
                    <li>Pedestals for 3D work (on request)</li>
                    <li>Climate-controlled interior</li>
                </ul>

            </div>


            <div class="package-card featured-package">

                <span class="popular">MOST POPULAR</span>

                <h3>Full Support</h3>

                <div class="package-line"></div>

                <p class="package-description">
                    The space plus marketing and on-site support
                    to make your show a success.
                </p>

                <ul>
                    <li>Everything in The Space package</li>
                    <li>Gallery-hosted opening reception</li>
                    <li>Promotion on EDL Gallery channels</li>
                    <li>Photography of the exhibition</li>
                    <li>On-site coordinator for the opening</li>
                </ul>

            </div>


            <div class="package-card">

                <h3>Custom</h3>

                <div class="package-line"></div>

                <p class="package-description">
                    Planning something larger or unusual?
                    Let's talk about what fits your show.
                </p>

                <ul>
                    <li>Extended durations</li>
                    <li>Group and collective pricing</li>
                    <li>Multi-room setups</li>
                    <li>Workshops and artist talks</li>
                </ul>

            </div>

        </div>

    </section>


    <!-- =========================================
         PROCESS
    ========================================== -->

    <section class="process-section">

        <div class="section-heading">

            <h4>HOW IT WORKS</h4>

            <h2>
                From Inquiry<br>
                <span>to Exhibition.</span>
            </h2>

        </div>

        <div class="process-container">

            <div class="process-item">

                <span>01</span>

                <h3>Submit Inquiry</h3>

                <p>
                    Fill in a short form describing your
                    exhibition, proposed dates, and artists.
                </p>

            </div>

            <div class="process-item">

                <span>02</span>

                <h3>Gallery Review</h3>

                <p>
                    EDL Gallery reviews your request and checks
                    space availability for your dates.
                </p>

            </div>

            <div class="process-item">

                <span>03</span>

                <h3>Complete Details</h3>

                <p>
                    Once approved, add your artists and artworks
                    through your gallery dashboard.
                </p>

            </div>

            <div class="process-item">

                <span>04</span>

                <h3>Exhibition Opens</h3>

                <p>
                    Your show goes live on the gallery calendar
                    and opens to visitors.
                </p>

            </div>

        </div>

    </section>


    <!-- =========================================
         GUIDELINES
    ========================================== -->

    <section class="guideline-section">

        <div class="guideline-content">

            <h4>GOOD TO KNOW</h4>

            <h2>
                Exhibition<br>
                Guidelines.
            </h2>

        </div>

        <div class="guideline-list">

            <p>Open to solo artists, groups, and collectives.</p>
            <p>Exhibitions are typically 1 to 4 weeks in length.</p>
            <p>Submissions should be original work by the artist(s).</p>
            <p>Artworks must be approved by the gallery before the opening.</p>
            <p>Exhibiting artists must provide details for every piece shown.</p>
            <p>Installation and de-installation happen on the scheduled days.</p>

        </div>

    </section>


    <!-- =========================================
         CTA
    ========================================== -->

    <section class="rent-cta">

        <h2>
            Ready to Exhibit<br>
            <span>at EDL Gallery?</span>
        </h2>

        <p>
            Start by submitting an exhibition inquiry.
            It only takes a few minutes and gives the gallery
            everything it needs to review your proposal.
        </p>

        <div class="rent-cta-buttons">

            <?php if (isUser()): ?>

                <a
                    href="submit-inquiry.php"
                    class="cta-button"
                >
                    SUBMIT AN INQUIRY
                </a>

                <a
                    href="my-inquiries.php"
                    class="cta-button-outline"
                >
                    MY INQUIRIES
                </a>

            <?php else: ?>

                <a
                    href="login.php"
                    class="cta-button"
                >
                    LOG IN TO SUBMIT
                </a>

                <a
                    href="register.php"
                    class="cta-button-outline"
                >
                    CREATE AN ACCOUNT
                </a>

            <?php endif; ?>

        </div>

    </section>


</main>


<?php require_once "includes/footer.php"; ?>

</body>

</html>
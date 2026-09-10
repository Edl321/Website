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

        <!-- RENT HERO -->
<section class="rent-hero">

    <div class="rent-hero-overlay"></div>

    <div class="rent-hero-content">

        <h1>RENT OUR SPACE</h1>

        <h2>
            Host Your <span>Event</span> With Us
        </h2>

        <p>
            Discover the perfect venue for exhibitions, private events,
            creative gatherings, and special occasions.
        </p>

        <a href="#inquiry" class="rent-button">
            INQUIRE NOW
        </a>

    </div>

</section>


<!-- OUR SPACE -->
<section class="space-section">

    <div class="space-image">
        <img src="Images/background-3.jpg" alt="EDL Gallery Event Space">
    </div>

    <div class="space-content">

        <h4>OUR SPACE</h4>

        <h2>
            A Space Designed for
            <span>Creativity</span>
        </h2>

        <p>
            EDL Gallery offers a carefully designed space for exhibitions,
            private events, art gatherings, and creative experiences.
        </p>

        <p>
            Our gallery provides an elegant and welcoming environment
            where art and people can come together.
        </p>

        <a href="#inquiry" class="outline-button">
            LEARN MORE
        </a>

    </div>

</section>


<!-- WHAT YOU CAN HOST -->
<section class="host-section">

    <div class="section-heading">

        <h4>WHAT YOU CAN HOST</h4>

        <h2>More Than Just an Art Space</h2>

        <p>
            Our gallery can accommodate different types of creative
            and private events.
        </p>

    </div>


    <div class="host-container">

        <div class="host-card">

            <div class="host-number">01</div>

            <h3>Art Exhibitions</h3>

            <p>
                Showcase your artwork in a professional gallery
                environment designed to highlight your work.
            </p>

        </div>


        <div class="host-card">

            <div class="host-number">02</div>

            <h3>Private Events</h3>

            <p>
                Host intimate celebrations, gatherings, and special
                occasions in a unique artistic setting.
            </p>

        </div>


        <div class="host-card">

            <div class="host-number">03</div>

            <h3>Creative Events</h3>

            <p>
                Perfect for workshops, talks, launches, and other
                creative activities.
            </p>

        </div>

    </div>

</section>


<!-- GALLERY PREVIEW -->
<section class="preview-section">

    <div class="section-heading">

        <h4>THE GALLERY</h4>

        <h2>Preview Our Space</h2>

        <p>
            Take a look at the environment available for your next event.
        </p>

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
            <img src="Images/background-3.jpg" alt="EDL Gallery interior">
        </div>

    </div>

</section>


<!-- INQUIRY -->
<section class="inquiry-section" id="inquiry">

    <div class="section-heading">

        <h4>GET IN TOUCH</h4>

        <h2>Plan Your Event With Us</h2>

        <p>
            Tell us about your event and our team will get back to you.
        </p>

    </div>


    <div class="inquiry-form">

        <div class="form-row">

            <div class="form-group">

                <label>FIRST NAME</label>

                <input type="text" placeholder="First name">

            </div>


            <div class="form-group">

                <label>LAST NAME</label>

                <input type="text" placeholder="Last name">

            </div>

        </div>


        <div class="form-group">

            <label>EMAIL</label>

            <input type="email" placeholder="Email address">

        </div>


        <div class="form-group">

            <label>EVENT TYPE</label>

            <select>

                <option>Select event type</option>
                <option>Art Exhibition</option>
                <option>Private Event</option>
                <option>Creative Event</option>
                <option>Workshop</option>
                <option>Other</option>

            </select>

        </div>


        <div class="form-group">

            <label>MESSAGE</label>

            <textarea
                rows="6"
                placeholder="Tell us about your event..."
            ></textarea>

        </div>


        <button type="submit" class="submit-button">
            SEND INQUIRY
        </button>

    </div>

</section>

    <?php require_once "includes/footer.php"; ?>

    </body>
</html>
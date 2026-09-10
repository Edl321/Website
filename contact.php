<?php

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

    <section class="contact-hero">

        <div class="contact-hero-text">

            <h1>CONTACT US</h1>

            <h2>
                Let's Connect.<br>
                <span>We'd Love to Hear From You.</span>
            </h2>

            <p>
                Have a question, inquiry, or exhibition idea?
                We'd love to hear from you.
            </p>

        </div>


        <div class="contact-hero-image">

            <img src="Images/contact.png" alt="EDL Gallery">
        </div>

    </section>

    <section class="contact-info">

        <div class="contact-heading">

            <h4>GET IN TOUCH</h4>

            <h2>
                We'd Be Happy<br>
                <span>to Hear From You.</span>
            </h2>

        </div>


        <div class="contact-container">


            <!-- LOCATION -->

            <div class="contact-card">

                <div class="contact-icon">
                    <img src="Images/gps-logo.jpg" alt="Location">
                </div>

                <h3>LOCATION</h3>

                <p>
                    Dumaguete City,<br>
                    Philippines
                </p>

            </div>

            <div class="contact-card">

                <div class="contact-icon">
                    <img src="Images/phone-icon.jpg" alt="Phone">
                </div>

                <h3>PHONE</h3>

                <p>
                    +63 912 345 6789
                </p>

            </div>


            <!-- EMAIL -->

            <div class="contact-card">

                <div class="contact-icon">
                    <img src="Images/mail-icon.jpg" alt="Email">
                </div>

                <h3>EMAIL</h3>

                <p>
                    info@edlgallery.com
                </p>

            </div>

        </div>

    </section>


    <section class="message-section">

        <div class="message-heading">

            <h4>SEND US A MESSAGE</h4>

            <h2>
                Let's Start a<br>
                <span>Conversation.</span>
            </h2>

            <p>
                Fill out the form below and we'll
                get back to you as soon as possible.
            </p>

        </div>


        <form class="contact-form">

            <div class="form-row">

                <div class="form-group">

                    <label for="name">
                        Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Your Name"
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Your Email"
                    >

                </div>

            </div>


            <div class="form-group">

                <label for="subject">
                    Subject
                </label>

                <input
                    type="text"
                    id="subject"
                    name="subject"
                    placeholder="Subject"
                >

            </div>


            <div class="form-group">

                <label for="message">
                    Message
                </label>

                <textarea
                    id="message"
                    name="message"
                    rows="7"
                    placeholder="Write your message..."
                ></textarea>

            </div>


            <button
                type="submit"
                class="send-button">
                SEND MESSAGE
            </button>

        </form>

    </section>

    <section class="find-section">

        <div class="find-text">

            <h4>FIND US</h4>

            <h2>
                Visit <span>EDL Gallery.</span>
            </h2>

            <p>
                Come visit our gallery and experience
                art, creativity, and expression in person.
            </p>

            <p>
                <strong>Location:</strong><br>
                Dumaguete City, Philippines
            </p>

        </div>


        <div class="map-container">

            <!-- Temporary map placeholder -->
            <img
                src="Images/gallery-map.png"
                alt="EDL Gallery Location">

        </div>

    </section>

    <section class="contact-cta">

        <h2>
            Have an Exhibition<br>
            <span>in Mind?</span>
        </h2>

        <p>
            Talk to us about exhibiting your work
            or renting our gallery space.
        </p>

        <a href="rent.html" class="contact-cta-button">
            RENT OUR SPACE
        </a>

    </section>

    <?php require_once "includes/footer.php"; ?>

        </body>
            </main>
                </html>
    
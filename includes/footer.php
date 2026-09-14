<footer class="footer" id="foot-id">

    <a href="<?php echo $basePath; ?>index.php" class="logo-1">
        <img src="<?php echo $basePath; ?>Images/logo-1.png" alt="EDL Gallery Logo">
    </a>

    <nav class="foot-link">

        <div class="foot-column">

            <h3>EXPLORE</h3>

            <a href="<?php echo $basePath; ?>index.php">Home</a>
            <a href="<?php echo $basePath; ?>exhibition.php">Exhibitions</a>
            <a href="<?php echo $basePath; ?>artist.php">Artist</a>
            <a href="<?php echo $basePath; ?>about-us.php">About Us</a>

        </div>


        <div class="foot-column">

            <h3>VISIT US</h3>

            <a href="<?php echo $basePath; ?>visit-us.php">Visit Our Gallery</a>
            <a href="<?php echo $basePath; ?>rent-space.php">Visit Our Space</a>
            <a href="<?php echo $basePath; ?>contact.php">Contact Us</a>

        </div>


        <div class="foot-column contact-column">

            <h3>CONTACT</h3>

            <div class="social-item">
                <img src="<?php echo $basePath; ?>Images/insta-icon.png" alt="Instagram">
                <a href="#">
                    <span>Instagram</span>
                </a>
            </div>

            <div class="social-item">
                <img src="<?php echo $basePath; ?>Images/facebook-icon.png" alt="Facebook">
                <a href="#">
                    <span>Facebook</span>
                </a>
            </div>

        </div>

        <div class="foot-column">

            <h3>ACCOUNT</h3>

            <?php if (isLoggedIn()): ?>

                <a href="<?php echo $basePath; ?>dashboard.php">Dashboard</a>
                <a href="<?php echo $basePath; ?>logout.php">Logout</a>

            <?php else: ?>

                <a href="<?php echo $basePath; ?>login.php">Login</a>
                <a href="<?php echo $basePath; ?>register.php">Create Account</a>

            <?php endif; ?>

        </div>

    </nav>


    <hr class="footer-line">

    <p class="copyright">
        ©2026 EDL Gallery. All Rights Reserved.
    </p>

</footer>
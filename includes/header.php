<?php

require_once __DIR__ . "/../security/authorize.php";

$currentPage = basename($_SERVER["PHP_SELF"]);

?>

<header class="main-nav">

    <a href="<?php echo $basePath; ?>index.php" class="logo">
        <img src="<?php echo $basePath; ?>Images/logo.png" alt="EDL Gallery Logo" width="150px" height="100px">
    </a>

    <nav class="nav-links">

        <a href="<?php echo $basePath; ?>index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
            <p>HOME</p>
        </a>

        <a href="<?php echo $basePath; ?>exhibition.php" class="<?php echo ($currentPage == 'exhibition.php') ? 'active' : ''; ?>">
            <p>EXHIBITION</p>
        </a>

        <a href="<?php echo $basePath; ?>artist.php" class="<?php echo ($currentPage == 'artist.php') ? 'active' : ''; ?>">
            <p>ARTISTS</p>
        </a>

        <a href="<?php echo $basePath; ?>rent-space.php" class="<?php echo ($currentPage == 'rent-space.php') ? 'active' : ''; ?>">
            <p>RENT OUR SPACE</p>
        </a>

        <a href="<?php echo $basePath; ?>about-us.php" class="<?php echo ($currentPage == 'about-us.php') ? 'active' : ''; ?>">
            <p>ABOUT US</p>
        </a>

        <a href="<?php echo $basePath; ?>contact.php" class="<?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>">
            <p>CONTACT</p>
        </a>

    </nav>

    <a href="<?php echo $basePath; ?>visit-us.php" class="visit-us">
        VISIT US
    </a>

</header>
<?php

session_start();

require_once "database/config.php";
require_once "security/authorize.php";


// Make sure the user is logged in
if (!isLoggedIn()) {

    header("Location: login.php");
    exit;

}


// Get number of exhibitions
$exhibitionStmt = $pdo->query(
    "SELECT COUNT(*) FROM exhibitions"
);

$exhibitionCount = $exhibitionStmt->fetchColumn();


// Get number of artists
$artistStmt = $pdo->query(
    "SELECT COUNT(*) FROM artists"
);

$artistCount = $artistStmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard | EDL Gallery</title>

    <link rel="stylesheet" href="style.css">

</head>


<body>


<?php require_once "includes/header.php"; ?>


<main class="dashboard">


    <!-- DASHBOARD HEADER -->

    <section class="dashboard-header">

        <div>

            <p class="dashboard-label">
                EDL GALLERY
            </p>

            <h1>
                Dashboard
            </h1>

            <p class="dashboard-welcome">

                Welcome back,
                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["first_name"]
                    );
                    ?>
                </strong>!

            </p>

        </div>

    </section>



    <!-- STATISTICS -->

    <section class="dashboard-stats">


        <div class="dashboard-card">

            <div class="dashboard-card-icon">

            <div>

                <p>
                    EXHIBITIONS
                </p>

                <h2>
                    <?php echo $exhibitionCount; ?>
                </h2>
            </div>
            </div>

        </div>



        <div class="dashboard-card">

            <div class="dashboard-card-icon">

            <div>

                <p>
                    ARTISTS
                </p>

                <h2>
                    <?php echo $artistCount; ?>
                </h2>

            </div>
            </div>
        </div>



        <div class="dashboard-card">

            <div class="dashboard-card-icon">

            <div>

                <p>
                    ACCOUNT
                </p>

                <h2>
                    ACTIVE
                </h2>

            </div>
            </div>
        </div>


    </section>



    <!-- QUICK ACTIONS -->

    <section class="dashboard-section">

        <div class="dashboard-section-title">

            <p>
                MANAGEMENT
            </p>

            <h2>
                Quick Actions
            </h2>

        </div>


        <div class="dashboard-actions">


            <a
                href="exhibition.php"
                class="dashboard-action"
            >

                <span>
                    01
                </span>

                <div>

                    <h3>
                        Exhibitions
                    </h3>

                    <p>
                        View and manage gallery exhibitions.
                    </p>

                </div>

            </a>



            <a
                href="artist.php"
                class="dashboard-action"
            >

                <span>
                    02
                </span>

                <div>

                    <h3>
                        Artists
                    </h3>

                    <p>
                        View and manage featured artists.
                    </p>

                </div>

            </a>



            <a
                href="index.php"
                class="dashboard-action"
            >

                <span>
                    03
                </span>

                <div>

                    <h3>
                        View Website
                    </h3>

                    <p>
                        Return to the EDL Gallery website.
                    </p>

                </div>

            </a>


        </div>

    </section>



    <!-- ACCOUNT INFORMATION -->

    <section class="dashboard-account">


        <div class="dashboard-section-title">

            <p>
                ACCOUNT
            </p>

            <h2>
                Account Information
            </h2>

        </div>


        <div class="account-information">


            <div class="account-row">

                <span>
                    FIRST NAME
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["first_name"]
                    );
                    ?>
                </strong>

            </div>



            <div class="account-row">

                <span>
                    LAST NAME
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["last_name"]
                    );
                    ?>
                </strong>

            </div>



            <div class="account-row">

                <span>
                    EMAIL
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["email"]
                    );
                    ?>
                </strong>

            </div>


        </div>


    </section>


</main>


<?php require_once "includes/footer.php"; ?>


</body>

</html>
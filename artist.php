<?php
$basePath = "";
require_once "database/config.php";
require_once "security/authorize.php";


// Get artists from database
$sql = "SELECT *
        FROM artists
        ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$artists = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artists | EDL Gallery</title>
    <link rel = "icon" type="image/x-icon" href = "Images/logo.png">
    <link rel="stylesheet" href="style.css">

</head>

<body>


<?php require_once "includes/header.php"; ?>

<section class="art-page-layout">
    <div class="artpage-text">

        <h1>
            Meet the <span>Artists</span>
        </h1>

        <p>
            Discover the creative minds<br>
            behind the art.
        </p>
    </div>
</section>

<section class="artist-section">

    <div class="artist-container">


        <?php if (empty($artists)): ?>


            <div class="no-artists">

                <h2>
                    NO ARTISTS AVAILABLE
                </h2>

                <p>
                    Artist information will be available soon.
                </p>

            </div>


        <?php else: ?>


            <?php foreach ($artists as $artist): ?>


                <article class="artist-card">


                    <?php if (!empty($artist["image"])): ?>

                        <img
                            src="Images/<?php echo htmlspecialchars($artist["image"]); ?>"
                            alt="<?php echo htmlspecialchars($artist["name"]); ?>"
                        >

                    <?php endif; ?>


                    <div class="artist-info">

                        <h2>
                            <?php
                            echo htmlspecialchars(
                                $artist["name"]
                            );
                            ?>
                        </h2>


                        <p>
                            <?php
                            echo htmlspecialchars(
                                $artist["biography"]
                            );
                            ?>
                        </p>

                    </div>


                </article>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>

</section>



<?php require_once "includes/footer.php"; ?>


</body>

</html>
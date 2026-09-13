<?php

session_start();

$basePath = "";

require_once "includes/function.php";
require_once "database/config.php";
require_once "security/shield.php";
require_once "security/authorize.php";

/*
|--------------------------------------------------------------------------
| USER ACCESS
|--------------------------------------------------------------------------
*/

if (!isUser()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| GET EXHIBITION ID
|--------------------------------------------------------------------------
*/

$exhibitionId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$exhibitionId) {
    header("Location: my-exhibitions.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET EXHIBITION
|--------------------------------------------------------------------------
| Make sure the exhibition belongs to the logged-in user.
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        title,
        start_date,
        end_date,
        description,
        image,
        status
    FROM exhibitions
    WHERE id = :exhibition_id
    AND organizer_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ":exhibition_id" => $exhibitionId,
    ":user_id" => $userId
]);

$exhibition = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exhibition) {
    header("Location: my-exhibitions.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| HANDLE FORM ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | CSRF PROTECTION
    |--------------------------------------------------------------------------
    */

    if (!verify_csrf_token()) {
        die("Invalid CSRF token.");
    }

    $action = $_POST["action"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | ADD ARTIST
    |--------------------------------------------------------------------------
    */

    if ($action === "add_artist") {

        $artistId = filter_input(
            INPUT_POST,
            "artist_id",
            FILTER_VALIDATE_INT
        );

        if ($artistId) {

            /*
            |--------------------------------------------------------------------------
            | VERIFY ARTIST EXISTS
            |--------------------------------------------------------------------------
            */

            $artistCheck = $pdo->prepare("
                SELECT id
                FROM artists
                WHERE id = :artist_id
                AND (
                    user_id = :user_id
                    OR user_id IS NULL
                )
                LIMIT 1
            ");

            $artistCheck->execute([
                ":artist_id" => $artistId,
                ":user_id" => $userId
            ]);

            $artistExists = $artistCheck->fetchColumn();

            /*
            |--------------------------------------------------------------------------
            | ADD ARTIST IF VALID
            |--------------------------------------------------------------------------
            */

            if ($artistExists) {

                /*
                |--------------------------------------------------------------------------
                | CHECK IF ARTIST IS ALREADY ADDED
                |--------------------------------------------------------------------------
                */

                $duplicateCheck = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM exhibition_artists
                    WHERE exhibition_id = :exhibition_id
                    AND artist_id = :artist_id
                ");

                $duplicateCheck->execute([
                    ":exhibition_id" => $exhibitionId,
                    ":artist_id" => $artistId
                ]);

                $alreadyAdded = (int) $duplicateCheck->fetchColumn();

                /*
                |--------------------------------------------------------------------------
                | INSERT INTO EXHIBITION_ARTISTS
                |--------------------------------------------------------------------------
                */

                if ($alreadyAdded === 0) {

                    $insert = $pdo->prepare("
                        INSERT INTO exhibition_artists
                        (
                            exhibition_id,
                            artist_id
                        )
                        VALUES
                        (
                            :exhibition_id,
                            :artist_id
                        )
                    ");

                    $insert->execute([
                        ":exhibition_id" => $exhibitionId,
                        ":artist_id" => $artistId
                    ]);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN TO PAGE
        |--------------------------------------------------------------------------
        */

        header(
            "Location: exhibition-artists.php?id=" . $exhibitionId
        );
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | REMOVE ARTIST
    |--------------------------------------------------------------------------
    */

    if ($action === "remove_artist") {

        $artistId = filter_input(
            INPUT_POST,
            "artist_id",
            FILTER_VALIDATE_INT
        );

        if ($artistId) {

            $delete = $pdo->prepare("
                DELETE FROM exhibition_artists
                WHERE exhibition_id = :exhibition_id
                AND artist_id = :artist_id
            ");

            $delete->execute([
                ":exhibition_id" => $exhibitionId,
                ":artist_id" => $artistId
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN TO PAGE
        |--------------------------------------------------------------------------
        */

        header(
            "Location: exhibition-artists.php?id=" . $exhibitionId
        );
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| GET CURRENTLY ASSIGNED ARTISTS
|--------------------------------------------------------------------------
*/

$assignedStmt = $pdo->prepare("
    SELECT
        a.id,
        a.name,
        a.biography,
        a.image
    FROM exhibition_artists ea

    INNER JOIN artists a
        ON a.id = ea.artist_id

    WHERE ea.exhibition_id = :exhibition_id

    ORDER BY a.name ASC
");

$assignedStmt->execute([
    ":exhibition_id" => $exhibitionId
]);

$assignedArtists = $assignedStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| GET AVAILABLE ARTISTS
|--------------------------------------------------------------------------
*/

$availableStmt = $pdo->prepare("
    SELECT
        id,
        name
    FROM artists
    WHERE user_id = :user_id
       OR user_id IS NULL
    ORDER BY name ASC
");

$availableStmt->execute([
    ":user_id" => $userId
]);

$availableArtists = $availableStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| GET ASSIGNED ARTIST IDS
|--------------------------------------------------------------------------
*/

$assignedIds = [];

foreach ($assignedArtists as $artist) {
    $assignedIds[] = (int) $artist["id"];
}

?>

<?php require_once "includes/header.php"; ?>
<link rel="icon" type="image/x-icon" href="Images/logo.png">
<link rel="stylesheet" href="style.css">
<main class="exhibition-artists-page">

    <!-- =========================================================
         PAGE HEADER
    ========================================================== -->

    <section class="exhibition-artists-hero">

        <div class="exhibition-artists-heading">

            <a
                href="my-exhibitions.php"
                class="back-dashboard-link"
            >
                ← BACK TO MY EXHIBITIONS
            </a>

            <p class="section-label">
                EXHIBITION ARTISTS
            </p>

            <h1>
                <?php
                echo htmlspecialchars(
                    $exhibition["title"],
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </h1>

            <p class="exhibition-artists-intro">
                Add and manage the artists participating in this exhibition.
            </p>

        </div>

    </section>


    <!-- =========================================================
         ADD ARTISTS
    ========================================================== -->

    <section class="add-artist-section">

        <div class="artist-section-heading">

            <p class="section-label">
                ARTISTS
            </p>

            <h2>
                Add Artists
            </h2>

        </div>


                <?php if (!empty($availableArtists)): ?>

            <form
                method="POST"
                class="add-artist-form"
            >

                <?php echo csrf_field(); ?>

                <input
                    type="hidden"
                    name="action"
                    value="add_artist"
                >

                <label for="artist_id">
                    SELECT ARTIST
                </label>

                <select
                    name="artist_id"
                    id="artist_id"
                    required
                >

                    <option value="">
                        Select an artist
                    </option>

                    <?php foreach ($availableArtists as $artist): ?>

                        <?php
                        $currentArtistId = (int) $artist["id"];

                        if (
                            in_array(
                                $currentArtistId,
                                $assignedIds,
                                true
                            )
                        ) {
                            continue;
                        }
                        ?>

                        <option
                            value="<?php echo $currentArtistId; ?>"
                        >
                            <?php
                            echo htmlspecialchars(
                                $artist["name"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <button
                    type="submit"
                    class="orange-button"
                >
                    ADD ARTIST
                </button>

            </form>

            <p class="field-help field-help-spaced">
                Don't see the artist you're looking for?
                <a href="my-artists.php">Add them to your artist directory first →</a>
            </p>

        <?php else: ?>

            <div class="no-artists-message">

                <h3>
                    No Artists Available
                </h3>

                <p>
                    You haven't added any artists yet. Add one to
                    your artist directory, then come back here to
                    include them in this exhibition.
                </p>

                <a
                    href="my-artists.php"
                    class="outline-button outline-button-spaced"
                >
                    GO TO MY ARTISTS
                </a>

            </div>

        <?php endif; ?>

    </section>


    <!-- =========================================================
         CURRENT ARTISTS
    ========================================================== -->

    <section class="current-artists-section">

        <div class="artist-section-heading">

            <p class="section-label">
                YOUR EXHIBITION
            </p>

            <h2>
                Current Artists
            </h2>

        </div>


        <?php if (empty($assignedArtists)): ?>

            <div class="no-artists-message">

                <h3>
                    No Artists Added Yet
                </h3>

                <p>
                    Add artists above to include them in this exhibition.
                </p>

            </div>

        <?php else: ?>

            <div class="exhibition-artist-grid">

                <?php foreach ($assignedArtists as $artist): ?>

                    <article class="exhibition-artist-card">

                        <!-- ARTIST IMAGE -->

                        <div class="exhibition-artist-image">

                            <?php if (!empty($artist["image"])): ?>

                                <img
                                    src="<?php
                                    echo htmlspecialchars(
                                        edlImagePath($artist["image"]),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>"
                                    alt="<?php
                                    echo htmlspecialchars(
                                        $artist["name"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>"
                                >

                            <?php else: ?>

                                <div class="artist-no-image">
                                    NO IMAGE
                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- ARTIST INFORMATION -->

                        <div class="exhibition-artist-info">

                            <h3>
                                <?php
                                echo htmlspecialchars(
                                    $artist["name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>
                            </h3>


                            <?php if (!empty($artist["biography"])): ?>

                                <p>
                                    <?php
                                    echo htmlspecialchars(
                                        $artist["biography"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </p>

                            <?php endif; ?>


                            <!-- REMOVE ARTIST -->

                            <form
                                method="POST"
                                class="remove-artist-form"
                            >

                                <?php echo csrf_field(); ?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="remove_artist"
                                >

                                <input
                                    type="hidden"
                                    name="artist_id"
                                    value="<?php
                                    echo (int) $artist["id"];
                                    ?>"
                                >

                                <button
                                    type="submit"
                                    class="remove-artist-button"
                                    onclick="return confirm('Remove this artist from the exhibition?');"
                                >
                                    REMOVE ARTIST
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>


    <!-- =========================================================
         NEXT STEP - ARTWORKS
    ========================================================== -->

    <section class="artworks-next-section">

        <p class="section-label">
            NEXT STEP
        </p>

        <h2>
            Add Artworks
        </h2>

        <p>
            Once your artists are added, you can add the artworks
            they will display in this exhibition.
        </p>

        <a
            href="exhibition-artworks.php?id=<?php echo (int) $exhibitionId; ?>"
            class="outline-button"
        >
            ADD ARTWORKS →
        </a>

    </section>

</main>

<?php require_once "includes/footer.php"; ?>
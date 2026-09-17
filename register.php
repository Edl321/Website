<?php


require_once "database/config.php";
require_once "security/shield.php";

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token. Please try again.";
    }

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    if (empty($first_name)) {

        $errors[] = "First name is required.";

    }

    if (empty($last_name)) {

        $errors[] = "Last name is required.";

    }


    if (empty($email)) {

        $errors[] = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";

    }


    if (empty($password)) {

        $errors[] = "Password is required.";

    } elseif (strlen($password) < 8) {

        $errors[] = "Password must be at least 8 characters.";

    }

    if (empty($confirm_password)) {

        $errors[] = "Please confirm your password.";

    } elseif ($password !== $confirm_password) {

        $errors[] = "Passwords do not match.";

    }

    if (empty($errors)) {

        $sql = "SELECT id
                FROM users
                WHERE email = :email";

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(":email", $email);

        $stmt->execute();

        $existingUser = $stmt->fetch();


        if ($existingUser) {

            $errors[] = "An account with this email already exists.";

        } else {

            $hashedpassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            $sql = "INSERT INTO users
                    (first_name, last_name, email, password, role)
                    VALUES
                    (:first_name, :last_name, :email, :password, 'user')";

            $stmt = $pdo->prepare($sql);

            $stmt->bindValue(":first_name", $first_name);
            $stmt->bindValue(":last_name", $last_name);
            $stmt->bindValue(":email", $email);
            $stmt->bindValue(":password", $hashedpassword);

            $stmt->execute();

            $success = "Account created successfully! You can now log in.";
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | EDL Gallery</title>
    <Link rel="icon"type="image/x-icon"href="Images/logo.png">
    <link rel="stylesheet" href="register.css">
</head>


<body>

    <section class="register-header">

        <img src="Images/logo.png" alt="EDL Gallery">

    </section>

    <h1>Create an Account</h1>

    <?php if (!empty($errors)): ?>

        <div>

            <?php foreach ($errors as $error): ?>

                <p>
                    <?php echo htmlspecialchars($error); ?>
                </p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <?php if (!empty($success)): ?>

        <div>

            <p>
                <?php echo htmlspecialchars($success); ?>
            </p>

            <p>
                <a href="login.php">Go to Login</a>
            </p>

        </div>

    <?php endif; ?>

    <?php if (empty($success)): ?>


<form method="POST" action="register.php">

    <?php echo csrf_field(); ?>

        <label for="first_name">First Name</label>

        <input
            type="text"
            id="first_name"
            name="first_name"
            value="<?php echo htmlspecialchars($first_name ?? ""); ?>"
            required
        >

            <br><br>

        <label for="last_name">Last Name</label>

        <input
            type="text"
            id="last_name"
            name="last_name"
            value="<?php echo htmlspecialchars($last_name ?? ""); ?>"
            required
        >

            <br><br>

        <label for="email">Email</label>

        <input
            type="email"
            id="email"
            name="email"
            value="<?php echo htmlspecialchars($email ?? ""); ?>"
            required
        >

            <br><br>

            <label for="password">Password</label>

        <input
            type="password"
            id="password"
            name="password"
            required
        >

            <br><br>

        <label for="confirm_password">Confirm Password</label>

        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            required
        >

            <br><br>

    <button type="submit">Create Account</button>

</form>

    <?php endif; ?>


    <p>
        Already have an account?
        <a href="login.php">Login</a>
    </p>


</body>

</html>
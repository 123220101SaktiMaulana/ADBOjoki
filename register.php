<?php
require_once "config/config.php";

$username = $email = $password = $confirm_password = $role = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $errors[] = "Please enter a username.";
    } else {
        $sql = "SELECT userID FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $errors[] = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                $errors[] = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $errors[] = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $errors[] = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $errors[] = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $errors[] = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $errors[] = "Password did not match.";
        }
    }

    // Validate role
    if (empty($_POST['role'])) {
        $errors[] = "Please select a role.";
    } else {
        $role = $_POST['role'];
    }

    // Check input errors before inserting in database
    if (empty($errors)) {
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssss", $param_username, $param_email, $param_password, $param_role);

            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_role = $role;

            if (mysqli_stmt_execute($stmt)) {
                header("location: login.php");
            } else {
                $errors[] = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<?php include 'includes/header.php'; ?>

<div class="form-container">
    <h2>Register</h2>
    <p>Please fill this form to create an account.</p>

    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo $username; ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo $email; ?>">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password">
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password">
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role">
                <option value="">Select a role...</option>
                <option value="customer">Customer</option>
                <option value="joki">Joki</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="form-group">
            <input type="submit" class="btn" value="Register">
        </div>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </form>
</div>

<?php include 'includes/footer.php'; ?> 
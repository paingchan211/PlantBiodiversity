<?php
session_name('paing_chan'); // Name the session to prevent conflicts
session_start();

include 'main.php'; // Include the connection

// Redirect to main_menu.php if the user is already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['email'])) {
    header("Location: main_menu.php");
    exit();
}

$message = '';
$success = false;

// Helper function to validate email format
function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Helper function to retrieve user details from the database
// also get the user type
function get_user_by_email($email, $conn)
{
    $stmt = $conn->prepare("SELECT a.email, a.password, a.type, 
                                   u.first_name, u.last_name, u.dob, u.gender, u.hometown
                            FROM account_table a 
                            JOIN user_table u ON a.email = u.email 
                            WHERE a.email = ?");
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close(); // Close the statement after use
        return $user;
    } else {
        return null;
    }
}

// Process the login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate email format
    if (!validate_email($email)) {
        $message = 'Invalid email format!';
    } else {
        // Retrieve user data by email from the database
        $user = get_user_by_email($email, $conn);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true); // Prevent session fixation

            // Set session variables upon successful login
            $_SESSION['loggedin'] = true;
            $_SESSION['email'] = $user['email'];
            $_SESSION['type'] = $user['type']; // Store user type
            // print the user type
            $_SESSION['username'] = $user['first_name'] . ' ' . $user['last_name'];
            $success = true;
        } else {
            $message = 'Login failed. Invalid email or password.';
        }
    }
}
?>

<?php include_once 'head.php'; ?>

<body>
    <?php include_once "header.php"; ?>
    <main id="main-mt" class="d-flex align-items-center justify-content-center p-2">
        <div class="card shadow-lg p-4" id="login-container">
            <h1 class="text-center mb-4">Please Log In</h1>

            <!-- Display success message and meta refresh for redirection -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <small>Login successful! Redirecting ...</small>
                </div>
                <?php echo $_SESSION['type'] ?>
                <?php if ($_SESSION['type'] == 'admin'): ?>
                    <meta http-equiv="refresh" content="2;url=main_menu_admin.php">
                <?php else: ?>
                    <meta http-equiv="refresh" content="2;url=main_menu.php">
                <?php endif; ?>
            <?php endif; ?>

            <form id="login-form" method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" placeholder="password" name="password" class="form-control" required>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
                <p class="mt-3 text-center">Don't have an account?
                    <a href="registration.php" class="text-primary">Register</a>
                </p>
                <p class="text-center">Forgot your password?
                    <a href="forgot_password.php" class="text-primary">Reset</a>
                </p>
            </form>

            <?php if ($message): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include_once 'footer.php'; ?>
</body>

</html>
<?php
session_name('paing_chan'); // name the session to prevent conflicts with other applications
session_start();

require_once 'main.php'; // Include the database connection

$error_message = '';
$success_message = '';
$success = false;

// Check if the token is valid
if (isset($_GET['token']) && isset($_SESSION['reset_token']) && $_GET['token'] === $_SESSION['reset_token']) {
    // Process the password reset form
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        if ($new_password !== $confirm_password) {
            $error_message = 'Passwords do not match!';
        } elseif (strlen($new_password) < 8 || !preg_match("/[0-9]/", $new_password) || !preg_match("/[\W]/", $new_password)) {
            $error_message = "Password must be at least 8 characters long, with at least one number and one symbol.";
        } else {
            // Update the password in account_table
            $email = $_SESSION['reset_email'];
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $query = "UPDATE account_table SET password = ? WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $hashed_password, $email);
            if ($stmt->execute()) {
                // Clear session variables related to reset
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_email']);

                $success_message = 'Password reset success!';
                $success = true;
            } else {
                $error_message = 'Failed to reset password. Please try again.';
            }
        }
    }
} else {
    $error_message = 'Invalid or expired reset token.';
}

?>

<?php include_once 'head.php'; ?>

<body>
    <?php include_once 'header.php'; ?>
    <main id="main-mt" class="d-flex align-items-center justify-content-center p-2">
        <div class="card shadow-lg p-4" id="reset-password-container" style="max-width: 380px;">
            <h1 class="text-center mb-4">Reset Password</h1>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message) ?><br>
                    Redirecting to Login ...
                    <meta http-equiv="refresh" content="3;url=login.php">
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" minlength="8">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="8">
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <?php include_once 'footer.php'; ?>
</body>

</html>
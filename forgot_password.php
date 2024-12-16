<?php
session_name('paing_chan'); // name the session to prevent conflicts with other applications
session_start();

require_once 'main.php'; // Include the database connection

$message = '';
$success = false;

// Helper function to validate email format
function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Helper function to retrieve the user from the database by email, dob, and hometown
function get_user_by_credentials($email, $dob, $hometown, $conn)
{
    $query = "SELECT email, dob, hometown FROM user_table WHERE email = ? AND dob = ? AND hometown = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $email, $dob, $hometown);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Process the password reset request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $dob = trim($_POST['dob']);
    $hometown = trim($_POST['hometown']);

    // Validate email format
    if (!validate_email($email)) {
        $message = 'Invalid email format!';
    } else {
        $user = get_user_by_credentials($email, $dob, $hometown, $conn);

        if ($user) {
            // Set the success flag to true
            $success = true;

            // Generate a token for password reset (if needed for further functionality)
            $token = bin2hex(random_bytes(16));

            // Store the token and email in the session
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_email'] = $email;
        } else {
            $message = 'Invalid email, date of birth, or hometown!';
        }
    }
}
?>

<?php include_once 'head.php'; ?>

<body>
    <?php include_once 'header.php'; ?>
    <main id="main-mt" class="d-flex align-items-center justify-content-center p-2">
        <div class="card shadow-lg p-4" id="reset-container">
            <h1 class="text-center mb-4">Forgot Password</h1>

            <!-- Display success or error messages -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Details matched! Redirecting to Reset Password ...
                </div>
                <meta http-equiv="refresh" content="2;url=reset_password.php?token=<?= htmlspecialchars($_SESSION['reset_token']) ?>">
            <?php elseif ($message): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Form for email, dob, and hometown -->
            <form method="POST" action="forgot_password.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Enter your Email</label>
                    <input type="email" id="email" name="email" placeholder="Email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="dob" class="form-label">Enter your Date of Birth</label>
                    <input type="date" id="dob" name="dob" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="hometown" class="form-label">Enter your Hometown</label>
                    <input type="text" id="hometown" name="hometown" placeholder="Hometown" class="form-control" required>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Request Reset</button>
                </div>
            </form>
        </div>
    </main>

    <?php include_once 'footer.php'; ?>
</body>
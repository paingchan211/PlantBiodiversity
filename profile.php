<?php
session_name('paing_chan'); // Name the session
session_start();
require_once 'main.php'; // Ensure this file contains database connection setup

// Redirect the user to the login page if they are not logged in
if (empty($_SESSION['loggedin']) || empty($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$name = '';
$studentId = '';
$profileImage = '';

// Fetch user data from the database
$sql = "SELECT first_name, last_name, student_id, email, profile_image FROM user_table WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userInfo = $result->fetch_assoc();

    // Assigning data to variables
    $firstName = $userInfo['first_name'];
    $lastName = $userInfo['last_name'];
    $email = $userInfo['email'];
    $profileImage = $userInfo['profile_image'] ?? 'default.jpg'; // Default image if none provided
    $studentId = $userInfo['student_id'];

    // Full name concatenation
    $name = $firstName . ' ' . $lastName;
} else {
    // If user not found, redirect or show an error
    header("Location: error.php");
    exit();
}

$stmt->close();
?>

<?php include_once 'head.php'; ?>

<body class="bg-light">
    <?php include_once 'header.php'; ?>
    <main id="main-mt">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card text-center shadow-lg">
                        <div class="card-header">
                            <h1 class="h3">Profile Page</h1>
                        </div>
                        <div class="card-body">
                            <!-- Display profile image -->
                            <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile Image" class="rounded-circle mb-3 profile-img">
                            <h6><strong>Name:</strong> <?= htmlspecialchars($name) ?></h6>
                            <h6><strong>Student ID:</strong> <?= htmlspecialchars($studentId) ?></h6>
                            <h6><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></h6>
                            <p class="mt-4">
                                I declare that this assignment is my individual work. I have not worked collaboratively nor have I copied from any other student's work or from any other source. I have not engaged another party to complete this assignment. I am aware of the University's policy with regards to plagiarism. I have not allowed, and will not allow, anyone to copy my work with the intention of passing it off as his or her own work.
                            </p>
                        </div>
                        <div class="card-footer">
                            <a href="update_profile.php" class="btn btn-success">Edit Profile</a>
                            <a href="index.php" class="btn btn-dark">Home Page</a>
                            <a href="about.php" class="btn btn-primary">About</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include_once 'footer.php'; ?>
</body>

</html>
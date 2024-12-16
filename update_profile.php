<?php
session_name('paing_chan'); // Use the same session as login
session_start();
require_once 'main.php'; // Database connection file

// Redirect if the user is not logged in
if (empty($_SESSION['loggedin']) || empty($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$message = '';
$success = false;

// Fetch current user data
$user = get_user_by_email($email, $conn);

// Initialize error variables and success flag
$first_name_error = $last_name_error = $dob_error = $gender_error = $email_error =
    $contact_number_error = $email_exists_error =
    $image_upload_error = $hometown_error = $image_error = $student_id_error = $password_error = "";

// Directories for uploads
$profile_img_dir = "profile_images/";
$resume_dir = "resume/";

// Ensure the upload directories exist
if (!is_dir($profile_img_dir)) mkdir($profile_img_dir, 0755, true);
if (!is_dir($resume_dir)) mkdir($resume_dir, 0755, true);

// Fetch user data from the database
function get_user_by_email($email, $conn)
{
    $stmt = $conn->prepare("SELECT * FROM user_table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Update user data in the database
function update_user_data($data, $conn)
{
    $stmt = $conn->prepare(
        "UPDATE user_table 
         SET first_name = ?, last_name = ?, dob = ?, gender = ?, 
             hometown = ?, email = ?, profile_image = ?, resume = ?, contact_number = ?, student_id = ? 
         WHERE email = ?"
    );

    if (!$stmt) {
        die("Prepare failed: " . $conn->error); // Add error handling
    }

    // Ensure the bind_param order matches the SQL update query order
    $stmt->bind_param(
        "sssssssssss",
        $data['first_name'],
        $data['last_name'],
        $data['dob'],
        $data['gender'],
        $data['hometown'],
        $data['email'],  // Updated email
        $data['profile_image'],
        $data['resume'],
        $data['contact_number'],
        $data['student_id'],  // Correctly binding student_id here
        $data['original_email'] // Original email to find the user
    );

    if (!$stmt->execute()) {
        echo "Error updating user: " . $stmt->error; // Add error message
        return false;
    }

    return true;
}


// Helper function to validate email format
function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Helper function to check if email already exists in the database
function email_exists_in_db($email, $conn)
{
    $stmt = $conn->prepare("SELECT email FROM user_table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $dob = trim($_POST['dob']);
    $gender = trim($_POST['gender']);
    $hometown = trim($_POST['hometown']);
    $contact_number = trim($_POST['contact_number']);
    $new_email = trim($_POST['email']);
    $profile_image = $user['profile_image']; // Keep existing image
    $resume = $user['resume']; // Keep existing resume
    $student_id = trim($_POST['student_id']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Input validation
    if (empty($first_name) || !preg_match("/^[a-zA-Z\s]+$/", $first_name)) {
        $first_name_error = "First name is required and can only contain letters and spaces.";
    }

    if (empty($last_name) || !preg_match("/^[a-zA-Z\s]+$/", $last_name)) {
        $last_name_error = "Last name is required and can only contain letters and spaces.";
    }

    if (empty($dob)) {
        $dob_error = "Date of birth is required.";
    }

    if (empty($gender)) {
        $gender_error = "Gender is required.";
    }

    if (empty($email) || !validate_email($email)) {
        $email_error = "Invalid email format.";
    } elseif ($email !== $new_email && email_exists_in_db($email, $conn)) {
        $email_exists_error = "This email is already registered.";
    }

    if (empty($hometown)) {
        $hometown_error = "Hometown is required.";
    }

    if (empty($contact_number) || !ctype_digit($contact_number)) {
        $contact_number_error = "Contact number is required and must be numeric.";
    }

    if (empty($student_id) || !ctype_digit($student_id)) {
        $student_id_error = "Student ID is required and must be numeric.";
    }

    // Validate image upload
    if (!empty($_FILES['profile_image']['name']) && empty($image_upload_error)) {
        $img_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if (in_array($img_ext, ['jpg', 'jpeg', 'png']) && $_FILES['profile_image']['size'] <= 5 * 1024 * 1024) {
            $profile_image = $profile_img_dir . uniqid() . "_" . basename($_FILES['profile_image']['name']);
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image);
        } else {
            $image_upload_error = 'Invalid image file. Allowed: jpg, jpeg, png. Max size: 5MB.';
        }
    }

    // Validate resume upload
    if (!empty($_FILES['resume']['name']) && empty($resume_upload_error)) {
        $resume_ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        if ($resume_ext === 'pdf' && $_FILES['resume']['size'] <= 7 * 1024 * 1024) {
            $resume = $resume_dir . uniqid() . "_" . basename($_FILES['resume']['name']);
            move_uploaded_file($_FILES['resume']['tmp_name'], $resume);
        } else {
            $resume_upload_error = 'Invalid resume file. Only PDF allowed. Max size: 7MB.';
        }
    }

    // Validate password if provided
    if (!empty($password) || !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $password_error = "Passwords do not match.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $update_password_sql = "UPDATE account_table SET password = ? WHERE email = ?";
            $stmt_password = $conn->prepare($update_password_sql);

            if ($stmt_password) {
                $stmt_password->bind_param('ss', $hashed_password, $email);
                if (!$stmt_password->execute()) {
                    $password_error = "Error updating password: " . $conn->error;
                }
                $stmt_password->close();
            } else {
                $password_error = "Database error: " . $conn->error;
            }
        }
    }


    // Check if required fields are filled
    if (
        empty($first_name) || empty($last_name) || empty($dob) || empty($gender) ||
        empty($hometown) || empty($new_email) || empty($contact_number)
    ) {
        $message = 'Please fill in all required fields.';
    } else if (
        $first_name_error === "" && $last_name_error === "" && $dob_error === "" &&
        $gender_error === "" && $hometown_error === "" &&
        $contact_number_error === "" && $password_error === ""
    ) {
        // Prepare data for update
        $update_data = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'dob' => $dob,
            'gender' => $gender,
            'hometown' => $hometown,
            'contact_number' => $contact_number,
            'email' => $new_email,
            'profile_image' => $profile_image,
            'resume' => $resume,
            'original_email' => $email,
            'student_id' => $student_id
        ];

        // Update user data in the database
        if (update_user_data($update_data, $conn)) {
            if ($email !== $new_email) {
                $_SESSION['email'] = $new_email; // Only update session if email changed
            }
            $_SESSION['username'] = $first_name . ' ' . $last_name; // Update username in session
            $success = true;
            header("Location: profile.php");
            exit();
        } else {
            $message = 'Failed to update profile. Please try again.';
        }
    } else {
        $message = 'Failed to update profile. Please try again.';
    }
}
?>

<?php include_once 'head.php'; ?>

<body>
    <?php include_once "header.php"; ?>

    <main id="main-mt" class="d-flex align-items-center justify-content-center p-2">
        <div class="card shadow-lg p-4" id="profile-update-container">
            <h1 class="text-center mb-4">Update Profile</h1>

            <?php if ($success): ?>
                <div class="alert alert-success">Profile updated successfully.</div>
            <?php endif; ?>


            <!-- Display error message -->
            <?php if ($message && !$success): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($user): ?>
                <form method="POST" action="" enctype="multipart/form-data">

                    <div class="mb-3 text-center">
                        <img src="<?= $user['profile_image'] ? htmlspecialchars($user['profile_image']) : ($user['gender'] === 'Male' ? 'profile_images/boy.jpg' : 'profile_images/girl.png') ?>" alt="Profile Image" class="rounded-circle mb-3 profile-img">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label for="profile_image" class="form-label">Profile Image</label>
                            <input type="file" id="profile_image" name="profile_image" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="resume" class="form-label">Upload Resume (PDF)</label>
                            <input type="file" id="resume" name="resume" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" id="first_name" name="first_name" pattern="[a-zA-Z\s]+" value="<?= htmlspecialchars($user['first_name']) ?>" class="form-control" required>
                            <small class="text-danger"><?= $first_name_error ?></small>
                        </div>

                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" id="last_name" name="last_name" pattern="[a-zA-Z\s]+" value="<?= htmlspecialchars($user['last_name']) ?>" class="form-control" required>
                            <small class="text-danger"><?= $last_name_error ?></small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required>
                            <small class="text-danger"><?= $email_error ?></small>
                        </div>

                        <div class="col-md-6">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($user['dob']) ?>" class="form-control" required>
                            <small class="text-danger"><?= $dob_error ?></small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="text" id="contact_number" name="contact_number" value="<?= htmlspecialchars($user['contact_number']) ?>" class="form-control" required>
                            <small class="text-danger"><?= $contact_number_error ?></small>
                        </div>

                        <div class="col-md-6">
                            <label for="hometown" class="form-label">Hometown</label>
                            <input type="text" id="hometown" name="hometown" value="<?= htmlspecialchars($user['hometown']) ?>" class="form-control" required>
                            <small class="text-danger"><?= $hometown_error ?></small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="student_id" class="form-label">Student ID</label>
                            <input type="text" id="student_id" name="student_id" value="<?= htmlspecialchars($user['student_id']) ?>" class="form-control" required>
                            <small class="text-danger"><?= $student_id_error ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <div class="d-flex align-items-center">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="gender" id="male" value="Male" <?= $user['gender'] === 'Male' ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="male">Male</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="female" value="Female" <?= $user['gender'] === 'Female' ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="female">Female</label>
                                </div>
                            </div>
                            <small class="text-danger"><?= $gender_error ?></small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                        </div>
                        <small class="text-danger"><?= $password_error ?></small>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                        <a href="index.php" class="btn btn-danger">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </main>

    <?php include_once 'footer.php'; ?>
</body>


</html>
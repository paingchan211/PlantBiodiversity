<?php include_once 'process_registration.php'; ?>
<?php
$message = '';
$success = false;

// Initialize error variables and success flag
$first_name_error = $last_name_error = $dob_error = $gender_error = $email_error =
    $contact_number_error = $email_exists_error =
    $image_upload_error = $resume_upload_error = $hometown_error = $image_error = $student_id_error = $password_error =  $confirm_password_error =
    "";

if ($_SESSION['type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Delete user logic
if (isset($_POST['delete_user']) && isset($_POST['email'])) {
    $email = $_POST['email'];
    // Use prepared statements for deletion
    $stmt = $conn->prepare("DELETE FROM user_table WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM account_table WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();

    // Redirect to refresh the page after deletion
    header("Location: manage_accounts.php");
    exit();
}

// Edit user logic
if (isset($_POST['edit_user'])) {
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $hometown = $_POST['hometown'];
    $contact_number = $_POST['contact_number'];
    $student_id = $_POST['student_id'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

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
    } elseif (email_exists_in_db($email, $conn)) {
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

    // Check if required fields are filled
    if (
        empty($first_name) || empty($last_name) || empty($dob) || empty($gender) ||
        empty($hometown) || empty($email) || empty($contact_number)
    ) {
        $message = 'Please fill in all required fields.';
    } else if (
        $first_name_error === "" && $last_name_error === "" && $dob_error === "" &&
        $gender_error === "" && $hometown_error === "" &&
        $contact_number_error === "" && $password_error === "" && $student_id_error === ""
    ) {
        $stmt = $conn->prepare("UPDATE user_table SET first_name=?, last_name=?, dob=?, gender=?, hometown=?, contact_number=?, student_id=? WHERE email=?");
        $stmt->bind_param("ssssssss", $first_name, $last_name, $dob, $gender, $hometown, $contact_number, $student_id, $email);
        $stmt->execute();
        $stmt->close();
    } else {
        $message = 'Failed to update profile. Please try again.';
    }


    // Validate password if provided
    if (!empty($password) || !empty($confirm_password)) {
        if (strlen($password) < 8 || !preg_match("/[0-9]/", $password) || !preg_match("/[\W]/", $password)) {
            $password_error = "Password must be at least 8 characters long with one number and one special character.";
        } else if ($password !== $confirm_password) {
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

    // update the type
    $type = $_POST['type'];
    $update_type_sql = "UPDATE account_table SET type = ? WHERE email = ?";
    $stmt_type = $conn->prepare($update_type_sql);
    if ($stmt_type) {
        $stmt_type->bind_param('ss', $type, $email);
        if (!$stmt_type->execute()) {
            $message = "Error updating type: " . $conn->error;
        }
        $stmt_type->close();
    } else {
        $message = "Database error: " . $conn->error;
    }
}

$result = $conn->query("SELECT * FROM user_table");


// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form input
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $dob = trim($_POST['dob']);
    $gender = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $hometown = trim($_POST['hometown']);
    $contact_number = trim($_POST['contact_number']);
    $student_id = trim($_POST['student_id']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Handle image upload
    $image_path = NULL; // Initialize image path
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $image = $_FILES['profile_image'];
        $target_dir = "uploads/";
        $image_path = $target_dir . basename($image['name']);
        $image_file_type = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));

        // Validate image file type
        if (!in_array($image_file_type, ['jpg', 'png', 'jpeg'])) {
            $image_upload_error = "Only JPG, JPEG, and PNG files are allowed.";
        } elseif (!move_uploaded_file($image['tmp_name'], $image_path)) {
            $image_upload_error = "Error uploading the image.";
        }
    } else {
        // If no image is uploaded, set image path to NULL
        $image_path = NULL;
    }

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
    } elseif (email_exists_in_db($email, $conn)) {
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


    // If there are no errors, save data to the database
    if (
        empty($first_name_error) && empty($last_name_error) && empty($dob_error) &&
        empty($gender_error) && empty($email_error) && empty($email_exists_error) &&
        empty($hometown_error) && empty($contact_number_error) &&
        empty($password_error) && empty($confirm_password_error) && empty($image_upload_error)
    ) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Start a transaction to insert into both tables
        $conn->begin_transaction();
        try {
            // Insert into user_table
            $stmt = $conn->prepare("INSERT INTO user_table 
                (email, first_name, last_name, dob, gender, contact_number, hometown, profile_image, student_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)");
            $stmt->bind_param("sssssssss", $email, $first_name, $last_name, $dob, $gender, $contact_number, $hometown, $image_path, $student_id);
            $stmt->execute();
            $stmt->close();

            // Insert into account_table
            $stmt = $conn->prepare("INSERT INTO account_table (email, password, type) VALUES (?, ?, 'user')");
            $stmt->bind_param("ss", $email, $hashed_password);
            $stmt->execute();
            $stmt->close();

            // Commit the transaction
            $conn->commit();
            $success = true; // Set success flag for redirection
        } catch (Exception $e) {
            $conn->rollback(); // Rollback the transaction on error
            echo "Error: " . $e->getMessage();
        }
    }
}
?>

<?php include_once 'head.php'; ?>

<body>
    <?php include_once 'header.php'; ?>

    <main id="main-mt">
        <div class="m-5">
            <div class="d-flex">
                <h2>Manage Users' Accounts</h2>
                <a href="main_menu_admin.php" id="admin-nav" class="btn btn-secondary ms-2 mt-1">Back to Main Menu</a>
                <button type="button" class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
            </div>
            <table class="table table-bordered mt-3">
                <thead class="table-dark">
                    <tr>
                        <th>Email</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Date of Birth</th>
                        <th>Gender</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['first_name']) ?></td>
                            <td><?= htmlspecialchars($row['last_name']) ?></td>
                            <td><?= htmlspecialchars($row['dob']) ?></td>
                            <td><?= htmlspecialchars($row['gender']) ?></td>
                            <td>
                                <?php
                                $email = $row['email'];
                                $type_result = $conn->query("SELECT type FROM account_table WHERE email='$email'");
                                $type_row = $type_result->fetch_assoc();
                                echo htmlspecialchars($type_row['type']);
                                ?>
                            </td>
                            <td>
                                <div class="d-flex">
                                    <button type="button" class="btn btn-warning me-2 edit-btn" data-bs-toggle="modal" data-bs-target="#editModal"
                                        data-email="<?= $row['email'] ?>"
                                        data-first-name="<?= $row['first_name'] ?>"
                                        data-last-name="<?= $row['last_name'] ?>"
                                        data-dob="<?= $row['dob'] ?>"
                                        data-gender="<?= $row['gender'] ?>"
                                        data-hometown="<?= $row['hometown'] ?>"
                                        data-contact-number="<?= $row['contact_number'] ?>"
                                        data-student-id="<?= $row['student_id'] ?>"
                                        data-type="<?= $type_row['type'] ?>">Edit</button>
                                    <button type="button" class="btn btn-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-email="<?= $row['email'] ?>">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="first_name" pattern="[a-zA-Z\s]+" placeholder="First Name" value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>">
                                    <small class="text-danger"><?php echo $first_name_error; ?></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="last_name" pattern="[a-zA-Z\s]+" placeholder="Last Name" value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>">
                                    <small class="text-danger"><?php echo $last_name_error; ?></small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="student_id" placeholder="Student ID" value="<?php echo isset($student_id) ? htmlspecialchars($student_id) : ''; ?>">
                                    <small class="text-danger"><?php echo $student_id_error; ?></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="contact_number" placeholder="Contact Number" value="<?php echo isset($contact_number) ? htmlspecialchars($contact_number) : ''; ?>">
                                    <small class="text-danger"><?php echo $contact_number_error; ?></small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="email" placeholder="Email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                                    <small class="text-danger"><?php echo $email_error; ?></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="hometown" placeholder="Hometown" value="<?php echo isset($hometown) ? htmlspecialchars($hometown) : ''; ?>">
                                    <small class="text-danger"><?php echo $hometown_error; ?></small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="password" placeholder="Password" value="<?php echo isset($password) ? htmlspecialchars($password) : ''; ?>">
                                    <small class="text-danger"><?php echo $password_error; ?></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" name="confirm_password" placeholder="Confirm Password" value="<?php echo isset($confirm_password) ? htmlspecialchars($confirm_password) : ''; ?>">
                                    <small class="text-danger"><?php echo $confirm_password_error; ?></small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="dob" class="form-label">Date of Birth:</label>
                                    <input type="date" class="form-control" name="dob" value="<?php echo isset($dob) ? htmlspecialchars($dob) : ''; ?>">
                                    <small class="text-danger"><?php echo $dob_error; ?></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Gender:</label>
                                    <select class="form-control" name="gender">
                                        <option value="Male" <?php echo isset($gender) && $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo isset($gender) && $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                    <small class="text-danger"><?php echo $gender_error; ?></small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="profile_image" class="form-label">Profile Image: (Optional)</label>
                                    <input type="file" class="form-control" name="profile_image" accept="image/*">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="post" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <small>Edit User Success.</small>
                            </div>
                            <input type="hidden" id="keepModalOpen" value="true">
                            <script>
                                setTimeout(function() {
                                    window.location.href = "manage_accounts.php";
                                }, 2000);
                            </script>
                        <?php elseif (!empty($message)): ?>
                            <div class="alert alert-danger">
                                <?php echo $message; ?>
                            </div>
                            <input type="hidden" id="keepModalOpen" value="true">
                        <?php endif; ?>

                        <div class="modal-body">
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
                                    <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                                    <small class=" text-danger"><?= $first_name_error ?></small>
                                </div>

                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                                    <small class="text-danger"><?= $last_name_error ?></small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    <small class="text-danger"><?= $email_error ?></small>
                                </div>

                                <div class="col-md-6">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" id="dob" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>" required>
                                    <small class="text-danger"><?= $dob_error ?></small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="contact_number" class="form-label">Contact Number</label>
                                    <input type="text" id="contact_number" name="contact_number" class="form-control" value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>" required>
                                    <small class="text-danger"><?= $contact_number_error ?></small>
                                </div>

                                <div class="col-md-6">
                                    <label for="hometown" class="form-label">Hometown</label>
                                    <input type="text" id="hometown" name="hometown" class="form-control" value="<?= htmlspecialchars($_POST['hometown'] ?? '') ?>" required>
                                    <small class="text-danger"><?= $hometown_error ?></small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="student_id" class="form-label">Student ID</label>
                                    <input type="text" id="student_id" name="student_id" class="form-control" value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>" required>
                                    <small class="text-danger"><?= $student_id_error ?></small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Gender</label>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check me-3">
                                            <input class="form-check-input" type="radio" name="gender" id="male"
                                                value="Male"
                                                <?= (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="male">Male</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="female"
                                                value="Female"
                                                <?= (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'checked' : '' ?> required>
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

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="type" class="form-label">Type</label>
                                    <select id="type" name="type" class="form-control" required>
                                        <option value="user" <?= (isset($_POST['type']) && $_POST['type'] === 'user') ? 'selected' : '' ?>>user</option>
                                        <option value="admin" <?= (isset($_POST['type']) && $_POST['type'] === 'admin') ? 'selected' : '' ?>>admin</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="edit_user" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form method="post">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete this user?
                            <input type="hidden" name="email" id="delete-email">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const keepModalOpen = document.getElementById("keepModalOpen").value === "true";
                if (keepModalOpen) {
                    const editModal = new bootstrap.Modal(document.getElementById("editModal"));
                    editModal.show();
                }
            });
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('first_name').value = this.dataset.firstName;
                    document.getElementById('last_name').value = this.dataset.lastName;
                    document.getElementById('email').value = this.dataset.email;
                    document.getElementById('dob').value = this.dataset.dob;
                    document.getElementById('contact_number').value = this.dataset.contactNumber;
                    document.getElementById('hometown').value = this.dataset.hometown;
                    document.getElementById('student_id').value = this.dataset.studentId;

                    // Handle Gender Radio Buttons
                    if (this.dataset.gender === "Male") {
                        document.getElementById('male').checked = true;
                    } else {
                        document.getElementById('female').checked = true;
                    }

                    // Handle Type Dropdown
                    const typeDropdown = document.getElementById('type');
                    for (let i = 0; i < typeDropdown.options.length; i++) {
                        if (typeDropdown.options[i].value === this.dataset.type) {
                            typeDropdown.selectedIndex = i;
                            break;
                        }
                    }
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                // Handle delete button clicks
                document.querySelectorAll('.delete-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const email = this.getAttribute('data-email');
                        document.getElementById('delete-email').value = email;
                    });
                });
            });
        </script>
    </main>
</body>

</html>
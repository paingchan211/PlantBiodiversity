<?php
session_name('paing_chan'); // Name the session to avoid conflicts
session_start();
include 'main.php'; // Include the database connection

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

// Initialize error variables and success flag
$first_name_error = $last_name_error = $dob_error = $gender_error = $email_error =
    $contact_number_error = $password_error = $confirm_password_error = $email_exists_error =
    $image_upload_error = $hometown_error = $image_error = $student_id_error = "";
$success = false;

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

    if (empty($password) || strlen($password) < 8 || !preg_match("/[0-9]/", $password) || !preg_match("/[\W]/", $password)) {
        $password_error = "Password must be at least 8 characters long with one number and one special character.";
    }

    if ($password !== $confirm_password) {
        $confirm_password_error = "Passwords do not match.";
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

            // redirect to login page

            if ($_SESSION['type'] == 'admin') {
                header("Location: manage_accounts.php");
            }
        } catch (Exception $e) {
            $conn->rollback(); // Rollback the transaction on error
            echo "Error: " . $e->getMessage();
        }
    }
}

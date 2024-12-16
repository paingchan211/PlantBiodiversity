<?php
session_name('paing_chan');
session_start();

if (empty($_SESSION['loggedin']) || $_SESSION['type'] !== 'user') {
    header("Location: login.php");
    exit();
}

include_once 'main.php'; // Include connection and database setup

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error  = '';
$success = false;
$showModal = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    function validateInput($data)
    {
        return htmlspecialchars(trim($data));
    }

    $errors = [];
    $common_name = validateInput($_POST['common_name']);
    $family = validateInput($_POST['family']);
    $genus = validateInput($_POST['genus']);
    $species = validateInput($_POST['species']);

    if (empty($family)) {
        $errors[] = "Family is required.";
    }
    if (empty($genus)) {
        $errors[] = "Genus is required.";
    }
    if (empty($species)) {
        $errors[] = "Species is required.";
    }

    if (preg_match('/\s/', $family)) {
        $errors[] = "Family cannot have spaces.";
    }
    if (preg_match('/\s/', $genus)) {
        $errors[] = "Genus cannot have spaces.";
    }
    if (!preg_match('/^[A-Z][a-z]+(?:\s[a-z]+)?$/', $species)) {
        $errors[] = "Species must begin with a capital letter and can have only one space.";
    }

    $target_dir = "images/plants/";
    $herbarium_leaf = basename($_FILES["herbarium_leaf"]["name"]);
    $description = basename($_FILES["description"]["name"]);
    $herbarium_target_file = $target_dir . $herbarium_leaf;
    $habitat_target_file = $target_dir . $description;

    $description_dir = "plants_description/";
    if (!is_dir($description_dir)) {
        mkdir($description_dir, 0777, true); // Create the directory with full permissions if it doesn't exist
    }

    $description_file = basename($_FILES["description"]["name"]);
    $description_target_file = $description_dir . $description_file;

    // Continue with the rest of your code for file upload


    $descriptionFileType = strtolower(pathinfo($description_target_file, PATHINFO_EXTENSION));
    if ($descriptionFileType !== "pdf") {
        $errors[] = "Only PDF files are allowed for the description file.";
    }
    if ($_FILES["description"]["size"] > 7000000) {
        $errors[] = "Description file is too large. Maximum size is 7MB.";
    }

    if (empty($errors)) {
        if (
            move_uploaded_file($_FILES["herbarium_leaf"]["tmp_name"], $herbarium_target_file) &&
            move_uploaded_file($_FILES["description"]["tmp_name"], $description_target_file)
        ) {
            $stmt = $conn->prepare("INSERT INTO plant_table (Scientific_Name, Common_Name, family, genus, species, plants_image, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $species, $common_name, $family, $genus, $species, $herbarium_leaf, $description_target_file);
            $success = true;

            if (!$stmt->execute()) {
                $errors[] = "Database error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $errors[] = "There was an error uploading the files.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        $showModal = true;
    }
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
    $showModal = true;
}
?>


<?php include_once 'head.php'; ?>

<body>
    <input type="hidden" id="showModalFlag" value="<?php echo $showModal ? 'true' : 'false'; ?>">
    <?php include_once 'header.php'; ?>
    <main id="main-mt" class="d-flex flex-column align-items-center">
        <div class="rounded ps-4 w-100" id="contribute-container">
            <h1 class="mb-4 text-center">Contributions</h1>
            <div class="text-center">
                <button class="btn btn-primary rounded h-100" data-bs-toggle="modal" data-bs-target="#contributeModal">CONTRIBUTE</button>
            </div>
            <div class="p-3">
                <?php
                // Modify the SQL query to only select approved plants
                $sql = "SELECT id, Common_Name, Scientific_Name, plants_image FROM plant_table WHERE status = 'approved'";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    echo '<div class="row">';
                    while ($row = $result->fetch_assoc()) {
                        $common_name_data = !empty($row['Common_Name']) ? htmlspecialchars($row['Common_Name']) : 'Unknown Common Name';
                        echo '
                            <div class="col-md-4 col-12 col-sm-6">
                                <div class="card rounded shadow-lg mb-4">
                                    <img src="images/plants/' . htmlspecialchars($row['plants_image']) . '" class="card-img-top" alt="Plant Image">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">' . htmlspecialchars($row['Scientific_Name']) . '</h5>
                                        <p class="card-text">' . $common_name_data . '</p>
                                        <a href="plant_detail.php?id=' . htmlspecialchars($row['id']) . '" class="btn btn-primary">View Description</a>
                                    </div>
                                </div>
                            </div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p class="text-center mt-5 fs-4">No approved contributions yet.</p>';
                }

                ?>
            </div>
        </div>

        <!-- Contribution Modal -->
        <div class="modal fade" id="contributeModal" tabindex="-1" aria-labelledby="contributeModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="contributeModalLabel">Contribute</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <!-- Updated Contribution Modal Form -->
                    <div class="modal-body">
                        <form action="contribute.php" method="post" enctype="multipart/form-data">
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <small>Upload successful. Please wait for admin approval.</small>
                                </div>
                                <input type="hidden" id="keepModalOpen" value="true">
                                <script>
                                    setTimeout(function() {
                                        window.location.href = "contribute.php";
                                    }, 2000);
                                </script>
                            <?php elseif (!empty($error)): ?>
                                <div class="alert alert-danger">
                                    <?php echo $error; ?>
                                </div>
                                <input type="hidden" id="keepModalOpen" value="true">
                            <?php endif; ?>


                            <div class="mb-3">
                                <label for="species" class="form-label">Scientific Name (Species)<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="species" name="species" value="<?php echo htmlspecialchars($species ?? ''); ?>" placeholder="e.g. Dipterocarpus bourdillonii" pattern="^[A-Z][a-z]+(?:\s[a-z]+)?$" title="Species must begin with a capital letter and can have only one space." required>
                            </div>
                            <div class="mb-3">
                                <label for="common_name" class="form-label">Common Name</label>
                                <input type="text" class="form-control" id="common_name" name="common_name" value="<?php echo htmlspecialchars($common_name ?? ''); ?>" placeholder="e.g. Chiratta anjili">
                            </div>
                            <div class="mb-3">
                                <label for="family" class="form-label">Family<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="family" name="family" value="<?php echo htmlspecialchars($family ?? ''); ?>" placeholder="e.g. Dipterocarpaceae" pattern="^[^\s]+$" title="Family cannot have spaces." required>
                            </div>
                            <div class="mb-3">
                                <label for="genus" class="form-label">Genus<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="genus" name="genus" value="<?php echo htmlspecialchars($genus ?? ''); ?>" placeholder="e.g. Dipterocarpus" pattern="^[^\s]+$" title="Genus cannot have spaces." required>
                            </div>

                            <div class="mb-3">
                                <label for="herbarium_leaf" class="form-label">Herbarium Leaf<span class="text-danger">*</span></label>
                                <input class="form-control" type="file" id="herbarium_leaf" name="herbarium_leaf" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description<span class="text-danger">*</span></label>
                                <input class="form-control" type="file" id="description" name="description" required>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const keepModalOpen = document.getElementById("keepModalOpen").value === "true";
            if (keepModalOpen) {
                const contributeModal = new bootstrap.Modal(document.getElementById("contributeModal"));
                contributeModal.show();
            }
        });
    </script>

    <?php include_once 'back-to-top.php'; ?>
    <?php include_once 'footer.php'; ?>
</body>

</html>
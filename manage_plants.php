<?php
session_name('paing_chan');
session_start();
require_once 'main.php';

// Redirect if the user is not an admin
if ($_SESSION['type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle adding a new plant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_plant'])) {
    $species = $conn->real_escape_string($_POST['species']);
    $common_name = $conn->real_escape_string($_POST['common_name']);
    $family = $conn->real_escape_string($_POST['family']);
    $genus = $conn->real_escape_string($_POST['genus']);

    $image_path = '';
    $description_path = '';

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $image_path = basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    // Handle description upload
    if (!empty($_FILES['description']['name'])) {
        $description_path = 'plants_description/' . basename($_FILES['description']['name']);
        move_uploaded_file($_FILES['description']['tmp_name'], $description_path);
    }

    // Insert plant data into the database
    $sql = "INSERT INTO plant_table (Scientific_Name, Common_Name, family, genus, plants_image, description, status)
            VALUES ('$species', '$common_name', '$family', '$genus', '$image_path', '$description_path', 'pending')";

    if ($conn->query($sql) === TRUE) {
        header("Location: manage_plants.php");
        exit();
    } else {
        echo "<p class='text-danger'>Error adding plant: " . $conn->error . "</p>";
    }
}

// Handle updating plant status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = intval($_POST['id']);
    $status = $conn->real_escape_string($_POST['status']);

    $sql = "UPDATE plant_table SET status = '$status' WHERE id = $id";
    if ($conn->query($sql) === TRUE) {
        header("Location: manage_plants.php");
        exit();
    } else {
        echo "<p class='text-danger'>Error updating plant status: " . $conn->error . "</p>";
    }
}

// Handle deleting a plant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_plant'])) {
    $delete_id = intval($_POST['delete_id']);
    $sql = "DELETE FROM plant_table WHERE id = $delete_id";
    if ($conn->query($sql) === TRUE) {
        header("Location: manage_plants.php");
        exit();
    } else {
        echo "<p class='text-danger'>Error deleting plant: " . $conn->error . "</p>";
    }
}

// Retrieve all plant records
$sql = "SELECT * FROM plant_table";
$result = $conn->query($sql);
?>

<?php include_once 'head.php'; ?>

<body>
    <?php include_once 'header.php'; ?>

    <main id="main-mt">
        <div class="m-5 small">
            <div class="d-flex">
                <h2>Manage Plants</h2>
                <a href="main_menu_admin.php" id="admin-nav" class="btn ms-2 mt-1">Back to Main Menu</a>
                <button type="button" class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#addPlantModal">Add Plant</button>
            </div>
            <table class="table table-bordered table-hover mt-3">
                <thead class="thead-dark text-center">
                    <tr>
                        <th>Scientific Name</th>
                        <th>Common Name</th>
                        <th>Family</th>
                        <th>Genus</th>
                        <th>Image</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Scientific_Name']) ?></td>
                            <td><?= htmlspecialchars($row['Common_Name']) ?></td>
                            <td><?= htmlspecialchars($row['family']) ?></td>
                            <td><?= htmlspecialchars($row['genus']) ?></td>
                            <td>
                                <img src="images/plants/<?= htmlspecialchars($row['plants_image']) ?>" alt="<?= htmlspecialchars($row['Common_Name']) ?>" style="width: 120px;">
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                switch ($row['status']) {
                                    case 'pending':
                                        $status_class = 'text-warning';
                                        break;
                                    case 'approved':
                                        $status_class = 'text-success';
                                        break;
                                    case 'rejected':
                                        $status_class = 'text-danger';
                                        break;
                                }
                                ?>
                                <span class="<?= $status_class ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span>
                            </td>
                            <td class="text-center">
                                <form method="POST" class="d-inline-block">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <button type="submit" name="status" value="approved" class="btn btn-success btn-sm">Approve</button>
                                        <button type="submit" name="status" value="rejected" class="btn btn-danger btn-sm">Reject</button>
                                    <?php elseif ($row['status'] === 'approved'): ?>
                                        <button type="submit" name="status" value="approved" class="btn btn-success btn-sm" disabled>Approved</button>
                                        <button type="submit" name="status" value="rejected" class="btn btn-danger btn-sm">Reject</button>
                                    <?php elseif ($row['status'] === 'rejected'): ?>
                                        <button type="submit" name="status" value="approved" class="btn btn-success btn-sm">Approve</button>
                                        <button type="submit" name="status" value="rejected" class="btn btn-danger btn-sm" disabled>Rejected</button>
                                    <?php endif; ?>
                                </form> <br>
                                <button class="btn btn-danger btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#deleteModal-<?= $row['id'] ?>">Delete</button>
                            </td>
                        </tr>

                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteModal-<?= $row['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel-<?= $row['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="delete_plant" value="1">
                                        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel-<?= $row['id'] ?>">Delete Plant</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete this plant?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Plant Modal -->
        <div class="modal fade" id="addPlantModal" tabindex="-1" aria-labelledby="addPlantModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPlantModalLabel">Add New Plant</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="add_plant" value="1">
                            <div class="mb-3">
                                <label for="species" class="form-label">Scientific Name (Species)<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="species" name="species" placeholder="e.g., Dipterocarpus bourdillonii" required>
                            </div>
                            <div class="mb-3">
                                <label for="common_name" class="form-label">Common Name</label>
                                <input type="text" class="form-control" id="common_name" name="common_name" placeholder="e.g. Chiratta anjili">
                            </div>
                            <div class="mb-3">
                                <label for="family" class="form-label">Family</label>
                                <input type="text" class="form-control" id="family" name="family" placeholder="e.g. Dipterocarpaceae">
                            </div>
                            <div class="mb-3">
                                <label for="genus" class="form-label">Genus</label>
                                <input type="text" class="form-control" id="genus" name="genus" placeholder="e.g. Dipterocarpus">
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Upload Image</label>
                                <input type="file" class="form-control-file" id="image" name="image">
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Upload Description (PDF, max 7MB)</label>
                                <input type="file" class="form-control-file" id="description" name="description" accept=".pdf">
                            </div>
                            <button type="submit" class="btn btn-primary">Add Plant</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>


</html>
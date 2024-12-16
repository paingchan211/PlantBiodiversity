<?php
session_name('paing_chan');
session_start();
if (empty($_SESSION['loggedin']) || empty($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

include_once 'main.php';
include_once 'head.php';

$plant_id = isset($_GET['id']) ? intval($_GET['id']) : null;

$selected_plant = null;
if ($plant_id) {
    $stmt = $conn->prepare("SELECT id, Scientific_Name, Common_Name, family, genus, species, plants_image, description FROM plant_table WHERE id = ?");
    $stmt->bind_param("i", $plant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $selected_plant = $result->fetch_assoc();
    $stmt->close();
}

if (!$selected_plant) {
    header("Location: contribute.php");
    exit();
}
?>

<body>
    <?php include_once "header.php"; ?>
    <main id="main-mt" class="min-vh-100 d-flex align-items-center justify-content-center">
        <div class="shadow-lg rounded p-4 w-100" id="plant-detail-container">
            <h1 class="h4 mb-4 fw-bold text-center">Plant Details</h1>

            <h2 class="h5 fw-bold">Scientific Name: <?= htmlspecialchars($selected_plant['Scientific_Name']) ?></h2>
            <p>Common Name: <?= htmlspecialchars($selected_plant['Common_Name']) ?></p>
            <p>Family: <?= htmlspecialchars($selected_plant['family']) ?></p>
            <p>Genus: <?= htmlspecialchars($selected_plant['genus']) ?></p>
            <p>Species: <?= htmlspecialchars($selected_plant['species']) ?></p>

            <div class="text-center">
                <h3 class="h6 fw-bold mb-3">Photo</h3>
                <img src="images/plants/<?= htmlspecialchars($selected_plant['plants_image']) ?>" class="img-fluid mb-3" alt="Herbarium Leaf" id="plant-detail-leaf-img">

            </div>

            <form action="pdf_generate.php" method="post">
                <input type="hidden" name="scientific_name" value="<?= htmlspecialchars($selected_plant['Scientific_Name']) ?>">
                <input type="hidden" name="common_name" value="<?= htmlspecialchars($selected_plant['Common_Name']) ?>">
                <input type="hidden" name="family" value="<?= htmlspecialchars($selected_plant['family']) ?>">
                <input type="hidden" name="genus" value="<?= htmlspecialchars($selected_plant['genus']) ?>">
                <input type="hidden" name="species" value="<?= htmlspecialchars($selected_plant['species']) ?>">
                <input type="hidden" name="plants_image" value="<?= htmlspecialchars($selected_plant['plants_image']) ?>">
                <div class="d-flex justify-content-around mt-3 gap-2">
                    <button type="submit" class="btn btn-primary ">Generate PDF</button>
                    <a href="pdf_download.php?id=<?= $plant_id ?>" class="btn btn-primary <?= empty($selected_plant['description']) ? 'disabled' : '' ?>">Download PDF</a>
                    <a href="contribute.php" class="btn btn-dark">Go Back</a>
                </div>
            </form>


        </div>
    </main>

    <?php include_once "footer.php"; ?>
</body>

</html>
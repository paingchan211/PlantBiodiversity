<?php
// Start session
include_once 'main.php';
session_name('paing_chan');
session_start();

$error = '';
$uploadSuccess = false;
$uploadedImagePath = '';
$plant_id = null;
$plantDetails = null;

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["plantPhoto"])) {
    $targetDir = "identify/";

    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            $error = "Failed to create upload directory";
        }
    }

    if (empty($error)) {
        $targetFile = $targetDir . basename($_FILES["plantPhoto"]["name"]);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = array('jpg', 'jpeg', 'png');

        if (!in_array($imageFileType, $allowedTypes)) {
            $error = "Sorry, only JPG, JPEG & PNG files are allowed.";
        } elseif ($_FILES["plantPhoto"]["error"] !== UPLOAD_ERR_OK) {
            $error = "Upload failed with error code: " . $_FILES["plantPhoto"]["error"];
        } elseif (move_uploaded_file($_FILES["plantPhoto"]["tmp_name"], $targetFile)) {
            $uploadedImagePath = $targetFile;
            $uploadSuccess = true;
        } else {
            $error = "Failed to upload file.";
        }
    }
}
?>

<?php include_once 'head.php'; ?>

<body class="bg-light">
    <?php include_once "header.php"; ?>

    <main id="main-mt">
        <div class="container mt-5">
            <h1 class="text-center mb-4">Plant Identification</h1>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow p-4">
                        <h2 class="text-center mb-4">Upload a Plant Photo</h2>

                        <?php if (!empty($error)) { ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php } ?>

                        <?php if ($uploadSuccess) { ?>
                            <div class="identification-results">
                                <div class="row mb-4">
                                    <!-- Uploaded Image -->
                                    <div class="col-md-6">
                                        <h5>Uploaded Image:</h5>
                                        <img src="<?php echo htmlspecialchars($uploadedImagePath); ?>"
                                            class="img-fluid rounded"
                                            alt="Uploaded Plant"
                                            id="uploadedImage">
                                    </div>

                                    <!-- Identification Results -->
                                    <div class="col-md-6">
                                        <h5>Identification Results:</h5>
                                        <div id="predictions" class="mb-3"></div>
                                        <div id="plant-info"></div>
                                    </div>
                                </div>

                                <!-- New Upload Button -->
                                <a href="identify.php" class="btn btn-primary">Upload Another Photo</a>
                            </div>
                        <?php } else { ?>
                            <form action="identify.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="plantPhoto" class="form-label">
                                        Choose a plant photo (JPG, JPEG, PNG)
                                    </label>
                                    <input type="file"
                                        id="plantPhoto"
                                        name="plantPhoto"
                                        class="form-control"
                                        required
                                        accept=".jpg,.jpeg,.png">
                                </div>
                                <button type="submit" class="btn btn-success w-100">Identify Plant</button>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include "footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@latest/dist/teachablemachine-image.min.js"></script>
    <script type="text/javascript">
        const URL = "./my_model/";
        let model;

        async function loadModel() {
            const modelURL = URL + "model.json";
            const metadataURL = URL + "metadata.json";
            return await tmImage.load(modelURL, metadataURL);
        }

        async function fetchPlantDetails(scientificName) {
            try {
                const response = await fetch(`get_plant_details.php?scientific_name=${encodeURIComponent(scientificName)}`);
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error fetching plant details:', error);
                return null;
            }
        }

        async function predictImage() {
            const img = document.getElementById('uploadedImage');
            const predictionsDiv = document.getElementById('predictions');
            const plantInfoDiv = document.getElementById('plant-info');

            if (!img) return;

            try {
                if (!model) {
                    model = await loadModel();
                }

                const predictions = await model.predict(img);

                // Sort predictions by probability
                const sortedPredictions = predictions.sort((a, b) => b.probability - a.probability);

                // Display predictions
                let predictionsHTML = '<div class="list-group mb-3">';
                let bestMatch = null;

                for (const prediction of sortedPredictions) {
                    if (prediction.probability > 0.15) { // Only show predictions with >15% confidence
                        const confidence = (prediction.probability * 100).toFixed(1);
                        const confidenceClass = confidence > 70 ? 'text-success' : 'text-muted';

                        predictionsHTML += `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>${prediction.className}</span>
                                    <span class="${confidenceClass}">${confidence}%</span>
                                </div>
                            </div>
                        `;

                        // Store best match if confidence is high enough
                        if (prediction === sortedPredictions[0] && prediction.probability > 0.70) {
                            bestMatch = prediction.className;
                        }
                    }
                }
                predictionsHTML += '</div>';
                predictionsDiv.innerHTML = predictionsHTML;

                if (bestMatch) {
                    // Fetch plant details using AJAX instead of page reload
                    const plantDetails = await fetchPlantDetails(bestMatch);

                    if (plantDetails) {
                        plantInfoDiv.innerHTML = `
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Plant Details:</h5>
                <table class="table table-borderless">
                    <tr>
                        <th>Scientific Name:</th>
                        <td><em>${plantDetails.Scientific_Name}</em></td>
                    </tr>
                    <tr>
                        <th>Family:</th>
                        <td>${plantDetails.Family || 'Not available'}</td>
                    </tr>
                    <tr>
                        <th>Genus:</th>
                        <td>${plantDetails.Genus || 'Not available'}</td>
                    </tr>
                    <tr>
                        <th>Species:</th>
                        <td>${plantDetails.Species || 'Not available'}</td>
                    </tr>
                </table>
                ${plantDetails.Description ? `
                    <div class="mt-3">
                        <h6>Description:</h6>
                    </div>
                ` : ''}
                ${plantDetails.id && plantDetails.Description ? `
                    <a href="pdf_download.php?id=${plantDetails.id}" 
                       class="btn btn-primary mt-3">
                        Download PDF Description
                    </a>
                ` : plantDetails.id && !plantDetails.Description ? `
                    <a href="pdf_download.php?id=${plantDetails.id}" 
                       class="btn btn-primary mt-3 disabled" 
                       aria-disabled="true">
                        Download PDF Description
                    </a>
                ` : ''}
            </div>
        </div>
    `;
                    } else {
                        plantInfoDiv.innerHTML = `
                            <div class="alert alert-warning">
                                Plant details not found in database.
                            </div>
                        `;
                    }
                } else {
                    plantInfoDiv.innerHTML = `
                        <div class="alert alert-info">
                            Confidence level too low for a definitive match. Please try uploading another image.
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error during prediction:', error);
                predictionsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        Error during plant identification. Please try again.
                    </div>
                `;
            }
        }

        // Initialize prediction when an image is uploaded
        if (document.getElementById('uploadedImage')) {
            predictImage();
        }
    </script>
</body>

</html>
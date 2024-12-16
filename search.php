<?php
session_name('paing_chan'); // name the session to prevent conflicts with other applications
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'main.php';

// Check if a search query is provided
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Display the search results
?>

<?php include_once 'head.php'; ?>

<body>
    <?php include_once 'header.php'; ?>

    <main class="container" id="main-mt">
        <div class="shadow-lg rounded p-4 w-100">
            <div class="text-center">
                <h1>Search Results</h1>
            </div>
            <div class="text-center">
                <a href="index.php" class="btn btn-primary mb-3">Back to Index</a>
            </div>
            <?php
            if ($query) {
                // Prepare SQL statement to search the database
                $stmt = $conn->prepare("
                    SELECT id, Scientific_Name, Common_Name, family, genus, plants_image 
                    FROM plant_table 
                    WHERE status = 'approved' AND (
                        LOWER(Scientific_Name) LIKE ? OR
                        LOWER(Common_Name) LIKE ? OR
                        LOWER(family) LIKE ? OR
                        LOWER(genus) LIKE ?
                    )
                ");
                $searchTerm = '%' . strtolower($query) . '%';
                $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
                $stmt->execute();
                $result = $stmt->get_result();

                // Display search results
                if ($result->num_rows > 0) {
                    echo '<div class="row">';
                    while ($row = $result->fetch_assoc()) {
                        echo '
                        <div class="col-md-4">
                            <div class="card rounded shadow-lg mb-4">
                                <img src="images/plants/' . htmlspecialchars($row['plants_image']) . '" class="card-img-top" alt="Plant Image" id="search-img">
                                <div class="card-body text-center">
                                    <h5 class="card-title">' . htmlspecialchars($row['Scientific_Name']) . '</h5>
                                    <p class="card-text">' . htmlspecialchars($row['Common_Name']) . '</p>
                                    <a href="plant_detail.php?id=' . htmlspecialchars($row['id']) . '" class="btn btn-primary">View Description</a>
                                </div>
                            </div>
                        </div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p>No results found for "' . htmlspecialchars($query) . '".</p>';
                }

                $stmt->close();
            } else {
                echo '<p>Please enter a search query.</p>';
            }
            $conn->close();
            ?>
        </div>
    </main>

    <?php include_once 'footer.php'; ?>
</body>

</html>
<?php
// get_plant_details.php
include_once 'main.php';
header('Content-Type: application/json');

function getPlantDetails($scientificName, $conn)
{
    $sql = "SELECT id, Scientific_Name, Family, Genus, Species, Description 
            FROM plant_table 
            WHERE Scientific_Name LIKE ? AND status = 'approved'";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$scientificName%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

if (isset($_GET['scientific_name'])) {
    $plantDetails = getPlantDetails($_GET['scientific_name'], $conn);
    echo json_encode($plantDetails);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Scientific name not provided']);
}

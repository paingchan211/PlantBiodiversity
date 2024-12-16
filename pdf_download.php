<?php
session_name('paing_chan');
session_start();
if (empty($_SESSION['loggedin']) || empty($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

include_once 'main.php';

$plant_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($plant_id) {
    $stmt = $conn->prepare("SELECT description FROM plant_table WHERE id = ?");
    $stmt->bind_param("i", $plant_id);
    $stmt->execute();
    $stmt->bind_result($description);
    $stmt->fetch();
    $stmt->close();

    $file_path = $description;

    if (file_exists($file_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit();
    } else {
        echo "Error: File not found.";
    }
} else {
    echo "Invalid request.";
}

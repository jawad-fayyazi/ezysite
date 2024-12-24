<?php
include('db_config.php');


$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$category = $_GET['category'] ?? '';
$templates = [];

if (!empty($category)) {
    $stmt = $conn->prepare("SELECT template_id, template_name, template_json, template_image, template_description, template_preview_link FROM templates_data WHERE template_category = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    $stmt->close();
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($templates);

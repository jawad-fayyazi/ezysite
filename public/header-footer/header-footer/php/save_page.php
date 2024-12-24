<?php
include('db_config.php');

// Create connection to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the POST request has JSON data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $projects_data = file_get_contents('php://input');


    // Extract project_id from the URL data
    $project_id = isset($_GET['project_id']) ? $_GET['project_id'] : null;
    $project_json = $projects_data;  // Keep the canvas data in JSON format

    if ($project_id) {
        // Check if this project_id already exists in the database
        $check_stmt = $conn->prepare("SELECT project_id FROM projects_data WHERE project_id = ?");
        $check_stmt->bind_param('i', $project_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            // If project_id exists, update the existing record
            $stmt = $conn->prepare("UPDATE projects_data SET project_json = ? WHERE project_id = ?");
            $stmt->bind_param('si', $project_json, $project_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['status' => 'success', 'message' => 'Canvas data updated successfully']);
        } else {
            // If project_id does not exist, insert a new record
            $stmt = $conn->prepare("INSERT INTO projects_data (project_id, project_json) VALUES (?, ?)");
            $stmt->bind_param('is', $project_id, $project_json);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['status' => 'success', 'message' => 'Canvas data saved successfully']);
        }

        $check_stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No project_id provided']);
    }


    // Close the connection
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
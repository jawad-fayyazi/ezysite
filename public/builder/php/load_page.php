<?php
include('db_config.php');

// Create connection to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if project_id is provided in the request
$project_id = isset($_GET['project_id']) ? $_GET['project_id'] : null;

if ($project_id) {
    // Prepare the SQL query to fetch data for the specific project_id
    $stmt = $conn->prepare("SELECT project_json FROM projects_data WHERE project_id = ? LIMIT 1");
    $stmt->bind_param("i", $project_id);

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if any data exists for the given project_id
    if ($result->num_rows > 0) {
        // Fetch the data and return as JSON
        $row = $result->fetch_assoc();
        echo $row['project_json'];
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No canvas data found for the specified project_id']);
    }

    // Close the statement
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'No project_id provided']);
}

// Close the connection
$conn->close();
?>
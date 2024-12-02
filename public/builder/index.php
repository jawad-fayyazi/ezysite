<?php
// include('php/db_config.php');

// // Create connection
// $conn = new mysqli($host, $username, $password, $dbname);

// // Check the connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

// // Step 1: Check if 'project_id' is set in the URL and is a valid integer
// if (!isset($_GET['project_id']) || empty($_GET['project_id']) || !filter_var($_GET['project_id'], FILTER_VALIDATE_INT)) {
//     // Redirect to 404 if 'project_id' is not set or invalid
//     header("Location: 404.php");
//     exit;
// }

// Step 2: Get the project_id from the URL
// $project_id = $_GET['project_id'];

// // Step 3: Query to fetch the project details from the database based on project_id
// $query = "SELECT project_name FROM projects_data WHERE project_id = ?";
// $stmt = $conn->prepare($query);
// $stmt->bind_param("i", $project_id); // Bind project_id
// $stmt->execute();
// $result = $stmt->get_result();

// // Step 4: Check if the project exists and has a name
// if ($result->num_rows === 0) {
//     // Redirect to 404 if no project found with the given project_id
//     header("Location: 404.php");
//     exit;
// }

// // Fetch the project name from the database
// $project_data = $result->fetch_assoc();
// $db_project_name = $project_data['project_name'];

// // Step 5: Check if 'project_name' is set in the URL
// if (!isset($_GET['project_name']) || empty($_GET['project_name'])) {
//     // Redirect to 404 if 'project_name' is not set
//     header("Location: 404.php");
//     exit;
// }

// // Step 6: Get the project_name from the URL
// $project_name_from_url = $_GET['project_name'];

// // Step 7: Case-insensitive check: Compare the project_name from URL and the database
// if (strtolower($project_name_from_url) !== strtolower($db_project_name)) {
//     // Redirect to 404 if the project_name does not match (case-insensitive)
//     header("Location: 404.php");
//     exit;
// }

// // If everything is valid, proceed with your logic
// $project_name = $project_name_from_url;
// $conn->close();

// Continue with your logic here, like displaying project information
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $project_name ?></title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Include GrapesJS -->
    <link href="css/grapes.min.css" rel="stylesheet" />
    <script src="js/grapes.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous"> -->

    <!-- Include GrapesJS Preset Webpage Plugin -->
    <script src="js/grapesjs-preset-webpage.min.js"></script>
    <script src="js/basic-blocks.js"></script>
    <script src="js/grapesjs-custom-code.js"></script>
    <script src="js/grapesjs-form-blocks.js"></script>
    <script src="https://unpkg.com/grapesjs-component-countdown@1.0.1"></script>
    <script src="https://unpkg.com/grapesjs-plugin-export@1.0.11"></script>
    <script src="https://unpkg.com/grapesjs-tabs@1.0.6"></script>
    <script src="https://unpkg.com/grapesjs-touch@0.1.1"></script>
    <script src="https://unpkg.com/grapesjs-parser-postcss@1.0.1"></script>
    <script src="https://unpkg.com/grapesjs-tooltip@0.1.7"></script>
    <script src="https://unpkg.com/grapesjs-tui-image-editor@0.1.3"></script>
    <script src="https://unpkg.com/grapesjs-typed@1.0.5"></script>
    <script src="https://unpkg.com/grapesjs-style-bg@2.0.1"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>






</head>

<body>
    <div class="app">

        <!-- <div id="navbar" class="gjs-one-bg gjs-two-color side-nav">
        <button id="collapse" class="btn-collapse"><i class="fa-solid fa-caret-left"></i></button>
        <div class="">
            <div class="">
                <button type="button" class="btn btn-outline-secondary btn-sm mx-2">
                    <i class="fa-solid fa-file-circle-plus"></i>Add Page
                </button>
                <ul class="pages">
                </ul>
            </div>
        </div>
    </div> -->
        <!-- <div id="pages-nav" class="pages-wrp gjs-two-color gjs-one-bg page-manager">
            <h3>Pages</h3>
            <div id="page-list"></div>
        </div>-->

        <div class="sidenav-collapse gjs-one-bg gjs-two-color">
            <i class="fa-solid fa-bars"> </i></div>
            <div id="side-bar-collpase" class="sidebar gjs-one-bg gjs-two-color">
                <h1 class="project-name"><?php 
                // echo $project_name 
                ?></h1>
                <div class="pages-collapse">
                    <i class="fa-solid fa-caret-down"></i>
                    Pages
                </div>
                <div id="page-list">
                    <button id="add-page" class="add-page-btn gjs-one-bg gjs-two-color gjs-four-color-h">
                        <i class="fa-solid fa-plus"></i> &nbsp; Add Page
                    </button>
                    <!-- Dynamic list of pages will go here -->
                    <ul id="pages-ul"></ul>
                </div>
            </div>
        </div>
        <div id="gjs"></div>
    


<script>
    var projectId = <?php 
    echo json_encode($project_id); 
    ?>;
    console.log("page id is ", projectId);
    // var projectName = <?php
    //  echo json_encode($project_name); 
     ?>;
</script>
    <script src="js/script.js"></script>
</body>

</html>
<?php
// live-content.php

// // Get the data from the AJAX request
// if (isset($_POST['pageData'])) {
//     $pageData = json_decode($_POST['pageData'], true);

//     // Directory to store the files
//     $dir = "C:/xampp/htdocs/website-deploy/"; // Specify the directory where the files will be saved

//     // Check if directory exists, if not create it
//     if (!file_exists($dir)) {
//         mkdir($dir, 0777, true);
//     }

//     // Iterate through the page data and create HTML and CSS files
//     foreach ($pageData as $page) {
//         $htmlContent = $page['html'];
//         $cssContent = $page['css'];

//         // HTML Boilerplate with a link to the corresponding CSS file
//         $htmlFileContent = "
// <!DOCTYPE html>
// <html lang='en'>
// <head>
//     <meta charset='UTF-8'>
//     <meta name='viewport' content='width=device-width, initial-scale=1.0'>
//     <title>{$page['name']}</title>
//     <link rel='stylesheet' href='{$page['name']}.css'>
// </head>
// <body>
//     {$htmlContent}
// </body>
// </html>";

//         // Create HTML file for the page
//         $htmlFilePath = $dir . '/' . $page['name'] . '.html';
//         file_put_contents($htmlFilePath, $htmlFileContent);

//         // Create CSS file for the page
//         $cssFilePath = $dir . '/' . $page['name'] . '.css';
//         file_put_contents($cssFilePath, $cssContent);
//     }

//     // Return success response to the client
//     echo json_encode(["status" => "success", "message" => "Files created successfully."]);


//     // Command to deploy the folder using Surge CLI
//     $command = "surge $dir --domain gratis-pigs.surge.sh"; // Change the domain as needed

//     // Execute the command to deploy
//     $output = shell_exec($command);

//     // Check the result
//     if ($output === null) {
//         echo json_encode(['message' => 'Error: Could not deploy site.']);
//     } else {
//         echo json_encode(['message' => 'Website is live at: gratis-pigs.surge.sh']);
//     }


// } else {
//     // Return error response if no data was sent
//     echo json_encode(["status" => "error", "message" => "No page data received."]);
// }










// final version 





// live-content.php

// Get the data from the AJAX request
if (isset($_POST['pageData'])) {
    $pageData = json_decode($_POST['pageData'], true);

    // Directory to store the files
    $dir = "C:/xampp/htdocs/website-deploy/"; // Specify the directory where the files will be saved

    // Check if directory exists, if not create it
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0777, true)) {
            echo json_encode(["status" => "error", "message" => "Failed to create directory."]);
            exit;
        }
    }

    // Iterate through the page data and create HTML and CSS files
    foreach ($pageData as $page) {
        $name = preg_replace('/[^a-zA-Z0-9-_]/', '', $page['name']); // Sanitize page name
        $htmlContent = $page['html'];
        $cssContent = $page['css'];

        // HTML Boilerplate with a link to the corresponding CSS file
        $htmlFileContent = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$name}</title>
    <link rel='stylesheet' href='{$name}.css'>
</head>
<body>
    {$htmlContent}
</body>
</html>";

        // Create HTML file for the page
        $htmlFilePath = $dir . '/' . $name . '.html';
        if (file_put_contents($htmlFilePath, $htmlFileContent) === false) {
            echo json_encode(["status" => "error", "message" => "Failed to create HTML file for {$name}."]);
            exit;
        }

        // Create CSS file for the page
        $cssFilePath = $dir . '/' . $name . '.css';
        if (file_put_contents($cssFilePath, $cssContent) === false) {
            echo json_encode(["status" => "error", "message" => "Failed to create CSS file for {$name}."]);
            exit;
        }
    }

    // Deploy using Surge CLI
    $command = escapeshellcmd("surge $dir --domain gratis-pigs.surge.sh"); // Sanitize the command
    $output = shell_exec($command);

    if ($output === null) {
        echo json_encode(["status" => "error", "message" => "Deployment failed."]);
    } else {
        echo json_encode(["status" => "success", "message" => "Website deployed successfully.", "output" => $output]);
    }
} else {
    // Return error response if no data was sent
    echo json_encode(["status" => "error", "message" => "No page data received."]);
}
?>
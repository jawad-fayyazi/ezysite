<?php
$project_name = isset($_GET['project_name']) ? $_GET['project_name'] : "Project Name";
$project_name = str_replace(' ', '-', $project_name);


// Function to create a new site on Netlify
function createNetlifySite($accessToken, $subdomain)
{
    $url = "https://api.netlify.com/api/v1/sites";
    $headers = [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ];
    $data = [
        "name" => $subdomain, // Specify custom subdomain
        "custom_domain" => null, // Leave null for default Netlify domain
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true); // Returns site details
}

// Function to deploy to Netlify using the command-line curl method
function deployToNetlifyWithCLI($accessToken, $siteId, $zipFilePath)
{
    // Prepare the command to execute
    $command = sprintf(
        'curl -H "Content-Type: application/zip" -H "Authorization: Bearer %s" --data-binary "@%s" https://api.netlify.com/api/v1/sites/%s/deploys',
        escapeshellarg($accessToken),
        escapeshellarg($zipFilePath),
        escapeshellarg($siteId)
    );

    // Execute the command and capture the output
    $output = shell_exec($command);

    // Return the output (for debugging purposes)
    return json_decode($output, true);
}

// Netlify token (use environment variables or a secure location for production)
$accessToken = "nfp_TWQ4uFSsYi3a4A3THbYCKkG52ftJkHERde18"; // Replace with your Netlify access token

// Check if zip file was uploaded
if (isset($_FILES['file'])) {
    $uploadedFile = $_FILES['file'];

    // Check for errors
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["status" => "error", "message" => "Error uploading the zip file."]);
        exit();
    }

    // Directory to store the zip temporarily
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $zipFilePath = $uploadDir . basename($uploadedFile['name']);
    move_uploaded_file($uploadedFile['tmp_name'], $zipFilePath);

    // Create a new Netlify site (if you want a unique subdomain for each user)
    $subdomain = $project_name . uniqid();
    $siteResponse = createNetlifySite($accessToken, $subdomain);

    if (isset($siteResponse['id'])) {
        $siteId = $siteResponse['id']; // Get the site ID
        // Deploy the website to Netlify using CLI
        $deployResponse = deployToNetlifyWithCLI($accessToken, $siteId, $zipFilePath);

        if (isset($deployResponse['state']) && $deployResponse['state'] === 'uploaded') {
            echo json_encode(["status" => "success", "url" => $deployResponse['url']]);
        } else {
            echo json_encode(["status" => "error", "message" => "Deployment failed: " . json_encode($deployResponse)]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Site creation failed: " . json_encode($siteResponse)]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No zip file received."]);
}
?>
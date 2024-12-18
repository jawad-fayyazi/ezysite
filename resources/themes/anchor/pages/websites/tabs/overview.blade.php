<?php
// Your JSON string
$jsonString = '{
  "assets": [],
  "styles": [],
  "pages": [
    {"name": "Page 1", "frames": [{"component": {"type": "wrapper","stylable": ["background","background-color","background-image","background-repeat","background-attachment","background-position","background-size"],"head": {"type": "head"},"docEl": {"tagName": "html"}},"id": "dvcAOgIFdvHHUsxj"}],"id": "QB03p13Ciwe75Qu8"},
    {"name": "Page 2", "frames": [{"component": {"type": "wrapper","stylable": ["background","background-color","background-image","background-repeat","background-attachment","background-position","background-size"],"head": {"type": "head"},"docEl": {"tagName": "html"}},"id": "OwZuLa9jgusubutN"}],"id": "sjFcXTx7LDR0PrHeD"},
    {"name": "Page 3", "frames": [{"component": {"type": "wrapper","stylable": ["background","background-color","background-image","background-repeat","background-attachment","background-position","background-size"],"head": {"type": "head"},"docEl": {"tagName": "html"}},"id": "rWkVv5esI8vamkAL"}],"id": "XziG9WQyunvKXkhBvZ"}
  ],
  "symbols": [],
  "dataSources": []
}';

// Parse JSON into an associative array
$data = json_decode($jsonString, true);

// Check if pages exist
if (isset($data['pages']) && is_array($data['pages'])) {
    // Count the number of pages
    $pageCount = count($data['pages']);
    echo "Number of pages: " . $pageCount . "\n";

    // Loop through the pages to get their names
    foreach ($data['pages'] as $page) {
        echo "Page name: " . $page['name'] . "\n";
    }
} else {
    echo "No pages found in the JSON.";
}

?>
<script>

  
</script>
let contentLoaded = false;

// Fetch the CSRF token from the meta tag
var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

const saveLoadUrls = {
  store: isHeader ? `/header/${projectId}/save` : `/footer/${projectId}/save`,
  load: isHeader ? `/header/${projectId}/load` : `/footer/${projectId}/load`,
};
const assetSyncUrl = {
  load: isHeader ? `/footer/${projectId}/load` : `/header/${projectId}/load`,
  save: isHeader ? `/footer/${projectId}/save` : `/header/${projectId}/save`,
};

const editor = grapesjs.init({
  container: "#gjs", // Container where the editor is rendered
  height: "100vh",
  width: "100%",
  fromElement: true,
  storageManager: {
    type: "remote",
    autosave: false,
    autoload: true,
    stepsBeforeSave: 1,
    options: {
      remote: {
        headers: {
          "X-CSRF-TOKEN": csrfToken,
        }, // Custom headers for the remote storage request
        urlStore: saveLoadUrls.store, // Endpoint URL where to store data project
        urlLoad: saveLoadUrls.load, // Endpoint URL where to load data project
      },
    },
  },
  assetManager: {
    upload: `/builder/assetmanager/${projectId}`, // Your local PHP endpoint
    uploadName: "files",
    autoAdd: true,
    headers: {
      "X-CSRF-TOKEN": csrfToken,
    },
  },
  plugins: [
    "grapesjs-preset-webpage", // Add first plugin
    "gjs-blocks-basic", // Add second plugin
    "grapesjs-plugin-forms",
    "grapesjs-custom-code", // Add third plugin
    "grapesjs-component-countdown",
    "grapesjs-tabs",
    "grapesjs-tooltip",
    "grapesjs-tui-image-editor",
    "grapesjs-typed",
  ],

  // Define their options here in a single `pluginsOpts` object
  pluginsOpts: {
    "grapesjs-preset-webpage": {}, // Options for the first plugin
    "gjs-blocks-basic": {}, // Options for the second plugin
    "grapesjs-custom-code": {}, // Options for the third plugin
    "grpaesjs-plugin-forms": {},
  },
  parser: {
    optionsHtml: {
      allowScripts: true,
      allowUnsafeAttr: true,
      allowUnsafeAttrValue: true,
    },
  },
});

let fileInput = null;
let errorSize = null;

// Create the status icon (initially hidden)
const statusIcon = document.createElement("i");
statusIcon.style.position = "absolute";
statusIcon.style.top = "10px";
statusIcon.style.right = "40px"; // Adjusted to avoid overlapping the close button
statusIcon.style.fontSize = "22px";
statusIcon.style.zIndex = "9999";
statusIcon.style.display = "none"; // Hidden by default

// Create the error message container
const errorMsg = document.createElement("div");
errorMsg.style.position = "absolute";
errorMsg.style.top = "40px"; // Position below the status icon
errorMsg.style.right = "20px";
errorMsg.style.padding = "8px 12px";
errorMsg.style.backgroundColor = "#f8d7da"; // Light red background
errorMsg.style.color = "#721c24"; // Dark red text
errorMsg.style.border = "1px solid #f5c6cb";
errorMsg.style.borderRadius = "4px";
errorMsg.style.fontSize = "14px";
errorMsg.style.zIndex = "9999";
errorMsg.style.display = "none"; // Hidden by default

// Trigger when Asset Manager opens
editor.on("asset:open", function () {
  const assetManager = document.querySelector(".gjs-am-assets");

  // Add the status icon and error message if not already added
  if (assetManager && !assetManager.contains(statusIcon)) {
    assetManager.appendChild(statusIcon);
  }
  if (assetManager && !assetManager.contains(errorMsg)) {
    assetManager.appendChild(errorMsg);
  }

  // Get the file input for validation
  fileInput = document.getElementById("gjs-am-uploadFile");
  if (fileInput !== null) {
    const MAX_FILE_SIZE = 1 * 1024 * 1024; // 1MB limit

    // File size validation
    fileInput.addEventListener("change", function (event) {
      const file = event.target.files[0];
      if (file && file.size > MAX_FILE_SIZE) {
        errorSize = "size";
        event.target.value = ""; // Clear the input field
      }
    });
  }
});

// Show spinner when upload starts
editor.on("asset:upload:start", () => {
  statusIcon.className = "fas fa-spinner fa-spin"; // Loading spinner
  statusIcon.style.color = "#333"; // Default gray color
  statusIcon.style.display = "block";
  errorMsg.style.display = "none"; // Hide error message if visible
});

// Show green tick on upload success
editor.on("asset:upload:end", () => {
  saveContent();
  statusIcon.className = "fas fa-check-circle"; // Green tick icon
  statusIcon.style.color = "#28a745"; // Green color
  setTimeout(() => {
    statusIcon.style.display = "none"; // Hide after 2 seconds
  }, 2000);
});

// Show red error icon and message on upload error
editor.on("asset:upload:error", (error) => {
  let errorMessage = "An error occurred while uploading the file.";

  // If errorSize is 'size', show size-related error message
  if (errorSize === "size") {
    errorMessage =
      "Image is too large. Please upload an image smaller than 1MB.";
    errorSize = null;
  }
  // If error response has a body, show that specific error message
  else if (error) {
    errorMessage = error; // Get the error message from the backend response
  }

  // Show the error message
  showError(errorMessage);
});

editor.on("asset:remove", (asset) => {
  // Get the asset URL or ID to identify which image to delete
  const imageUrl = asset.get("src"); // Assuming 'src' contains the image URL or ID

  // Make a request to delete the image on the server
  deleteImageFromServer(imageUrl);
  saveContent();
});

// Function to make an API request to the server to delete the image
function deleteImageFromServer(imageUrl) {
  // Extract the relative path after /storage/
  const filePath = imageUrl.replace(
    "https://ezysite.wpengineers.com//storage/",
    ""
  );

  fetch(`/builder/assetmanager/${projectId}`, {
    method: "DELETE", // HTTP DELETE method
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content, // Laravel CSRF token
    },
    body: JSON.stringify({ imagePath: filePath }), // Send the relative image path to the server
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        saveContent();
        showSuccess('Image deleted successfully');
                syncDeletedAssets(
                  imageUrl,
                  `/builder/${projectId}`,
                  `/builder/${projectId}`,
                );
                syncDeletedAssets(
                  imageUrl,
                  assetSyncUrl.load,
                  assetSyncUrl.save,
                );
      } else {
showSuccess("Image deleted successfully");
syncDeletedAssets(imageUrl, `/builder/${projectId}`, `/builder/${projectId}`);
syncDeletedAssets(imageUrl, assetSyncUrl.load, assetSyncUrl.save);      }
    })
    .catch((error) => {
      console.log("Error:", error);
showSuccess("Image deleted successfully");
syncDeletedAssets(imageUrl, `/builder/${projectId}`, `/builder/${projectId}`);
syncDeletedAssets(imageUrl, assetSyncUrl.load, assetSyncUrl.save);    });
}

// Reusable function to show error messages
function showError(message) {
  statusIcon.className = "fas fa-times-circle"; // Red error icon
  statusIcon.style.color = "#dc3545"; // Red color
  statusIcon.style.display = "block";

  errorMsg.textContent = message;
  errorMsg.style.display = "block";

  // Hide after 3 seconds
  setTimeout(() => {
    statusIcon.style.display = "none";
    errorMsg.style.display = "none";
  }, 3000);
}
function showSuccess(message) {
  statusIcon.className = "fas fa-check-circle"; // ✅ Success icon
  statusIcon.style.color = "#28a745"; // ✅ Green color
  statusIcon.style.display = "block";

  // ✅ Apply green styling for success message
  errorMsg.style.backgroundColor = "#d4edda"; // Light green background
  errorMsg.style.color = "#155724"; // Dark green text
  errorMsg.style.border = "1px solid #c3e6cb"; // Green border

  errorMsg.textContent = message;
  errorMsg.style.display = "block";

  // ✅ Revert back to default after 3 seconds
  setTimeout(() => {
    statusIcon.style.display = "none";
    errorMsg.style.display = "none";

    // Revert to default (error) styling
    errorMsg.style.backgroundColor = "#f8d7da"; // Light red background
    errorMsg.style.color = "#721c24"; // Dark red text
    errorMsg.style.border = "1px solid #f5c6cb"; // Red border
  }, 3000);
}




async function syncDeletedAssets(
  deletedAssetSrc,
  footerJsonUrl,
  footerJsonUrlSave
) {
  try {
    // ✅ Fetch footer JSON
    const footerResponse = await fetch(footerJsonUrl);
    const footerData = await footerResponse.json();

    // ✅ Filter out the deleted asset from the footer assets
    footerData.assets = footerData.assets.filter(
      (asset) => asset.src !== deletedAssetSrc
    );

    // ✅ Save the updated project and footer data
    await saveUpdatedJson(footerJsonUrlSave, footerData); // Save footer data

    console.log(
      `✅ Deleted asset '${deletedAssetSrc}' synced across all builders.`
    );
  } catch (error) {
    console.error("❌ Error syncing deleted assets:", error);
  }
}

// ✅ Function to save updated JSON
async function saveUpdatedJson(url, data) {
  await fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": csrfToken, // Laravel CSRF token
    },
    body: JSON.stringify(data),
  });
}




editor.on("load", async function () {
  async function syncHeaderFooterAssets(builder, headerJsonUrl, footerJsonUrl) {
    try {
      // ✅ Get the current project data directly from the builder (no extra request)
      const projectData = editor.getProjectData();

      // ✅ Convert project assets into a Set for comparison
      const projectAssets = new Set(
        (projectData.assets || []).map((asset) => asset.src)
      );

      // ✅ Fetch Header and Footer JSON data
      const [headerResponse, footerResponse] = await Promise.all([
        fetch(headerJsonUrl),
        fetch(footerJsonUrl),
      ]);

      const [headerData, footerData] = await Promise.all([
        headerResponse.json(),
        footerResponse.json(),
      ]);

      // ✅ Combine header and footer assets
      const headerFooterAssets = [
        ...(headerData.assets || []),
        ...(footerData.assets || []),
      ];

      // ✅ Find missing assets from header/footer that are NOT in project
      const missingAssets = headerFooterAssets.filter(
        (asset) => !projectAssets.has(asset.src)
      );

      // ✅ Loop through missing assets and add them to the builder
      missingAssets.forEach((asset) => {
        builder.AssetManager.add({
          src: asset.src,
          width: asset.width || "auto",
          height: asset.height || "auto",
        });
      });

      console.log("✅ Missing header/footer assets added:", missingAssets);
    } catch (error) {
      console.error("❌ Error syncing header and footer assets:", error);
    }
  }

  // Call function with header/footer URLs
  syncHeaderFooterAssets(
    editor,
    `/builder/${projectId}`,
    assetSyncUrl.load,
  );
});





editor.on("load", function () {
  const blockManager = editor.BlockManager;

  // Block IDs
  //const typedBlockId = "typed"; // Block ID for "typed" block
  // const tabsBlockId = "tabs"; // Block ID for "tabs" block
  //const extraCategory = "Extra"; // Desired category name
  const extraBlocks = ["typed", "tabs"];
  // Function to move a block to a new category
  function moveBlockToCategory(blockId, category) {
    const block = blockManager.get(blockId);

    if (block) {
      // Remove the block from the Block Manager
      blockManager.remove(blockId);

      // Re-add the block with the new category
      blockManager.add(blockId, {
        ...block.attributes, // Retain original properties
        category: category, // Assign to the new category
      });
    }
  }

  // Move 'typed' and 'tabs' blocks to the 'Extra' category
  // moveBlockToCategory(typedBlockId, extraCategory);
  // moveBlockToCategory(tabsBlockId, extraCategory);
  extraBlocks.forEach((block) => {
    moveBlockToCategory(block, "Extra");
  });
});

// Add save button to the 'options' panel
editor.Panels.addButton("options", {
  id: "save-button",
  className: "fa fa-solid fa-floppy-disk save-icon",
  command: "saveContent",
  attributes: { title: "Save HTML and CSS" },
  label: `<span id="red-dot" style="display: none; position: absolute; top: 0; right: 0; width: 10px; height: 10px; border-radius: 50%; background-color: red;"></span>`,
});



function saveContent() {
  startLoadingAnimation();
  editor.store();

  // Get the current page's HTML and CSS
  const htmlContent = editor.getHtml();
  const cssContent = editor.getCss();

  // Prepare data to send to the backend
  const data = {
    html: htmlContent,
    css: cssContent,
    project_id: projectId,
  };
  // Dynamically set the URL for the fetch request
  const fetchUrl = isHeader
    ? `/header/data`
    : `/footer/data`;

  // Send data to backend (using Fetch API)
  fetch(fetchUrl, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content, // Laravel CSRF token
    },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.success) {
        setTimeout(() => {
          showSuccessAnimation();
          console.log("Saved!!!");
        }, 1500);
      } else {
        errorAnimation();
        // showErrorAnimation(); // Error feedback
        console.error("Save failed:", result.message);
      }
    })
    .catch((error) => {
      errorAnimation();
      // showErrorAnimation(); // Handle network errors
      console.error("Error saving content:", error);
    });
}

function startLoadingAnimation() {
  const button = editor.Panels.getButton("options", "save-button");
  button.set("className", "fa fa-spinner fa-spin loading-icon"); // Add spinner class
}

function showSuccessAnimation() {
  const button = editor.Panels.getButton("options", "save-button");
  button.set("className", "fa fa-check checkmark-icon"); // Change icon to checkmark
  hidePendingChanges();
  // Reset back to original icon after 1.5 seconds
  setTimeout(() => {
    button.set("className", "fa fa-solid fa-floppy-disk save-icon"); // Original save icon
  }, 1500);
}
function loadContent() {
  editor.load();
}
let saveTimeout;


editor.Commands.add("saveContent", {
  run: function () {
    clearTimeout(saveTimeout);
    saveContent();
  },
});




editor.on("update", function () {
  clearTimeout(saveTimeout);
  saveTimeout = setTimeout(saveContent, 5000); // Save after 5-second delay
});

editor.on("update", function () {
  showPendingChanges();
});

let is_pending_flag = true;
// Function to show red dot when there are unsaved changes
function showPendingChanges() {
  // ✅ Get the save button
  const button = editor.Panels.getButton("options", "save-button");

  // ✅ Check if the button exists and has the "fa-floppy-disk" class
  if (!button || !button.get("className").includes("fa-floppy-disk")) {
    console.log("🔄 Save button not ready, retrying in 3 seconds...");

    // ✅ Retry function after 3 seconds
    setTimeout(showPendingChanges, 1500);
    return; // Exit function to prevent showing red dot
  }

  // ✅ If save button has "fa-floppy-disk", show the red dot
  if (is_pending_flag) {
    const redDot = document.getElementById("red-dot");
    if (redDot) redDot.style.display = "inline"; // Show the red dot

    is_pending_flag = false;
  }
}

// Function to hide red dot once changes are saved
function hidePendingChanges() {
  const redDot = document.getElementById("red-dot");
  const projectName = document.getElementById("project-name");
  if (redDot) redDot.style.display = "none"; // Hide the red dot
  is_pending_flag = true;
}

function errorAnimation() {
  const button = editor.Panels.getButton("options", "save-button");
  button.set(
    "className",
    "fa fa-solid fa-circle-exclamation fa-beat-fade error-icon"
  ); // Change icon to checkmark

  // Reset back to original icon after 1.5 seconds
  setTimeout(() => {
    button.set("className", "fa fa-solid fa-floppy-disk save-icon"); // Original save icon
    showPendingChanges();
  }, 4500);
}
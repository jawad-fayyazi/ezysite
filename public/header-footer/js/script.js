let contentLoaded = false;

// Fetch the CSRF token from the meta tag
var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

const saveLoadUrls = {
  store: isHeader ? `/header/${projectId}/save` : `/footer/${projectId}/save`,
  load: isHeader ? `/header/${projectId}/load` : `/footer/${projectId}/load`,
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
        // showErrorAnimation(); // Error feedback
        console.error("Save failed:", result.message);
      }
    })
    .catch((error) => {
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

  // Reset back to original icon after 1.5 seconds
  setTimeout(() => {
    button.set("className", "fa fa-solid fa-floppy-disk save-icon"); // Original save icon
  }, 1500);
}

function loadContent() {
  editor.load();
}

editor.Commands.add("saveContent", {
  run: function () {
    saveContent();
  },
});

let saveTimeout;
editor.on("update", function () {
  clearTimeout(saveTimeout);
  saveTimeout = setTimeout(saveContent, 5000); // Save after 5-second delay
});

editor.on("update", function () {
  startLoadingAnimation();
});
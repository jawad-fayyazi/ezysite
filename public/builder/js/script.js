let contentLoaded = false;

// Fetch the CSRF token from the meta tag
var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


const editor = grapesjs.init({
  container: "#gjs", // Container where the editor is rendered
  height: "100vh",
  width: "90%",
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
        urlStore: `/builder/${projectId}`, // Endpoint URL where to store data project
        urlLoad: `/builder/${projectId}`, // Endpoint URL where to load data project
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
    "grapesjs-plugin-export",
  ],

  // Define their options here in a single `pluginsOpts` object
  pluginsOpts: {
    "grapesjs-preset-webpage": {}, // Options for the first plugin
    "gjs-blocks-basic": {}, // Options for the second plugin
    "grapesjs-custom-code": {}, // Options for the third plugin
    "grpaesjs-plugin-forms": {},
    "grapesjs-plugin-export": {},
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
// Use a while loop to keep checking until the fileInput is found
editor.on("asset:open", function () {
  fileInput = document.getElementById("gjs-am-uploadFile");
  if (fileInput !== null) {
    const MAX_FILE_SIZE = 1 * 1024 * 1024; // 1MB limit

    // Add the change event listener to the file input
    fileInput.addEventListener("change", function (event) {
      const file = event.target.files[0];

      if (file && file.size > MAX_FILE_SIZE) {
        alert("Image is too large. Please upload an image smaller than 1MB.");
        event.target.value = ""; // Clear the input field
      }
    });

    // Once the fileInput is found and the listener is added, stop checking
    fileInput = null; // Reset to stop the while loop
  }
});


editor.on("asset:upload:error", (error) => {
  // Handle error during asset upload
  console.error(error);
  alert(error);
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

// Add save button to the 'options' panel
editor.Panels.addButton("options", {
  id: "live-content",
  className: "fa fa-solid fa-upload",
  command: "liveContent",
  attributes: { title: "Live this Page" },
});




let editedPages = {}; // Map to track pages opened and their data



// Track data for the current page before switching
const trackAndSwitchPage = (newPageId) => {
  const activePageId = pagesApi.getSelected()?.id;
  const html = editor.getHtml();
  const css = editor.getCss();
  updatePageData(activePageId, html, css); // Save the current page's data before switching
  pagesApi.select(newPageId); // Switch to the new page
  renderPages(); // Update the UI for the selected page
};


// Function to update page data in the object
const updatePageData = (pageId, html, css) => {
  if (editedPages[pageId]) {
    // Update existing page data
    editedPages[pageId].html = html;
    editedPages[pageId].css = css;
  } else {
    // Add new page data
    editedPages[pageId] = { html, css };
  }
};


// Event listener for editor updates
editor.on("update", () => {
  const activePageId = pagesApi.getSelected()?.id;
  if (activePageId) {
    const htmlContent = editor.getHtml();
    const cssContent = editor.getCss();
    updatePageData(activePageId, htmlContent, cssContent);
  }
});


function saveContent() {
  startLoadingAnimation();
  editor.store();

  // Check if no page data exists in the editedPages object
  if (Object.keys(editedPages).length === 0) {
    // If no pages exist in the object, add the current page's data

    const currentPageHtml = editor.getHtml();
    const currentPageCss = editor.getCss();
    const currentPageId = pagesApi.getSelected()?.id;
    editedPages[currentPageId] = {
      html: currentPageHtml,
      css: currentPageCss,
    };
  }

  // Prepare data for all tracked pages
  const pagesData = Object.entries(editedPages).map(([pageId, data]) => ({
    pageId,
    html: data.html,
    css: data.css,
  }));

  const data = {
    websiteId: projectId,
    pages: pagesData,
  };

  // Send the data to the backend
  fetch("/pages/all-data", {
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
          console.log("Pages data saved successfully!");
          editedPages = {}; // Clear the map after successful save
        }, 1500);
      } else {
        errorAnimation();
        console.error("Save failed:", result.message);
        console.log("Failed pages:", result.failedPages);
      }
    })
    .catch((error) => {
      errorAnimation();
      console.error("Error saving pages data:", error);
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


let originalProjectName = document.getElementById("project-name").innerText;
let is_pending_flag = true;
// Function to show red dot when there are unsaved changes
function showPendingChanges() {
  if (is_pending_flag) {
    const redDot = document.getElementById("red-dot");
    const projectName = document.getElementById("project-name");
    if (redDot) redDot.style.display = "inline"; // Show the red dot
    if (projectName) projectName.innerText = originalProjectName + "*";
    is_pending_flag = false;
  }
}


// Function to hide red dot once changes are saved
function hidePendingChanges() {
  const redDot = document.getElementById("red-dot");
  const projectName = document.getElementById("project-name");
  if (redDot) redDot.style.display = "none"; // Hide the red dot
  if (projectName) projectName.innerText = originalProjectName;
  is_pending_flag = true;
}



function errorAnimation() {
  
    const button = editor.Panels.getButton("options", "save-button");
    button.set("className", "fa fa-solid fa-circle-exclamation fa-beat-fade error-icon"); // Change icon to checkmark

    // Reset back to original icon after 1.5 seconds
    setTimeout(() => {
      button.set("className", "fa fa-solid fa-floppy-disk save-icon"); // Original save icon
      showPendingChanges();
    }, 4500);
  

}



// Get the Pages API
const pagesApi = editor.Pages;

// Add Page Button
const addPageBtn = document.getElementById("add-page");
const pageList = document.getElementById("pages-ul");

// Function to render the pages list
// Function to render the pages list
// Function to render the pages list
// Function to render the pages list
function renderPages() {
  pageList.innerHTML = ""; // Clear current list

  const pages = pagesApi.getAll(); // Get all pages
  const activePageId = pagesApi.getSelected()?.id; // Get the ID of the active/selected page

  // If there are pages, loop through and add them to the list
  if (pages.length > 0) {
    pages.forEach((page) => {
      const li = document.createElement("li");
      li.className = "page-item gjs-one-bg gjs-two-color gjs-four-color-h";
      const isActive = activePageId && page.id === activePageId;
      li.innerHTML = `
        <span class="page-name-container">
          <span class="page-name" contenteditable="false">${page.getName()}</span>
          ${isActive ? '<span class="active-dot"></span>' : ""}
        </span>
        <ul class="sub-menu">
          <li class="menu-item edit-page gjs-one-bg gjs-two-color gjs-four-color-h" data-id="${
            page.id
          }">Edit</li>
          <li class="menu-item view-page gjs-one-bg gjs-two-color gjs-four-color-h" data-id="${
            page.id
          }">View</li>
          <li class="menu-item rename-page gjs-one-bg gjs-two-color gjs-four-color-h" data-id="${
            page.id
          }">Rename</li>
          <li class="menu-item delete-page gjs-one-bg gjs-two-color gjs-four-color-h" data-id="${
            page.id
          }">Delete</li>
        </ul>
      `;
      pageList.appendChild(li);

      // Initially hide the sub-menu
      const subMenu = li.querySelector(".sub-menu");
      subMenu.style.display = "none"; // Initially hide the sub-menu

      // Make the entire li clickable to select the page
      li.addEventListener("click", function (e) {
        e.stopPropagation(); // Prevent event bubbling

        // Close other open sub-menus
        document.querySelectorAll(".sub-menu").forEach((menu) => {
          if (menu !== subMenu && menu.style.display === "block") {
            menu.style.animation = "slideUp 0.2s ease-in forwards";
            setTimeout(() => {
              menu.style.display = "none"; // Hide after slide-up animation
              menu.style.animation = ""; // Reset animation
            }, 200); // Match the duration of slide-up animation
          }
        });

        // Toggle current sub-menu
        if (subMenu.style.display === "none") {
          subMenu.style.display = "block"; // Show before animation starts
          subMenu.style.animation = "slideDown 0.2s ease-out forwards";
        } else {
          subMenu.style.animation = "slideUp 0.2s ease-in forwards";
          setTimeout(() => {
            subMenu.style.display = "none"; // Hide after slide-up animation
            subMenu.style.animation = ""; // Reset animation
          }, 200); // Match the duration of slide-up animation
        }
      });

      // Handle "EDIT" click
      const editPageBtn = li.querySelector(".edit-page");
      editPageBtn.addEventListener("click", (e) => {
        e.stopPropagation(); // Prevent li click from triggering page selection
        const pid = page.id;
        if (pid) { 
          trackAndSwitchPage(pid); 
          subMenu.style.display = "none"; // Close sub-menu after action
          renderPages();
        }
        else {
          renderPages();
        }
      });

      // Handle "VIEW" click (currently does nothing)
      const viewPageBtn = li.querySelector(".view-page");
      viewPageBtn.addEventListener("click", (e) => {
        e.stopPropagation(); // Prevent li click from triggering page selection
        console.log("View Page clicked for: " + page.getName());
        subMenu.style.display = "none"; // Close sub-menu after action
      });

      // Handle "RENAME" click
      const renamePageBtn = li.querySelector(".rename-page");
      renamePageBtn.addEventListener("click", (e) => {
        e.stopPropagation(); // Prevent li click from triggering page selection
        const pageNameElement = li.querySelector(".page-name");

        // Enable content editing
        pageNameElement.contentEditable = "true";
        pageNameElement.focus();

        // Move the cursor to the end of the text
        const range = document.createRange();
        const selection = window.getSelection();
        range.selectNodeContents(pageNameElement);
        range.collapse(false); // Collapse the range to the end
        selection.removeAllRanges();
        selection.addRange(range);

        subMenu.style.display = "none"; // Close sub-menu after action

        // Add event listener to save on blur
        pageNameElement.addEventListener("blur", function () {
          this.contentEditable = "false"; // Disable contenteditable
          const newPageName = this.textContent.trim();
          if (newPageName) {
            page.setName(newPageName); // Update the page name

            // Send the updated name to the backend
            const pageId = page.id;
            const updatedPageData = {
              name: newPageName,
              page_id: pageId,
              websiteId: projectId,
            };

            // Send the updated page name to the backend
            fetch(`/pages/rename/${pageId}`, {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                  .querySelector('meta[name="csrf-token"]')
                  .getAttribute("content"), // CSRF token
              },
              body: JSON.stringify(updatedPageData),
            })
              .then((response) => response.json())
              .then((data) => {
                if (data.success) {
                  console.log("Page renamed successfully:", data.page);
                  saveContent();
                  renderPages(); // Re-render the list of pages with updated names
                } else {
                  console.error("Error renaming page:", data.error);
                }
              })
              .catch((error) => console.error("Error:", error));
          } else {
            alert("Page name cannot be empty."); // Show an alert if the name is empty
            renderPages();
            return;
          }
        });

        // Add event listener to save on Enter key
        pageNameElement.addEventListener("keydown", function (e) {
          if (e.key === "Enter") {
            e.preventDefault(); // Prevent new line
            this.blur(); // Trigger blur event to save changes
          }
        });
      });

      // Handle "DELETE" click
      const deletePageBtn = li.querySelector(".delete-page");
      deletePageBtn.addEventListener("click", (e) => {
        e.stopPropagation(); // Prevent li click from triggering page selection
        const pageId = e.target.dataset.id; // Get the page ID from the data attribute
        const page = pagesApi.getAll().find((p) => p.id === pageId); // Find the page by ID
        const pageName = page ? page.getName() : "Unknown Page"; // Get the page name, fallback if not found

        // Check if it's the only page left
        if (pagesApi.getAll().length === 1) {
          alert("You cannot delete this page. It is the only page left.");
          renderPages();
          return; // Prevent deletion if it's the last page
        }

        // Ask for confirmation
        if (
          confirm(`Are you sure you want to delete the page: "${pageName}"?`)
        ) {
          const reqData = {
            websiteId: projectId,
          };
          // Send DELETE request to backend to remove the page from the database
          fetch(`/pages/${pageId}`, {
            method: "DELETE",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"), // CSRF token
            },
            body: JSON.stringify(reqData),
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                console.log("Page deleted successfully:", data.page);
                pagesApi.remove(pageId); // Delete the page locally using GrapesJS API
                renderPages(); // Re-render the list of pages
                saveContent();
              } else {
                console.error("Error deleting page:", data.error);
              }
            })
            .catch((error) => console.error("Error:", error));
        }

        subMenu.style.display = "none"; // Close sub-menu after action
      });
    });
  } else {
    const noPagesMessage = document.createElement("li");
    noPagesMessage.className =
      "page-item gjs-one-bg gjs-two-color gjs-four-color-h";
    noPagesMessage.textContent = "No pages available.";
    pageList.appendChild(noPagesMessage);
  }
}

// Add new page on button click
addPageBtn.addEventListener("click", () => {
  const newPage = pagesApi.add({
    name: `Page ${pagesApi.getAll().length + 1}`,
  });
  console.log(newPage.id);

  // Now send the new page data to the backend to update the database
  const pageData = {
    name: newPage.getName(), // Get the page's name
    page_id: newPage.id, // Page Id
    website_id: projectId, // Assuming you have a website_id field available
  };

  fetch("/pages", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content"), // CSRF token
    },
    body: JSON.stringify(pageData),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        console.log("Page added successfully:", data.page);
        renderPages(); // Update the list
        saveContent();
      } else {
        console.error("Error adding page:", data.error);
      }
    })
    .catch((error) => console.error("Error:", error));

  pagesApi.select(newPage.id); // Switch to the new page
  renderPages(); // Update the list
});

editor.on("load", function () {
  // Now pages are loaded, call renderPages
  renderPages();
});

// Select the elements
const pagesCollapse = document.querySelector(".pages-collapse");
const pageListContainer = document.getElementById("page-list");
const icon = pagesCollapse.querySelector("i");

// Add click event listener to the pages-collapse div
pagesCollapse.addEventListener("click", function () {
  // Toggle the visibility of the page list
  pageListContainer.classList.toggle("hidden");

  // Toggle the icon between caret-down and caret-up
  if (pageListContainer.classList.contains("hidden")) {
    icon.classList.remove("fa-caret-down");
    icon.classList.add("fa-caret-right");
  } else {
    icon.classList.remove("fa-caret-right");
    icon.classList.add("fa-caret-down");
  }
});

// const previewButton = editor.Panels.getButton('options', 'preview'); // 'views' is the panel ID, 'preview' is the button ID

// Select the elements
const sidenavCollapse = document.querySelector(".sidenav-collapse");
const sideBar = document.getElementById("side-bar-collpase");
const iconBar = sidenavCollapse.querySelector("i");
const gjsResize = document.getElementById("gjs");
const panelButtonResize = document.querySelector(".gjs-pn-devices-c");

// Add click event listener to the pages-collapse div
iconBar.addEventListener("click", function () {
  // Toggle the visibility of the page list
  sideBar.classList.toggle("hidden");

  // Toggle the icon between caret-down and caret-up
  if (sideBar.classList.contains("hidden")) {
    gjsResize.classList.add("resize");
    gjsResize.style.width = "100%";
    panelButtonResize.classList.add("panel-button-resize");
    sidenavCollapse.classList.add("close");
  } else {
    sidenavCollapse.classList.remove("close");
    gjsResize.classList.remove("resize");
    panelButtonResize.classList.remove("panel-button-resize");
    gjsResize.style.width = "90%";
  }
});

// Assuming you have the GrapesJS editor instance available as `editor`
editor.on("load", () => {
  // Wait for GrapesJS to load
  const logo = "Ezysite"; // The project name or any dynamic content

  // Find the button container in the panel (gjs-pn-button)
  const buttons = document.querySelectorAll(".gjs-pn-buttons"); // Assuming there's one button, or use a more specific selector
  const panelButtonDiv = buttons[1]; // Change the index to target other buttons (0-based index)

  if (panelButtonDiv) {
    // Create the span element
    const span = document.createElement("span");
    span.classList.add("logo"); // Optionally add a class to style it
    span.textContent = logo; // Set the content of the span

    // Append the span inside the button div
    panelButtonDiv.appendChild(span);
  }
});



editor.Commands.add("liveContent", {
  run(editor) {
    const pages = editor.Pages.getAll(); // Get all pages
    const zip = new JSZip(); // Initialize the ZIP file
    const imageMap = new Map(); // To track already processed images

    const extractImages = (htmlContent) => {
      // Regular expression to match base64 encoded images in <img> src
      const imgRegex =
        /<img[^>]+src="data:image\/(png|jpeg|jpg|gif);base64,([^"]+)"/g;
      const images = [];
      let match;

      while ((match = imgRegex.exec(htmlContent)) !== null) {
        // match[2] contains the base64 string, match[1] is the image format (e.g., png)
        images.push({ base64: match[2], format: match[1] });
      }

      return images;
    };

    const saveBase64Image = (base64Data, format, imageName) => {
      // Convert base64 string to binary data and save it as a PNG (or the specified format)
      const binary = atob(base64Data);
      const len = binary.length;
      const arrayBuffer = new ArrayBuffer(len);
      const uintArray = new Uint8Array(arrayBuffer);

      for (let i = 0; i < len; i++) {
        uintArray[i] = binary.charCodeAt(i);
      }

      return new Blob([uintArray], { type: `image/${format}` }); // Create a Blob for the image
    };

    const fetchPageContent = (page) => {
      return new Promise((resolve) => {
        editor.Pages.select(page); // Switch to the page

        setTimeout(() => {
          let html = editor.getHtml(); // Get the HTML
          const css = editor.getCss(); // Get the CSS
          const images = extractImages(html); // Extract base64 images

          // Process images and add them to the ZIP
          images.forEach((image, index) => {
            const imageKey = `${image.format}:${image.base64}`; // Unique key for the image based on its content
            let imageName = imageMap.get(imageKey);

            if (!imageName) {
              // If the image hasn't been processed yet, create a new image file name
              imageName = `image-${Date.now()}-${index + 1}`;
              const blob = saveBase64Image(
                image.base64,
                image.format,
                imageName
              );
              zip.file(`${imageName}.${image.format}`, blob); // Save the image as PNG or JPG
              imageMap.set(imageKey, imageName); // Save the image name to avoid duplicate images
            }

            // Update the <img> src in HTML to point to the new file in ZIP
            html = html.replace(
              `src="data:image/${image.format};base64,${image.base64}"`,
              `src="${imageName}.${image.format}"`
            );
          });

          resolve({
            name: page.getName() || `Page-${page.id}`,
            html,
            css,
          });
        }, 500); // Adjusted timeout for more stable rendering
      });
    };

    const processPages = async () => {
      for (const page of pages) {
        const isMainPage = await checkIfMainPage(page.id); // Fetch if the page is main from the DB

        // Determine the file name
        let pageName = isMainPage
          ? "index"
          : page.getName() || `Page-${page.id}`;
        const content = await fetchPageContent(page);

        // Add HTML content
        zip.file(
          `${pageName}.html`,
          `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${content.name}</title>
    <link rel="stylesheet" href="${pageName}.css">
</head>
<body>
    ${content.html}
</body>
</html>`
        );

        // Add CSS content
        zip.file(`${pageName}.css`, content.css);
      }

      // Generate the ZIP file and send to PHP
      zip.generateAsync({ type: "blob" }).then((content) => {
        uploadToServer(content);
      });
    };

    const uploadToServer = (zipBlob) => {
      const formData = new FormData();
      formData.append("file", zipBlob, "website.zip"); // Append the zip file to the form data

      // Replace `projectId` with the actual project ID
      console.log(projectId);
      // Send the file to the Laravel endpoint
      fetch(`/deploy/${projectId}`, {
        method: "POST",
        body: formData,
        headers: {
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
            .content, // Laravel CSRF token
        },
      })
        .then((response) => response.json())
        .then((data) => {
          // Check for success
          if (data.status === "success") {
            alert(`Website live at: ${data.domain}.test.wpengineers.com`);
          } else {
            alert(`Error: ${data.message}`);
          }
        })
        .catch((error) => {
          console.error("Request failed:", error);
          alert("An error occurred while uploading the files.");
        });
    };

    // Helper function to check if a page is marked as main in the database
    const checkIfMainPage = false;

    processPages(); // Start processing the pages
  },
});




// // Wait until the editor is fully loaded
// editor.on('load', function () {
//   if (window.location.search.includes("screenshot=true")) {
//       setTimeout(function () {
//         iconBar.click();
//         editor.runCommand("preview");
//       }, 2000);
//   }
// });

// document.getElementById("test").addEventListener("click", function () {
//   // Step 1: Extract HTML and CSS from GrapesJS
//   const builderHTML = editor.getHtml(); // Replace `editor` with your GrapesJS editor instance
//   const builderCSS = editor.getCss();

//   // Step 2: Set desired size and aspect ratio
//   const targetWidth = 1280; // Example: Set width to 1280px
//   const aspectRatio = 9 / 16; // Example: Maintain 16:9 aspect ratio
//   const targetHeight = targetWidth * aspectRatio;

//   // Step 3: Create a temporary div to render the extracted content
//   const tempDiv = document.createElement("div");
//   tempDiv.id = "temporaryCaptureDiv";
//   tempDiv.style.position = "absolute";
//   tempDiv.style.top = "0";
//   tempDiv.style.left = "0";
//   tempDiv.style.width = `${targetWidth}px`; // Set the width for the content
//   tempDiv.style.height = `${targetHeight}px`; // Adjust height as needed
//   tempDiv.style.overflow = "hidden"; // Avoid overflow issues
//   tempDiv.style.backgroundColor = "#fff"; // Set background color to avoid transparency issues
//   tempDiv.style.zIndex = "-1"; // Hide from view

//   // Add the div to the body first
//   document.body.appendChild(tempDiv);

//   // Step 4: Create and append the full boilerplate HTML structure
//   const boilerplateHTML = `
//         <!DOCTYPE html>
//         <html lang="en">
//         <head>
//             <meta charset="UTF-8">
//             <meta name="viewport" content="width=device-width, initial-scale=1.0">
//             <title>Temporary Capture</title>
//             <style>${builderCSS}</style> <!-- Add extracted CSS -->
//         </head>
//         <body>
//             ${builderHTML} <!-- Add extracted HTML -->
//         </body>
//         </html>
//     `;

//   // Inject boilerplate into the div
//   tempDiv.innerHTML = boilerplateHTML;

//   // Step 5: Ensure all images are loaded before capturing
//   const images = tempDiv.querySelectorAll("img");
//   const promises = Array.from(images).map((img) => {
//     return new Promise((resolve) => {
//       if (img.complete) {
//         resolve();
//       } else {
//         img.onload = img.onerror = resolve;
//       }
//     });
//   });

//   Promise.all(promises).then(() => {
//     // Step 6: Capture the content using html2canvas
//     html2canvas(tempDiv, {
//       width: targetWidth,
//       height: targetHeight,
//       scale: 1, // Adjust scaling to improve resolution
//     })
//       .then(function (canvas) {
//         const image = canvas.toDataURL("image/png");

//         // Trigger download
//         const link = document.createElement("a");
//         link.href = image;
//         link.download = "new_screenshot.png";
//         link.click();

//         // Clean up the temporary div
//         document.body.removeChild(tempDiv);
//       })
//       .catch(function (error) {
//         console.error("Error capturing screenshot:", error);
//       });
//   });
// });

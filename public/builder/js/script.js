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

// Add save button to the 'options' panel
editor.Panels.addButton("options", {
  id: "live-content",
  className: "fa fa-solid fa-upload",
  command: "liveContent",
  attributes: { title: "Live this Page" },
});

function saveContent() {
  startLoadingAnimation();
  editor.store();
  setTimeout(() => {
    showSuccessAnimation();
    console.log("Saved!!!");
  }, 1500);
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
        pagesApi.select(page.id); // Open the page for editing
        subMenu.style.display = "none"; // Close sub-menu after action
        renderPages();
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
        const projectId = e.target.dataset.id;
        const page = pagesApi.getAll().find((p) => p.id === projectId); // Find the page by ID
        const pageName = page ? page.getName() : "Unknown Page"; // Get the page name, fallback if not found

        // Ask for confirmation
        if (
          confirm(`Are you sure you want to delete the page: "${pageName}"?`)
        ) {
          pagesApi.remove(projectId); // Delete the selected page
          renderPages(); // Re-render the list after deletion
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
  const logo = "TeknoFlair"; // The project name or any dynamic content

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

// editor.Commands.add("liveContent", {
//   run: function () {
//     liveContent();
//   },
// });

// // Event listener for live button click
// function liveContent() {
//   // Get HTML and CSS from GrapesJS editor
//   const htmlContent = editor.getHtml();
//   const cssContent = editor.getCss();

//   // Combine HTML and CSS into one file
//   const fullHtml = `<!DOCTYPE html>
// <html lang="en">
// <head>
//     <meta charset="UTF-8">
//     <meta name="viewport" content="width=device-width, initial-scale=1.0">
//     <title>Exported Page</title>
//     <link href="styles.css" rel="stylesheet" />
// </head>
// <body>
// ${htmlContent}
// </body>
// </html>`;

//   // Create a zip file with JSZip
//   const zip = new JSZip();
//   zip.file("index.html", fullHtml); // Add the HTML file
//   zip.file("styles.css", cssContent); // Add the CSS file

//   // Generate the ZIP file as a Blob (Binary Large Object)
//   zip
//     .generateAsync({ type: "blob" })
//     .then(function (blob) {
//       // Once the ZIP file is ready, send it to your PHP server
//       uploadToPHPServer(blob);
//     })
//     .catch(function (error) {
//       console.error("Error generating the ZIP file:", error);
//     });
// }

// // Function to send the ZIP file to PHP server
// function uploadToPHPServer(blob) {
//   const formData = new FormData();
//   formData.append("file", blob, "site.zip");

//   // Send the file to your PHP server (upload.php)
//   fetch("live-content.php", {
//     method: "POST",
//     body: formData,
//   })
//     .then((response) => response.json())
//     .then((data) => {
//       console.log("Upload success:", data);
//     })
//     .catch((error) => {
//       console.error("Upload failed:", error);
//     });
// }

// editor.Commands.add("liveContent", {
//   run(editor) {
//     const pages = editor.Pages.getAll(); // Get all pages
//     const zip = new JSZip(); // Initialize the ZIP file

//     const fetchPageContent = (page) => {
//       return new Promise((resolve) => {
//         editor.Pages.select(page); // Switch to the page

//         setTimeout(() => {
//           const html = editor.getHtml(); // Get the HTML
//           const css = editor.getCss(); // Get the CSS
//           resolve({
//             name: page.getName() || `Page-${page.id}`, // Use the page title or ID for file names
//             html,
//             css,
//           });
//         }, 200); // Add a delay to ensure proper page rendering
//       });
//     };

//     const processPages = async () => {
//       for (const page of pages) {
//         const content = await fetchPageContent(page);

//         // Add an HTML file for the page
//         zip.file(
//           `${page.getName()}.html`,
//           `
// <!DOCTYPE html>
// <html lang="en">
// <head>
//     <meta charset="UTF-8">
//     <meta name="viewport" content="width=device-width, initial-scale=1.0">
//     <title>${page.getName()}</title>
//     <link rel="stylesheet" href="${page.getName()}.css">
// </head>
// <body>
//     ${content.html}
// </body>
// </html>`
//         );

//         // Add a CSS file for the page
//         zip.file(`${page.getName()}.css`, content.css);
//       }

//       // Generate the ZIP and trigger the download
//       zip.generateAsync({ type: "blob" }).then((content) => {
//         saveAs(content, "website.zip"); // Use FileSaver.js to trigger download
//       });
//     };

//     processPages(); // Start processing the pages
//   },
// });

// var htmlContent = editor.getHtml();
// var cssContent = editor.getCss();

// function deployWebsite() {
//   // Make the AJAX request to PHP
//   fetch("php/deploy_to_surge.php", {
//     method: "POST",
//     headers: {
//       "Content-Type": "application/json",
//     },
//     body: JSON.stringify({
//       html: "<h1 style='color: aqua;'>Hello World</h1>",
//       css: cssContent,
//     }),
//   })
//     .then((response) => response.json())
//     .then((data) => {
//       alert(data.message); // Show success or error message
//     })
//     .catch((error) => {
//       console.error("Error:", error);
//       alert("Deployment failed");
//     });
// }

// semi final v

// editor.Commands.add("liveContent", {
//   run(editor) {
//     const pages = editor.Pages.getAll(); // Get all pages
//     const pageData = []; // Initialize an array to store page data

//     const fetchPageContent = (page) => {
//       return new Promise((resolve) => {
//         editor.Pages.select(page); // Switch to the page

//         setTimeout(() => {
//           const html = editor.getHtml(); // Get the HTML
//           const css = editor.getCss(); // Get the CSS
//           resolve({
//             name: page.getName() || `Page-${page.id}`, // Use the page title or ID for file names
//             html,
//             css,
//           });
//         }, 200); // Add a delay to ensure proper page rendering
//       });
//     };

//     const processPages = async () => {
//       for (const page of pages) {
//         const content = await fetchPageContent(page);
//         pageData.push(content); // Add page data to the array
//       }

//       // Now send the page data to the server using AJAX
//       uploadToServer(pageData);
//     };

//     const uploadToServer = (pageData) => {
//       const data = new FormData();
//       data.append("pageData", JSON.stringify(pageData)); // Convert pageData to JSON string

//       // Send data to PHP server (live-content.php)
//       fetch("php/deploy_to_surge.php", {
//         method: "POST",
//         body: data,
//       })
//         .then((response) => response.json())
//         .then((data) => {
//           if (data.status === "success") {
//             console.log("Files created successfully:", data.message);
//           } else {
//             console.error("Error:", data.message);
//           }
//         })
//         .catch((error) => {
//           console.error("Request failed:", error);
//         });
//     };

//     processPages(); // Start processing the pages
//   },
// });

// final working v of surge

// editor.Commands.add("liveContent", {
//   run(editor) {
//     const pages = editor.Pages.getAll(); // Get all pages
//     const pageData = []; // Initialize an array to store page data

//     const fetchPageContent = (page) => {
//       return new Promise((resolve) => {
//         editor.Pages.select(page); // Switch to the page

//         setTimeout(() => {
//           const html = editor.getHtml(); // Get the HTML
//           const css = editor.getCss(); // Get the CSS
//           resolve({
//             name: page.getName() || `Page-${page.id}`, // Use the page title or ID for file names
//             html,
//             css,
//           });
//         }, 200); // Add a delay to ensure proper page rendering
//       });
//     };

//     const processPages = async () => {
//       for (const page of pages) {
//         const content = await fetchPageContent(page);
//         pageData.push(content); // Add page data to the array
//       }

//       // Now send the page data to the server using AJAX
//       uploadToServer(pageData);
//     };

//     const uploadToServer = (pageData) => {
//       const data = new FormData();
//       data.append("pageData", JSON.stringify(pageData)); // Convert pageData to JSON string

//       // Send data to PHP server (live-content.php)
//       fetch("php/deploy_to_surge.php", {
//         method: "POST",
//         body: data,
//       })
//         .then((response) => response.json())
//         .then((data) => {
//           if (data.status === "success") {
//             console.log("Files created successfully:", data.message);
//             alert("Success! Your website has been deployed: " + data.message);
//           } else {
//             console.error("Error:", data.message);
//             alert("Error: " + data.message);
//           }
//         })
//         .catch((error) => {
//           console.error("Request failed:", error);
//           alert(
//             "Request failed. Please check your server or network connection."
//           );
//         });
//     };

//     processPages(); // Start processing the pages
//   },
// });

editor.Commands.add("liveContent", {
  run(editor) {
    const pages = editor.Pages.getAll(); // Get all pages
    const zip = new JSZip(); // Initialize the ZIP file

    const fetchPageContent = (page) => {
      return new Promise((resolve) => {
        editor.Pages.select(page); // Switch to the page

        setTimeout(() => {
          const html = editor.getHtml(); // Get the HTML
          const css = editor.getCss(); // Get the CSS
          resolve({
            name: page.getName() || `Page-${page.id}`, // Use the page title or ID for file names
            html,
            css,
          });
        }, 500); // Adjusted timeout for more stable rendering
      });
    };

    const processPages = async () => {
      for (const page of pages) {
        const content = await fetchPageContent(page);

        // Add HTML content
        zip.file(
          `${content.name}.html`,
          `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${content.name}</title>
    <link rel="stylesheet" href="${content.name}.css">
</head>
<body>
    ${content.html}
</body>
</html>`
        );

        // Add CSS content
        zip.file(`${content.name}.css`, content.css);
      }

      // Generate the ZIP file and send to PHP
      zip.generateAsync({ type: "blob" }).then((content) => {
        uploadToServer(content);
      });
    };

    const uploadToServer = (zipBlob) => {
      const formData = new FormData();
      formData.append("file", zipBlob, "website.zip"); // Append the zip file to the form data

      // Send the file to the PHP server
      fetch(`php/netlify_deploy.php?project_name=${projectName}`, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          // Log the response for debugging
          console.log(data);

          // Check for success
          if (data.status === "success") {
            // Only show one alert if deployment was successful
            alert(`Website deployed successfully! Live at: ${data.url}`);
          } else {
            // Show alert if there was an error
            alert(`Deployment failed: ${data.message}`);
          }
        })
        .catch((error) => {
          // Log the error for debugging
          console.error("Request failed:", error);
          alert("An error occurred while deploying the website.");
        });
    };

    processPages(); // Start processing the pages
  },
});

// Show Templates Modal
function showTemplates(category) {
  document.getElementById("modalCategory").textContent = category;
  const modal = document.getElementById("templateModal");
  modal.style.display = "block";

  // Fetch templates via AJAX
  // Fetch templates via AJAX
  fetch(`./php/fetch_templates.php?category=${category}`)
    .then((response) => response.json())
    .then((templates) => {
      const container = document.getElementById("templatesContainer");
      container.innerHTML = ""; // Clear previous templates

      templates.forEach((template) => {
        const card = document.createElement("div");
        card.className = "template-card";

        // Create an image element and set the source from the template's image URL
        const img = document.createElement("img");
        img.src = template.template_image; // The image URL fetched from the database
        img.alt = template.template_name;
        img.className = "template-img"; // You can add CSS for styling

        // Add image to the card
        card.appendChild(img);

        // Add template name text
        const title = document.createElement("h3");
        title.textContent = template.template_name;
        card.appendChild(title);

        // Add template description (if available)
        if (template.template_description) {
          const description = document.createElement("p");
          description.textContent = template.template_description;
          card.appendChild(description);
        }

        // Add a preview link (if available)
        if (template.template_preview_link) {
          const previewLink = document.createElement("a");
          previewLink.href = template.template_preview_link;
          previewLink.textContent = "Preview Template";
          previewLink.target = "_blank"; // Open link in a new tab
          previewLink.onclick = (event) => {
            event.stopPropagation(); // Prevent click event from reaching the parent div
          };
          card.appendChild(previewLink);
        }

        // Add click event to select the template
        card.onclick = () => selectTemplate(template);

        // Append the card to the container
        container.appendChild(card);
      });
    })
    .catch((error) => {
      console.error("Error fetching templates:", error);
    });
}

// Select Template
function selectTemplate(template) {
  document.getElementById("templateModal").style.display = "none";

  // Open Project Modal
  const projectModal = document.getElementById("projectModal");
  projectModal.style.display = "block";
  document.getElementById(
    "selectedTemplate"
  ).textContent = `Create Project from: ${template.template_name}`;
  document.getElementById("template_json").value = template.template_json;
}

// Close Modal
function closeModal() {
  document
    .querySelectorAll(".modal")
    .forEach((modal) => (modal.style.display = "none"));
}

const projectName = document.getElementById("project_name");
projectName.addEventListener("input", () => {
  const value = projectName.value;
  projectName.value = value.charAt(0).toUpperCase() + value.slice(1);
});


const projectNameModal = document.getElementById("new_project_name");
projectNameModal.addEventListener("input", () => {
  const value = projectNameModal.value;
  projectNameModal.value = value.charAt(0).toUpperCase() + value.slice(1);
});



document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM fully loaded and parsed");

  //  Select necessary elements
  const editModal = document.getElementById("editModal");
  const editId = document.getElementById("editId");
  const editName = document.getElementById("editName");
  const editPhoto = document.getElementById("editPhoto");
  const currentPhoto = document.getElementById("currentPhoto");
  const photoContainer = document.getElementById("photoContainer");
  const removePhotoCheckbox = document.getElementById("removePhotoCheckbox");
  const removePhotoLabel = document.querySelector(
    "label[for='removePhotoCheckbox']"
  );
  const apiMessage = document.getElementById("apiMessage");
  const addModal = document.getElementById("addModal");
  const closeAddModal = document.getElementById("closeAddModal");
  const openAddModal = document.getElementById("openAddModal");
  const addForm = document.getElementById("addForm");

  //  Open the Add Modal
  openAddModal.addEventListener("click", function () {
    addModal.classList.remove("hidden");
  });

  //  Close the Add Modal
  closeAddModal.addEventListener("click", function () {
    addModal.classList.add("hidden");
    addForm.reset(); // Reset form inputs
  });

  //  Handle Add Category Form Submission
  addForm.addEventListener("submit", async function (event) {
    event.preventDefault(); // Prevent form default behavior

    const formData = new FormData(addForm);

    const response = await fetch("add_category.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();
    Swal.fire({
      title: result.success ? "Success" : "Error",
      text: result.message,
      icon: result.success ? "success" : "error",
    });

    if (result.success) {
      addForm.reset(); //  Reset the form after successful submission
      addModal.classList.add("hidden"); //  Close the modal
      setTimeout(() => window.location.reload(), 1000); //  Reload page after 1 sec
    }
  });

  //  Reset Edit Modal Before Opening
  function resetEditModal() {
    editId.value = "";
    editName.value = "";
    editName.removeAttribute("disabled");
    editPhoto.value = "";
    currentPhoto.src = "";
    currentPhoto.classList.add("hidden");
    removePhotoCheckbox.checked = false;
    removePhotoCheckbox.classList.add("hidden");
    removePhotoLabel.classList.add("hidden");
    apiMessage.classList.add("hidden");
    photoContainer.style.display = "block";
  }

  //  Handle Edit Click
  function handleEditClick(event) {
    resetEditModal();

    const button = event.currentTarget;
    const id = button.dataset.id;
    let name = button.dataset.name?.trim() || "";
    let photo = button.dataset.photo ? button.dataset.photo.trim() : "";

    //  Correctly detect API category
    const isApiCategory =
      button.dataset.api === "true" ||
      button.dataset.api === "1" ||
      button.dataset.api === "t";

    console.log(
      `Editing category: ID=${id}, Name=${name}, isAPI=${isApiCategory}`
    );

    editId.value = id;

    if (!name) {
      console.error("Error: Category name is empty!");
      Swal.fire(
        "Error",
        "Category name is missing. Please check the database.",
        "error"
      );
      return;
    }

    editName.value = name; // Keep category name

    // ✅ If API category → Disable name field but allow image upload
    if (isApiCategory) {
      editName.setAttribute("disabled", "disabled");
      apiMessage.classList.remove("hidden");
      editPhoto.removeAttribute("disabled"); //  Allow photo updates
    } else {
      editName.removeAttribute("disabled");
      apiMessage.classList.add("hidden");
    }

    //  Handle Image Display
    if (photo !== "") {
      currentPhoto.src = "uploads/" + photo;
      currentPhoto.classList.remove("hidden");
      removePhotoCheckbox.classList.remove("hidden");
      removePhotoLabel.classList.remove("hidden");
    } else {
      currentPhoto.src = "";
      currentPhoto.classList.add("hidden");
      removePhotoCheckbox.classList.add("hidden");
      removePhotoLabel.classList.add("hidden");
    }

    editModal.classList.remove("hidden"); // Show modal
  }

  //  Handle Remove Photo Checkbox
  removePhotoCheckbox.addEventListener("change", function () {
    if (this.checked) {
      currentPhoto.classList.add("hidden");
      currentPhoto.src = "";
    } else if (currentPhoto.src !== "") {
      currentPhoto.classList.remove("hidden");
    }
  });

  //  Attach Event Listeners to Edit & Delete Buttons
  function attachEventListeners() {
    document.querySelectorAll(".edit-btn").forEach((button) => {
      button.removeEventListener("click", handleEditClick);
      button.addEventListener("click", handleEditClick);
    });

    document.querySelectorAll(".delete-btn").forEach((button) => {
      button.removeEventListener("click", handleDeleteClick);
      button.addEventListener("click", handleDeleteClick);
    });
  }

  attachEventListeners(); // Attach on page load

  //  Close Modal and Reset Data
  document
    .getElementById("closeEditModal")
    .addEventListener("click", function () {
      editModal.classList.add("hidden");
      resetEditModal();
    });

  //  Refresh Button Handler
  document.getElementById("refreshBtn").addEventListener("click", () => {
    document.getElementById("searchInput").value = "";
    setTimeout(() => location.reload(), 100);
  });

  //  Search & Update Table with Functional Edit & Delete Buttons
  document
    .getElementById("searchInput")
    .addEventListener("input", async function () {
      const query = this.value.trim();
      const tableBody = document.querySelector("tbody");

      if (!query) {
        window.location.reload();
        return;
      }

      const response = await fetch(
        `search_category.php?query=${encodeURIComponent(query)}`
      );
      const categories = await response.json();

      tableBody.innerHTML =
        categories.length === 0
          ? `<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No categories found.</td></tr>`
          : categories
              .map(
                (category) => ` 
    <tr class="hover:bg-gray-100 transition">
        <td class="pl-4 px-3 py-3 text-sm text-gray-700">${category.id}</td>
        <td class="px-3 py-3 text-sm text-gray-700 font-medium">
            ${category.category_name.replace(
              new RegExp(`(${query})`, "gi"),
              '<span class="bg-yellow-300">$1</span>'
            )}
        </td>
        <td class="px-3 py-3 text-sm text-gray-700">
            ${
              category.category_photo
                ? `<img src="uploads/${category.category_photo}" class="w-12 h-12 rounded-lg border border-gray-300 shadow-sm object-cover" onerror="this.onerror=null; this.src='uploads/default-placeholder.png';">`
                : `<img src="uploads/default-placeholder.png" class="w-12 h-12 rounded-lg border border-gray-300 shadow-sm object-cover">`
            }
        </td>
        <td class="px-2 py-3 text-sm text-left">
            ${
              category.is_api
                ? '<i class="fas fa-check-circle text-green-500 text-lg ml-2"></i>'
                : '<i class="fas fa-times-circle text-red-500 text-lg ml-2"></i>'
            }
        </td>
        <td class="px-3 py-3 text-center">
            <button class="edit-btn text-blue-500 hover:text-blue-700 transition transform hover:scale-110 mr-3"
                data-id="${category.id}" 
                data-name="${category.category_name}" 
                data-photo="${category.category_photo || ""}" 
                data-api="${category.is_api ? "true" : "false"}">
                <i class="fas fa-edit"></i>
            </button>
            <button class="delete-btn text-red-500 hover:text-red-700 transition transform hover:scale-110 ml-3"
                data-id="${category.id}">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
`
              )
              .join("");

      attachEventListeners(); //  Reattach event listeners after search
    });
  //  Delete Category Function
  function handleDeleteClick() {
    const id = this.dataset.id;
    Swal.fire({
      title: "Are you sure?",
      text: "You won't be able to revert this!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "No, cancel!",
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`delete_category.php?id=${id}`, {
          method: "GET",
        })
          .then(() => {
            Swal.fire("Deleted!", "The category has been deleted.", "success");
            setTimeout(() => window.location.reload(), 2000);
          })
          .catch((error) => console.error("Error:", error));
      }
    });
  }

  //  Edit Form Submission
  document
    .getElementById("editForm")
    .addEventListener("submit", async function (event) {
      event.preventDefault();
      const categoryName = document.getElementById("editName").value.trim();
      const isApiCategory = document
        .getElementById("editName")
        .hasAttribute("disabled");

      if (!isApiCategory && !categoryName) {
        Swal.fire({
          title: "Error",
          text: "Category name cannot be empty.",
          icon: "error",
        });
        return;
      }
      const formData = new FormData(this);
      const response = await fetch("edit_category.php", {
        method: "POST",
        body: formData,
      });
      const result = await response.json();
      Swal.fire({
        title: result.success ? "Success" : "Error",
        text: result.message,
        icon: result.success ? "success" : "error",
      });
      if (result.success) {
        setTimeout(() => window.location.reload(), 2000);
      }
    });
});

document.addEventListener("DOMContentLoaded", () => {
  
  const mobileMenuToggle = document.querySelector(".mobile-menu-toggle")
  const sidebar = document.querySelector(".sidebar")

  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener("click", () => {
      sidebar.classList.toggle("open")
    })
  }

  // Set active nav item based on current page
  const currentPath = window.location.pathname
  const navItems = document.querySelectorAll(".nav-item")

  navItems.forEach((item) => {
    const href = item.getAttribute("href")
    if (href && currentPath.includes(href) && href !== "index.php") {
      item.classList.add("active")
    } else if (currentPath.endsWith("index.php") && href === "index.php") {
      item.classList.add("active")
    }
  })

  // Initialize date pickers if they exist
  const datePickers = document.querySelectorAll('input[type="date"]')
  if (datePickers.length > 0) {
    datePickers.forEach((picker) => {
      // You can add date picker initialization here if needed
    })
  }

  // Toggle forms in client view
  const toggleButtons = document.querySelectorAll("[data-toggle]")
  if (toggleButtons.length > 0) {
    toggleButtons.forEach((button) => {
      button.addEventListener("click", function () {
        const targetId = this.getAttribute("data-toggle")
        const targetElement = document.getElementById(targetId)
        if (targetElement) {
          targetElement.classList.toggle("hidden")
        }
      })
    })
  }
})

function toggleForm(formId) {
  const form = document.getElementById(formId)
  form.classList.toggle("hidden")
}

function hideEditForm(formId) {
  const form = document.getElementById(formId)
  form.classList.add("hidden")
}

function editInstagram(id, usuario, correo) {
  document.getElementById("edit_id_instagram").value = id
  document.getElementById("edit_usuario_instagram").value = usuario
  document.getElementById("edit_correo_instagram").value = correo
  document.getElementById("instagram-edit-form").classList.remove("hidden")
}

function editFacebook(id, usuario, correo) {
  document.getElementById("edit_id_facebook").value = id
  document.getElementById("edit_usuario_facebook").value = usuario
  document.getElementById("edit_correo_facebook").value = correo
  document.getElementById("facebook-edit-form").classList.remove("hidden")
}

function editYoutube(id, usuario, correo) {
  document.getElementById("edit_id_youtube").value = id
  document.getElementById("edit_usuario_youtube").value = usuario
  document.getElementById("edit_correo_youtube").value = correo
  document.getElementById("youtube-edit-form").classList.remove("hidden")
}

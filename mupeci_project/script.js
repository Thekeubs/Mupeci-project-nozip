// Variables globales
let currentUser = null
const visitors = [
  {
    id: 1,
    name: "Jean Mballa",
    department: "Direction Générale",
    purpose: "rendez-vous",
    status: "waiting",
    time: "09:30",
  },
  {
    id: 2,
    name: "Marie Fotso",
    department: "Comptabilité",
    purpose: "visite",
    status: "in-progress",
    time: "10:15",
  },
]

// Fonctions de navigation
function showLogin(userType) {
  const modal = document.getElementById("loginModal")
  const title = document.getElementById("loginTitle")
  const subtitle = document.getElementById("loginSubtitle")

  if (userType === "admin") {
    title.textContent = "Connexion Administrateur"
    subtitle.textContent = "Accès sécurisé à l'administration"
    document.getElementById("email").value = "admin@mupeci.com"
    document.getElementById("password").value = "admin123"
  } else {
    title.textContent = "Connexion Réceptionniste"
    subtitle.textContent = "Accédez à votre espace de travail"
    document.getElementById("email").value = "marie@mupeci.com"
    document.getElementById("password").value = "password123"
  }

  modal.style.display = "block"

  // Gérer la soumission du formulaire
  document.getElementById("loginForm").onsubmit = (e) => {
    e.preventDefault()
    login(userType)
  }
}

function closeModal() {
  document.getElementById("loginModal").style.display = "none"
}

function login(userType) {
  const email = document.getElementById("email").value
  const password = document.getElementById("password").value

  // Simulation de l'authentification
  if (
    (email === "marie@mupeci.com" && password === "password123") ||
    (email === "admin@mupeci.com" && password === "admin123")
  ) {
    currentUser = { email, type: userType }
    closeModal()

    if (userType === "admin") {
      // Pour l'admin, on pourrait ajouter la vérification du code secret
      showDashboard()
    } else {
      showReception()
    }
  } else {
    alert("Identifiants incorrects")
  }
}

function showReception() {
  hideAllInterfaces()
  document.getElementById("receptionistInterface").style.display = "block"
  updateQueueTable()
}

function showDashboard() {
  hideAllInterfaces()
  document.getElementById("dashboardInterface").style.display = "block"
  initChart()
}

function hideAllInterfaces() {
  const interfaceElements = document.querySelectorAll(".interface")
  interfaceElements.forEach((interfaceElement) => {
    interfaceElement.style.display = "none"
  })

  // Cacher aussi la page d'accueil
  const welcomeSection = document.querySelector(".welcome-section")
  if (welcomeSection) {
    welcomeSection.style.display = "none"
  }
}

function logout() {
  currentUser = null
  hideAllInterfaces()
  document.querySelector(".welcome-section").style.display = "block"
}

// Fonctions de gestion des visiteurs
function addVisitor(event) {
  event.preventDefault()

  const name = document.getElementById("visitorName").value
  const phone = document.getElementById("visitorPhone").value
  const email = document.getElementById("visitorEmail").value
  const idNumber = document.getElementById("visitorId").value
  const department = document.getElementById("department").value
  const purpose = document.getElementById("purpose").value

  // Validation simple
  if (!name || !phone || !idNumber || !department) {
    alert("Veuillez remplir tous les champs obligatoires")
    return
  }

  // Validation du téléphone camerounais
  if (!phone.match(/^\+237[0-9]{9}$/)) {
    alert("Format de téléphone invalide (+237XXXXXXXXX)")
    return
  }

  // Ajouter le visiteur
  const newVisitor = {
    id: visitors.length + 1,
    name: name,
    department: department,
    purpose: purpose,
    status: "waiting",
    time: new Date().toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" }),
  }

  visitors.push(newVisitor)

  // Réinitialiser le formulaire
  event.target.reset()

  // Mettre à jour l'affichage
  updateQueueTable()

  alert("Visiteur enregistré avec succès")
}

function updateQueueTable() {
  const tbody = document.getElementById("queueBody")
  if (!tbody) return

  tbody.innerHTML = ""

  const activeVisitors = visitors.filter((v) => v.status === "waiting" || v.status === "in-progress")

  activeVisitors.forEach((visitor) => {
    // Correspondance pour le nom du département
    let deptName = '';
    if (typeof departments !== 'undefined') {
      const dept = departments.find(d => d.id == visitor.department_id);
      deptName = dept ? dept.name : '';
    }
    const row = document.createElement("tr")
    row.innerHTML = `
            <td>${visitor.name}</td>
            <td>${deptName}</td>
            <td>${visitor.purpose.charAt(0).toUpperCase() + visitor.purpose.slice(1)}</td>
            <td>${visitor.checked_in_at ? visitor.checked_in_at : ''}</td>
            <td><span class="status status-${visitor.status}">${visitor.status === "waiting" ? "En attente" : "En cours"}</span></td>
            <td>
                ${
                  visitor.status === "waiting"
                    ? `<button class="btn btn-sm btn-primary" onclick="startVisit(${visitor.id})">Démarrer</button>`
                    : `<button class="btn btn-sm btn-success" onclick="completeVisit(${visitor.id})">Terminer</button>`
                }
            </td>
        `
    tbody.appendChild(row)
  })
}

function startVisit(visitorId) {
  const visitor = visitors.find((v) => v.id === visitorId)
  if (visitor) {
    visitor.status = "in-progress"
    updateQueueTable()
    alert("Visite démarrée")
  }
}

function completeVisit(visitorId) {
  const visitor = visitors.find((v) => v.id === visitorId)
  if (visitor) {
    visitor.status = "completed"
    updateQueueTable()
    alert("Visite terminée")
  }
}

// Graphique simple avec Canvas
function initChart() {
  const canvas = document.getElementById("hourlyChart")
  if (!canvas) return

  const ctx = canvas.getContext("2d")
  const data = [1, 2, 3, 5, 4, 6, 3, 2, 1, 0] // Données d'exemple
  const hours = ["8h", "9h", "10h", "11h", "12h", "13h", "14h", "15h", "16h", "17h"]

  // Effacer le canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height)

  // Configuration
  const padding = 40
  const chartWidth = canvas.width - 2 * padding
  const chartHeight = canvas.height - 2 * padding
  const maxValue = Math.max(...data)

  // Dessiner les axes
  ctx.strokeStyle = "#e5e7eb"
  ctx.lineWidth = 1

  // Axe Y
  ctx.beginPath()
  ctx.moveTo(padding, padding)
  ctx.lineTo(padding, canvas.height - padding)
  ctx.stroke()

  // Axe X
  ctx.beginPath()
  ctx.moveTo(padding, canvas.height - padding)
  ctx.lineTo(canvas.width - padding, canvas.height - padding)
  ctx.stroke()

  // Dessiner la courbe
  ctx.strokeStyle = "#22c55e"
  ctx.lineWidth = 3
  ctx.beginPath()

  data.forEach((value, index) => {
    const x = padding + (index * chartWidth) / (data.length - 1)
    const y = canvas.height - padding - (value * chartHeight) / maxValue

    if (index === 0) {
      ctx.moveTo(x, y)
    } else {
      ctx.lineTo(x, y)
    }
  })

  ctx.stroke()

  // Dessiner les points
  ctx.fillStyle = "#22c55e"
  data.forEach((value, index) => {
    const x = padding + (index * chartWidth) / (data.length - 1)
    const y = canvas.height - padding - (value * chartHeight) / maxValue

    ctx.beginPath()
    ctx.arc(x, y, 4, 0, 2 * Math.PI)
    ctx.fill()
  })

  // Ajouter les labels des heures
  ctx.fillStyle = "#6b7280"
  ctx.font = "12px Arial"
  ctx.textAlign = "center"

  hours.forEach((hour, index) => {
    const x = padding + (index * chartWidth) / (hours.length - 1)
    ctx.fillText(hour, x, canvas.height - 10)
  })
}

// Fermer le modal en cliquant à l'extérieur
window.onclick = (event) => {
  const modal = document.getElementById("loginModal")
  if (event.target === modal) {
    closeModal()
  }
}

// Initialisation
document.addEventListener("DOMContentLoaded", () => {
  // L'application est prête
  console.log("Application MUPECI initialisée")
})

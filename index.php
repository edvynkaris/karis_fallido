<?php
// Karis SaaS — index.php V4 (Theme System + Full Branding)
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Karis | PyMEs Oficial</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Karis es una plataforma que conecta clientes y PyMEs de forma segura y moderna.">
  <meta property="og:title" content="Karis | Plataforma PyME">
  <meta property="og:description" content="Gestiona, vende y crece con Karis.">
  <meta property="og:type" content="website">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@700;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
/* ===============================
   SISTEMA DE COLORES (VARIABLES)
================================ */
:root {
  /* --- MODO CLARO (DEFAULT) --- */
  --bg-main: #ffffff;
  --bg-card: #f8f9fa;
  --text-main: #111111;
  --text-muted: #666666;
  --border-color: rgba(0,0,0,0.1);
  --nav-glass: rgba(255, 255, 255, 0.85);
  
  /* Colores Principales (Brillantes) */
  --theme-client: #00bfa5;  /* Teal Vibrante */
  --theme-business: #dc2626; /* Rojo Vibrante */
  
  /* Gradientes sutiles para secciones */
  --grad-client: linear-gradient(180deg, rgba(0,191,165,0.05) 0%, rgba(255,255,255,0) 100%);
  --grad-business: linear-gradient(180deg, rgba(220,38,38,0.05) 0%, rgba(255,255,255,0) 100%);
}

/* ===============================
   SCROLLBAR FANTASMA (Invisible hasta que la tocas)
================================ */

/* 1. El carril (track) siempre invisible */
::-webkit-scrollbar {
    width: 8px;  /* Delgada */
    height: 8px;
    background-color: transparent; 
}

/* 2. El "pulgar" (la barra móvil) - INVISIBLE POR DEFECTO */
::-webkit-scrollbar-thumb {
    background-color: transparent; /* Truco: Es transparente */
    border-radius: 10px;
}

/* 3. SOLO AL PASAR EL MOUSE (Hover) se hace visible */
::-webkit-scrollbar-thumb:hover {
    background-color: #bbbbbb; /* Gris suave en modo claro */
}

/* ===============================
   AJUSTE ESPECÍFICO MODO OSCURO
================================ */

/* En modo oscuro: Sigue invisible, pero al tocarla se pone BLANCA */
body.dark-mode ::-webkit-scrollbar-thumb:hover {
    background-color: rgba(255, 255, 255, 0.8); /* Blanco semi-transparente elegante */
}

/* Al hacer click y arrastrar (Active) */
body.dark-mode ::-webkit-scrollbar-thumb:active {
    background-color: #ffffff; /* Blanco puro al agarrarla */
}

/* --- MODO OSCURO (DARK MODE) --- */
body.dark-mode {
  --bg-main: #0f1115;
  --bg-card: #181b21;
  --text-main: #ffffff;
  --text-muted: #9ca3af;
  --border-color: rgba(255,255,255,0.1);
  --nav-glass: rgba(15, 17, 21, 0.9);
  
  /* Colores Principales (Más Oscuros/Apagados) */
  --theme-client: #008f7a; /* Teal más profundo */
  --theme-business: #991b1b; /* Rojo sangre oscuro */
  
  /* Gradientes oscuros */
  --grad-client: linear-gradient(180deg, rgba(0,143,122,0.15) 0%, rgba(15,17,21,0) 100%);
  --grad-business: linear-gradient(180deg, rgba(153,27,27,0.15) 0%, rgba(15,17,21,0) 100%);
}

/* ===============================
   BASE
================================ */
* { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }

body {
  font-family: 'Inter', sans-serif;
  background-color: var(--bg-main);
  color: var(--text-main);
  overflow-x: hidden;
  transition: background-color 0.3s, color 0.3s;
}

/* ===============================
   INTRO SCREEN
================================ */
.intro-screen {
  height: 100vh;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--bg-main); /* Se adapta al tema */
  position: relative;
  z-index: 10;
  transition: background 0.3s;
}

.intro-logo-container { text-align: center; animation: fadeIn 1.5s ease-out; }

.intro-img {
  max-width: 320px; height: auto;
  margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto;
}

.intro-title {
  font-family: 'Outfit', sans-serif;
  font-size: 5rem;
  letter-spacing: -2px;
  margin: 0;
  /* Gradiente de texto */
  background: linear-gradient(to right, var(--theme-client), var(--theme-business));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.scroll-indicator {
  position: absolute; bottom: 40px;
  /* --- NUEVO: Alineación Horizontal --- */
  display: flex;             /* Pone los elementos uno al lado del otro */
  align-items: center;       /* Los centra verticalmente */
  gap: 10px;                 /* Espacio entre la flecha y el texto */
  
  /* --- Animación y Estilo --- */
  animation: bounce 2s infinite; 
  opacity: 0.7;
  font-size: 0.9rem; 
  letter-spacing: 1px; 
  text-transform: uppercase;
  color: var(--text-muted);
}

/* Ajuste fino solo para la flecha */
.scroll-indicator i {
  transform: translateY(-5px); /* El número negativo la sube */
}

/* ===============================
   NAVBAR
================================ */
.navbar {
  position: fixed; top: 0; left: 0; width: 100%;
  height: 70px;
  display: flex; align-items: center; justify-content: space-between; /* Espacio para el toggle */
  padding: 0 30px;
  z-index: 1000;
  background: var(--nav-glass);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border-color);
  transform: translateY(-100%);
  transition: transform 0.4s ease, background 0.3s, border 0.3s;
}

.navbar.visible { transform: translateY(0); }

.nav-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.nav-logo-img { height: 52px; width: auto; }
.nav-title { font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--text-main); }

/* Toggle Button */
.theme-toggle {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  color: var(--text-main);
  width: 40px; height: 40px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  font-size: 20px;
  transition: all 0.3s;
}
.theme-toggle:hover { transform: rotate(15deg); border-color: var(--theme-client); }

/* ===============================
   SPLIT HERO
================================ */
.hero-split { height: 90vh; display: flex; position: relative; }

.split {
  flex: 1; position: relative;
  transition: flex 0.6s cubic-bezier(0.25, 1, 0.5, 1);
  overflow: hidden; display: flex; align-items: center; justify-content: center; cursor: pointer;
}

/* Fotos de fondo (puedes cambiarlas) */
.split.left { background: url('https://images.unsplash.com/photo-1556742049-0cfed4f7a07d?q=80&w=2070') center/cover; }
.split.right { background: url('https://images.unsplash.com/photo-1556761175-5973dc0f32e7?q=80&w=2032') center/cover; }

/* Overlays con los colores principales */
.split.left::before {
  content: ""; position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(0, 191, 165, 0.9), rgba(0, 143, 122, 0.75)); /* Static Teal for image overlay */
}
.split.right::before {
  content: ""; position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(185, 28, 28, 0.9), rgba(127, 29, 29, 0.85)); /* Static Red for image overlay */
}

.split-content { position: relative; z-index: 10; text-align: center; color: #fff; } /* Siempre blanco aquí */
.split h2 { font-family: 'Outfit', sans-serif; font-size: 2.8rem; text-transform: uppercase; margin-bottom: 15px; text-shadow: 0 4px 20px rgba(0,0,0,0.3); }

.action-btn {
  padding: 12px 30px; border: 2px solid #fff; border-radius: 50px;
  color: #fff; font-weight: 600; font-size: 0.9rem; text-transform: uppercase;
  background: transparent; cursor: pointer; transition: all 0.3s;
}

.split:hover .action-btn { background: #fff; color: #000; }
.hero-split:hover .split { opacity: 0.6; }
.hero-split .split:hover { flex: 2; opacity: 1; }

/* ===============================
   SECCIONES UNIFICADAS
================================ */
.info-section {
  padding: 80px 20px;
  max-width: 1100px;
  margin: 0 auto;
}

.section-title {
  font-family: 'Outfit', sans-serif;
  font-size: 2.2rem;
  margin-bottom: 40px;
  text-align: center;
  color: var(--text-main);
}

.grid-features {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;
}

.card {
  background: var(--bg-card);
  padding: 30px; border-radius: 16px;
  border: 1px solid var(--border-color);
  transition: transform 0.3s, border-color 0.3s;
}
.card:hover { transform: translateY(-5px); }

.icon-box { font-size: 32px; margin-bottom: 15px; }
.card h3 { font-size: 1.25rem; margin-bottom: 10px; font-weight: 600; color: var(--text-main); }
.card p { font-size: 0.95rem; color: var(--text-muted); line-height: 1.5; }

/* --- TEMA CLIENTE (SEGURIDAD) --- */
/* Esta sección usa el Azul/Teal */
.client-theme {
  background: var(--grad-client);
  border-bottom: 1px solid var(--border-color);
}
.client-theme .section-title span { color: var(--theme-client); }
.client-theme .card:hover { border-color: var(--theme-client); }
.client-theme .icon-box { color: var(--theme-client); }

/* --- TEMA PYME (NOSOTROS) --- */
/* Esta sección usa el Rojo */
.business-theme {
  background: var(--grad-business);
}
.business-theme .section-title span { color: var(--theme-business); }
.business-theme .card:hover { border-color: var(--theme-business); }
.business-theme .icon-box { color: var(--theme-business); }


/* ===============================
   FOOTER
================================ */
footer {
  background: var(--bg-card);
  padding: 40px 20px; text-align: center;
  border-top: 1px solid var(--border-color); font-size: 0.9rem; color: var(--text-muted);
}

/* ===============================
   MODALES
================================ */
.modal-overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.85); backdrop-filter: blur(5px);
  z-index: 2000;
  display: flex; align-items: center; justify-content: center;
  opacity: 0; visibility: hidden; transition: all 0.3s;
}
.modal-overlay.active { opacity: 1; visibility: visible; }

.modal-box {
  background: var(--bg-main); /* Adaptable */
  padding: 40px; border-radius: 20px;
  text-align: center; max-width: 420px; width: 90%;
  border: 1px solid var(--border-color);
  transform: scale(0.9); transition: 0.3s; position: relative;
}
.modal-overlay.active .modal-box { transform: scale(1); }

.close-modal {
  position: absolute; top: 15px; right: 15px;
  background: none; border: none; color: var(--text-main);
  font-size: 20px; cursor: pointer;
}
.modal-title { font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin-bottom: 5px; color: var(--text-main); }
.modal-desc { color: var(--text-muted); margin-bottom: 25px; }

.modal-actions { display: flex; gap: 15px; }
.btn-option {
  flex: 1; padding: 12px; border-radius: 10px;
  text-decoration: none; font-weight: 600; font-size: 0.9rem;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: 0.2s;
}
.btn-secondary { background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color); }
.btn-secondary:hover { border-color: var(--text-muted); }

/* Animaciones */
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes bounce { 0%, 20%, 50%, 80%, 100% {transform: translateY(0);} 40% {transform: translateY(-10px);} 60% {transform: translateY(-5px);} }

/* Responsive */
@media(max-width: 768px) {
  .intro-title { font-size: 3.5rem; }
  .hero-split { flex-direction: column; height: auto; }
  .split { height: 300px; width: 100%; }
  .split h2 { font-size: 2rem; }
  .navbar { padding: 0 20px; }
}
</style>
</head>
<body class=""> 
  <section class="intro-screen">
    <div class="intro-logo-container">
      <img src="assets/logo.png" alt="Karis Logo" class="intro-img">
      <h1 class="intro-title">KARIS</h1>
    </div>
    <div class="scroll-indicator">
      Desliza para comenzar <br> <i class="ph ph-caret-down" style="margin-top:10px; font-size:20px;"></i>
    </div>
  </section>

  <nav class="navbar" id="stickyNav">
    <a href="#" class="nav-brand">
      <img src="assets/logo.png" alt="Logo Small" class="nav-logo-img">
    </a>
  
    <button class="theme-toggle" id="themeBtn" title="Cambiar Tema">
      <i class="ph ph-moon"></i>
    </button>
  </nav>

  <div class="hero-split" id="mainContent">
    <div class="split left" onclick="openModal('cliente')">
      <div class="split-content">
        <h2>¿Qué vas a pedir hoy?</h2>
        <button class="action-btn">Cliente</button>
      </div>
    </div>
    <div class="split right" onclick="openModal('pyme')">
      <div class="split-content">
        <h2>Administra tu negocio</h2>
        <button class="action-btn">Pyme</button>
      </div>
    </div>
  </div>

  <section class="info-section client-theme">
    <div class="section-title">
      <h2>Seguridad <span>Blindada</span></h2>
    </div>
    <div class="grid-features">
      <div class="card">
        <i class="ph ph-shield-check icon-box"></i>
        <h3>Encriptación Total</h3>
        <p>Datos protegidos con el color de la confianza. Tu seguridad es nuestra prioridad #1.</p>
      </div>
      <div class="card">
        <i class="ph ph-lock-key icon-box"></i>
        <h3>Accesos Controlados</h3>
        <p>Gestión avanzada de permisos para que nadie vea lo que no debe.</p>
      </div>
      <div class="card">
        <i class="ph ph-cloud-check icon-box"></i>
        <h3>Respaldos Diarios</h3>
        <p>Copias automáticas. Tranquilidad mental garantizada.</p>
      </div>
    </div>
  </section>

  <section class="info-section business-theme">
    <div class="section-title">
      <h2>Sobre <span>Nosotros</span></h2>
    </div>
    <div class="grid-features">
      <div class="card about">
        <i class="ph ph-rocket-launch icon-box"></i>
        <h3>Pasión por Crecer</h3>
        <p>Llevamos el color de la fuerza en nuestro ADN. Impulsamos tu negocio al límite.</p>
      </div>
      <div class="card about">
        <i class="ph ph-users-three icon-box"></i>
        <h3>Comunidad Activa</h3>
        <p>Conectamos necesidades con soluciones reales en tiempo récord.</p>
      </div>
      <div class="card about">
        <i class="ph ph-headset icon-box"></i>
        <h3>Soporte Real</h3>
        <p>Gente apasionada ayudándote a resolver problemas reales, sin robots.</p>
      </div>
    </div>
  </section>

  <footer>
    <p>&copy; 2026 EdvynKaris. Potenciando PyMEs.</p>
  </footer>

  <div class="modal-overlay" id="mainModal">
    <div class="modal-box">
      <button class="close-modal" onclick="closeModal()"><i class="ph ph-x"></i></button>
      <h2 class="modal-title" id="modalTitle">Bienvenido</h2>
      <p class="modal-desc" id="modalDesc">Elige una opción</p>
      <div class="modal-actions">
        <a href="login.php" class="btn-option btn-secondary" id="btnLogin">
          <i class="ph ph-sign-in"></i> Entrar
        </a>
        <a href="registro.php" class="btn-option" id="btnRegister" style="color:#fff;">
          <i class="ph ph-user-plus"></i> Registrarse
        </a>
      </div>
    </div>
  </div>

<script>
// --- LOGICA NAVBAR ---
const navbar = document.getElementById('stickyNav');
const mainContent = document.getElementById('mainContent');
window.addEventListener('scroll', () => {
  if (window.scrollY > window.innerHeight - 100) navbar.classList.add('visible');
  else navbar.classList.remove('visible');
});

// --- LOGICA DARK MODE ---
const themeBtn = document.getElementById('themeBtn');
const body = document.body;
const icon = themeBtn.querySelector('i');

// Revisar preferencia guardada
if(localStorage.getItem('theme') === 'dark'){
  body.classList.add('dark-mode');
  icon.classList.replace('ph-moon', 'ph-sun');
}

themeBtn.addEventListener('click', () => {
  body.classList.toggle('dark-mode');
  
  if(body.classList.contains('dark-mode')){
    icon.classList.replace('ph-moon', 'ph-sun');
    localStorage.setItem('theme', 'dark');
  } else {
    icon.classList.replace('ph-sun', 'ph-moon');
    localStorage.setItem('theme', 'light');
  }
});

// --- LOGICA MODAL ---
const modalOverlay = document.getElementById('mainModal');
const modalTitle = document.getElementById('modalTitle');
const modalDesc = document.getElementById('modalDesc');
const btnLogin = document.getElementById('btnLogin');
const btnRegister = document.getElementById('btnRegister');

function openModal(type) {
  modalOverlay.classList.add('active');
  if(type === 'cliente') {
    modalTitle.innerText = "Hola, Cliente";
    modalDesc.innerText = "¿Listo para pedir?";
    // Boton toma el color del tema actual (variable CSS)
    btnRegister.style.background = "var(--theme-client)";
    // Links
    btnLogin.href = "login.php?type=cliente";
    btnRegister.href = "registro.php?type=cliente";
  } else {
    modalTitle.innerText = "Portal Negocios";
    modalDesc.innerText = "Gestiona tu empresa";
    // Boton toma el color del tema actual (variable CSS)
    btnRegister.style.background = "var(--theme-business)";
    // Links
    btnLogin.href = "login.php?type=pyme";
    btnRegister.href = "registro.php?type=pyme";
  }
}

function closeModal() { modalOverlay.classList.remove('active'); }
modalOverlay.addEventListener('click', (e) => { if (e.target === modalOverlay) closeModal(); });
</script>

</body>
</html>
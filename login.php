<?php
session_start();
require '../config/db.php';

// --- FUNCIONES DE SEGURIDAD (ANTI-FUERZA BRUTA) ---
function checkRateLimit($conn, $ip) {
    $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['attempts'];
}

function recordLoginAttempt($conn, $ip) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
}

function clearLoginAttempts($conn, $ip) {
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
}

// 1. Configuración Inicial
$type = isset($_GET['type']) ? $_GET['type'] : 'cliente'; 
// Tipos válidos: 'cliente', 'pyme', 'admin'

$ip_usuario = $_SERVER['REMOTE_ADDR'];

// Configuración Visual según quién entra
if ($type === 'admin') {
    $themeColor = '#f59e0b'; // Dorado para el CEO
    $title = 'Acceso Corporativo';
    $bgImage = 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070'; // Edificio corporativo
    $bgOverlay = 'linear-gradient(135deg, rgba(245,158,11,0.9), rgba(15,17,21,0.9))';
} elseif ($type === 'pyme') {
    $themeColor = '#dc2626';
    $title = 'Portal Negocios';
    $bgImage = 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?q=80&w=2032';
    $bgOverlay = 'linear-gradient(135deg, rgba(185,28,28,0.9), rgba(15,17,21,0.8))';
} else {
    $themeColor = '#00bfa5';
    $title = 'Inicia Sesión';
    $bgImage = 'https://images.unsplash.com/photo-1556742049-0cfed4f7a07d?q=80&w=2070';
    $bgOverlay = 'linear-gradient(135deg, rgba(0,191,165,0.9), rgba(15,17,21,0.8))';
}

$msg = "";
$shakeError = false;

// 2. Procesar Login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Anti-Fuerza Bruta
    if (checkRateLimit($conn, $ip_usuario) >= 5) { // Damos 5 intentos al admin
        $msg = "<div class='alert error'>Bloqueo de seguridad temporal.</div>";
        $shakeError = true;
    } else {
        $loginSuccess = false;
        $userData = null;

        // --- SELECCION DE LÓGICA SEGÚN TIPO ---
        if ($type === 'admin') {
            // LÓGICA CEO
            $stmt = $conn->prepare("SELECT id, nombre, password, rol FROM admins WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $loginSuccess = true;
                    $userData = $row;
                    $userData['type'] = 'admin';
                }
            }
        } elseif ($type === 'pyme') {
            // LÓGICA PYME
            $stmt = $conn->prepare("SELECT id, empresa_id, nombre, password, rol FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $loginSuccess = true;
                    $userData = $row;
                    $userData['type'] = 'pyme';
                }
            }
        } else {
            // LÓGICA CLIENTE
            $stmt = $conn->prepare("SELECT id, nombre, password FROM clientes WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $loginSuccess = true;
                    $userData = $row;
                    $userData['type'] = 'cliente';
                }
            }
        }

        if ($loginSuccess) {
            session_regenerate_id(true);
            clearLoginAttempts($conn, $ip_usuario);
            
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['nombre'] = $userData['nombre'];
            $_SESSION['type'] = $userData['type'];
            
            // Redirecciones
            if ($type === 'admin') {
                $_SESSION['rol'] = $userData['rol'];
                header("Location: ../CEO/panel_ceo.php");
            } elseif ($type === 'pyme') {
                $_SESSION['rol'] = $userData['rol'];
                $_SESSION['empresa_id'] = $userData['empresa_id'];
                header("Location: ../PyME/panel_pyme.php");
            } else {
                header("Location: panel_cliente.php");
            }
            exit;

        } else {
            recordLoginAttempt($conn, $ip_usuario);
            $msg = "<div class='alert error'>Credenciales incorrectas.</div>";
            $shakeError = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login <?php echo ucfirst($type); ?> | Karis</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Outfit:wght@700;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
:root {
  --theme: <?php echo $themeColor; ?>;
  --bg-dark: #0f1115;
  --bg-card: #181b21;
  --text-main: #ffffff;
  --text-muted: #9ca3af;
}
body { margin: 0; font-family: 'Inter', sans-serif; background-color: var(--bg-dark); color: var(--text-main); height: 100vh; overflow: hidden; display: flex; }
.split-screen { display: flex; width: 100%; height: 100%; }
.side-image { flex: 1; background: url('<?php echo $bgImage; ?>') center/cover no-repeat; position: relative; display: flex; flex-direction: column; justify-content: space-between; padding: 40px; }
.side-image::before { content: ""; position: absolute; inset: 0; background: <?php echo $bgOverlay; ?>; }
.brand-area { position: relative; z-index: 2; font-family: 'Outfit', sans-serif; }
.brand-area h1 { font-size: 3rem; margin: 0; }
.brand-area p { font-size: 1.2rem; opacity: 0.9; margin-top: 10px; }
.side-form { flex: 0 0 500px; background: var(--bg-card); display: flex; flex-direction: column; justify-content: center; padding: 40px; position: relative; box-shadow: -10px 0 30px rgba(0,0,0,0.5); }
h2 { font-family: 'Outfit', sans-serif; color: var(--theme); margin-bottom: 20px; font-size: 2rem; }
.subtitle { color: var(--text-muted); margin-bottom: 30px; font-size: 0.95rem; }
.input-group { margin-bottom: 20px; position: relative; }
.input-group label { display: block; margin-bottom: 8px; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
.input-group input { width: 100%; padding: 14px 16px; padding-left: 45px; background: #0f1115; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: #fff; font-size: 1rem; outline: none; transition: 0.3s; box-sizing: border-box; }
.input-group i.icon-left { position: absolute; left: 16px; top: 42px; color: var(--text-muted); font-size: 1.2rem; transition: 0.3s; }
.toggle-password { position: absolute; right: 16px; top: 42px; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; }
.input-group input:focus { border-color: var(--theme); box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1); }
.btn-submit { width: 100%; padding: 16px; background: var(--theme); color: #fff; border: none; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: 0.3s; margin-top: 10px; }
.btn-submit:hover { filter: brightness(1.1); transform: translateY(-2px); }
.links { margin-top: 25px; text-align: center; font-size: 0.9rem; color: var(--text-muted); }
.links a { color: var(--theme); text-decoration: none; font-weight: 600; }
.alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
.alert.error { background: rgba(220,38,38,0.2); color: #fca5a5; border: 1px solid #7f1d1d; }
@keyframes shake { 0%, 100% { transform: translateX(0); } 10%, 90% { transform: translateX(-5px); } 20%, 80% { transform: translateX(5px); } }
.shake { animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both; border-color: #dc2626 !important; }
@media (max-width: 900px) { .split-screen { flex-direction: column; overflow-y: auto; } .side-image { flex: 0 0 150px; padding: 20px; } .side-form { flex: 1; width: 100%; padding: 30px; } }
</style>
</head>
<body>
<div class="split-screen">
  <div class="side-image">
    <div class="brand-area">
      <h1>KARIS</h1>
      <p><?php echo ($type === 'admin') ? 'Gestión Corporativa' : 'Bienvenido de vuelta.'; ?></p>
    </div>
    <div style="position: relative; z-index: 2;">
        <a href="index.php" style="color:white; text-decoration:none; display:flex; align-items:center; gap:8px; font-size:0.9rem; opacity:0.8;">
            <i class="ph ph-arrow-left"></i> Volver al inicio
        </a>
    </div>
  </div>
  <div class="side-form">
    <?php echo $msg; ?>
    <h2><?php echo $title; ?></h2>
    <p class="subtitle">Ingresa tus credenciales <?php echo ($type === 'admin') ? 'de administrador' : ''; ?>.</p>
    <form method="POST" action="login.php?type=<?php echo $type; ?>">
      <div class="input-group">
        <label>Correo <?php echo ($type === 'admin') ? 'Corporativo' : 'Electrónico'; ?></label>
        <input type="email" name="email" class="<?php echo $shakeError ? 'shake' : ''; ?>" placeholder="nombre@edvynkaris.com" required>
        <i class="ph ph-envelope-simple icon-left"></i>
      </div>
      <div class="input-group">
        <label>Contraseña</label>
        <input type="password" name="password" id="passwordInput" class="<?php echo $shakeError ? 'shake' : ''; ?>" placeholder="Tu contraseña maestra" required>
        <i class="ph ph-lock-key icon-left"></i>
        <i class="ph ph-eye toggle-password" onclick="togglePassword()"></i>
      </div>
      <button type="submit" class="btn-submit">Ingresar</button>
      <?php if($type !== 'admin'): ?>
      <div class="links">¿No tienes cuenta? <a href="registro.php?type=<?php echo $type; ?>">Regístrate aquí</a></div>
      <?php endif; ?>
    </form>
  </div>
</div>
<script>
function togglePassword() {
    var input = document.getElementById("passwordInput");
    var icon = document.querySelector(".toggle-password");
    if (input.type === "password") { input.type = "text"; icon.classList.remove("ph-eye"); icon.classList.add("ph-eye-slash"); } 
    else { input.type = "password"; icon.classList.remove("ph-eye-slash"); icon.classList.add("ph-eye"); }
}
</script>
</body>
</html>
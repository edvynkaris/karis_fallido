<?php
require '../config/db.php';

// 1. Capturar tipo (Pyme o Cliente)
$type = isset($_GET['type']) ? $_GET['type'] : 'cliente';
$isPyme = ($type === 'pyme');

// 2. Configuración Visual
$themeColor = $isPyme ? '#dc2626' : '#00bfa5'; // Rojo vs Teal
$title = $isPyme ? 'Registra tu Negocio' : 'Únete a Karis';
$bgImage = $isPyme 
    ? 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?q=80&w=2032' 
    : 'https://images.unsplash.com/photo-1556742049-0cfed4f7a07d?q=80&w=2070';

$msg = "";

// 3. PROCESAMIENTO PHP
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Encriptar password común para ambos casos
    $password = password_hash($_POST['admin_password'], PASSWORD_BCRYPT);
    
    if ($isPyme) {
        // --- LOGICA PYME (2 Pasos en 1 Transacción) ---
        
        // A. Datos Empresa
        $emp_nombre    = trim($_POST['empresa_nombre']);
        $emp_rut       = trim($_POST['empresa_rut']);
        $emp_rubro     = trim($_POST['empresa_rubro']);
        $emp_direccion = trim($_POST['empresa_direccion']);
        $emp_email     = trim($_POST['empresa_email']); // Email de contacto empresa
        $emp_telefono  = trim($_POST['empresa_telefono']); // Telefono empresa

        // B. Datos Admin
        $adm_nombre    = trim($_POST['admin_nombre']);
        $adm_email     = trim($_POST['admin_email']); // Email para loguearse
        $adm_telefono  = trim($_POST['admin_telefono']); // Telefono personal

        // Validar si el email del ADMIN ya existe
        $check = $conn->query("SELECT id FROM usuarios WHERE email = '$adm_email'");
        
        if($check->num_rows > 0){
            $msg = "<div class='alert error'>El correo del administrador ya está registrado.</div>";
        } else {
            // TRANSACCION: Crear Empresa -> Obtener ID -> Crear Admin
            $conn->begin_transaction();

            try {
                // 1. Insertar Empresa (Con todos los datos nuevos)
                $stmtEmp = $conn->prepare("INSERT INTO empresas (nombre, rut_empresa, rubro, direccion, email, telefono, estado) VALUES (?, ?, ?, ?, ?, ?, 'activo')");
                $stmtEmp->bind_param("ssssss", $emp_nombre, $emp_rut, $emp_rubro, $emp_direccion, $emp_email, $emp_telefono);
                $stmtEmp->execute();
                $empresa_id = $conn->insert_id;

                // 2. Insertar Usuario Admin vinculado
                $stmtUser = $conn->prepare("INSERT INTO usuarios (empresa_id, nombre, email, password, telefono, rol, direccion) VALUES (?, ?, ?, ?, ?, 'admin', ?)");
                // Nota: Usamos la misma dirección de la empresa para el admin por defecto, o podrías pedirla aparte
                $stmtUser->bind_param("isssss", $empresa_id, $adm_nombre, $adm_email, $password, $adm_telefono, $emp_direccion);
                $stmtUser->execute();

                $conn->commit();
                header("Location: login.php?type=pyme&success=1");
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                $msg = "<div class='alert error'>Error crítico: " . $e->getMessage() . "</div>";
            }
        }

    } else {
        // --- LOGICA CLIENTE (Simple, 1 Paso) ---
        $nombre   = trim($_POST['admin_nombre']); // Reutilizamos name='admin_nombre' para simplificar JS
        $email    = trim($_POST['admin_email']);
        $telefono = trim($_POST['admin_telefono']);

        $check = $conn->query("SELECT id FROM clientes WHERE email = '$email'");
        
        if($check->num_rows > 0){
            $msg = "<div class='alert error'>El correo ya existe.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO clientes (nombre, email, password, telefono) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre, $email, $password, $telefono);
            
            if ($stmt->execute()) {
                header("Location: login.php?type=cliente&success=1");
                exit;
            } else {
                $msg = "<div class='alert error'>Error: " . $conn->error . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?> | Karis</title>
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

body {
  margin: 0; font-family: 'Inter', sans-serif;
  background-color: var(--bg-dark); color: var(--text-main);
  height: 100vh; overflow: hidden;
  display: flex;
}

/* Layout */
.split-screen { display: flex; width: 100%; height: 100%; }
.side-image {
  flex: 1;
  background: url('<?php echo $bgImage; ?>') center/cover no-repeat;
  position: relative;
  display: flex; flex-direction: column; justify-content: space-between; padding: 40px;
}
.side-image::before {
  content: ""; position: absolute; inset: 0;
  background: <?php echo $isPyme 
      ? 'linear-gradient(135deg, rgba(185,28,28,0.95), rgba(15,17,21,0.85))' 
      : 'linear-gradient(135deg, rgba(0,191,165,0.95), rgba(15,17,21,0.85))'; ?>;
}

.brand-area { position: relative; z-index: 2; font-family: 'Outfit', sans-serif; }
.brand-area h1 { font-size: 3rem; margin: 0; }

.side-form {
  flex: 0 0 550px; /* Un poco más ancho para los pasos */
  background: var(--bg-card);
  display: flex; flex-direction: column; justify-content: center;
  padding: 40px; position: relative;
  box-shadow: -10px 0 30px rgba(0,0,0,0.5);
}

/* Estilos de Pasos (Stepper) solo para Pymes */
.stepper-dots {
  display: flex; gap: 8px; margin-bottom: 20px;
}
.dot {
  width: 10px; height: 10px; border-radius: 50%; background: #333; transition: 0.3s;
}
.dot.active { background: var(--theme); transform: scale(1.2); }

/* Formularios */
h2 { font-family: 'Outfit', sans-serif; color: var(--theme); margin-bottom: 10px; font-size: 2rem; }
.step-title { color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; display: block; }

.input-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; } /* Para poner inputs lado a lado */

.input-group { margin-bottom: 15px; position: relative; }
.input-group label { display: block; margin-bottom: 6px; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); }
.input-group input, .input-group select {
  width: 100%; padding: 12px 14px; padding-left: 40px;
  background: #0f1115; border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px; color: #fff; font-size: 0.95rem; outline: none; transition: 0.3s;
}
.input-group i {
  position: absolute; left: 14px; top: 38px;
  color: var(--text-muted); font-size: 1.1rem; transition: 0.3s;
}
.input-group input:focus { border-color: var(--theme); }
.input-group input:focus + i { color: var(--theme); }

/* Botones */
.btn-submit, .btn-next {
  width: 100%; padding: 15px;
  background: var(--theme); color: #fff; border: none; border-radius: 12px;
  font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px;
}
.btn-back {
  background: transparent; border: 1px solid rgba(255,255,255,0.2); color: #fff;
  padding: 15px; border-radius: 12px; font-weight: 600; cursor: pointer; width: 100%;
}
.btn-submit:hover, .btn-next:hover { filter: brightness(1.1); transform: translateY(-2px); }

.links { margin-top: 25px; text-align: center; font-size: 0.9rem; color: #666; }
.links a { color: var(--theme); text-decoration: none; font-weight: 600; }
.alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; border: 1px solid transparent; }
.alert.error { background: rgba(220,38,38,0.2); color: #fca5a5; border-color: #7f1d1d; }

/* Animación de cambio de paso */
.form-step { display: none; animation: fadeIn 0.4s; }
.form-step.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }

@media (max-width: 900px) {
  .split-screen { flex-direction: column; overflow-y: auto; }
  .side-image { flex: 0 0 120px; padding: 20px; }
  .side-form { flex: 1; width: 100%; padding: 25px; height: auto; }
  .input-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="split-screen">
  
  <div class="side-image">
    <div class="brand-area">
      <h1>KARIS</h1>
      <p>Construye el futuro.</p>
    </div>
    <div style="position: relative; z-index: 2;">
        <a href="index.php" style="color:white; text-decoration:none; display:flex; align-items:center; gap:8px; font-size:0.9rem; opacity:0.8;">
            <i class="ph ph-arrow-left"></i> Inicio
        </a>
    </div>
  </div>

  <div class="side-form">
    <?php echo $msg; ?>

    <form method="POST" action="" id="mainForm">
      
      <?php if($isPyme): ?>
      
        <div class="stepper-dots">
            <div class="dot active" id="dot1"></div>
            <div class="dot" id="dot2"></div>
        </div>

        <div class="form-step active" id="step1">
            <h2>Registra tu Pyme</h2>
            <span class="step-title">Paso 1 de 2: Información del Negocio</span>
            
            <div class="input-group">
                <label>Nombre de Fantasía</label>
                <input type="text" name="empresa_nombre" id="e_nombre" placeholder="Ej: Sushi King">
                <i class="ph ph-storefront"></i>
            </div>

            <div class="input-grid">
                <div class="input-group">
                    <label>RUT Empresa</label>
                    <input type="text" name="empresa_rut" id="e_rut" placeholder="76.xxx.xxx-x">
                    <i class="ph ph-identification-card"></i>
                </div>
                <div class="input-group">
                    <label>Rubro</label>
                    <select name="empresa_rubro" id="e_rubro" style="padding-left:40px;">
                        <option value="">Selecciona...</option>
                        <option value="Gastronomía">Gastronomía</option>
                        <option value="Retail">Retail / Tienda</option>
                        <option value="Servicios">Servicios</option>
                        <option value="Belleza">Salud y Belleza</option>
                        <option value="Otro">Otro</option>
                    </select>
                    <i class="ph ph-tag"></i>
                </div>
            </div>

            <div class="input-group">
                <label>Dirección Comercial</label>
                <input type="text" name="empresa_direccion" id="e_direccion" placeholder="Calle, Número, Comuna">
                <i class="ph ph-map-pin"></i>
            </div>

            <div class="input-grid">
                <div class="input-group">
                    <label>Email Contacto (Público)</label>
                    <input type="email" name="empresa_email" id="e_email" placeholder="contacto@empresa.cl">
                    <i class="ph ph-envelope"></i>
                </div>
                <div class="input-group">
                    <label>Teléfono Negocio</label>
                    <input type="tel" name="empresa_telefono" id="e_telefono" placeholder="+56 2 2222 2222">
                    <i class="ph ph-phone"></i>
                </div>
            </div>

            <button type="button" class="btn-next" onclick="goToStep(2)">Continuar <i class="ph ph-arrow-right"></i></button>
        </div>

        <div class="form-step" id="step2">
            <h2>Cuenta Admin</h2>
            <span class="step-title">Paso 2 de 2: Tus Datos de Acceso</span>

            <div class="input-group">
                <label>Tu Nombre Completo</label>
                <input type="text" name="admin_nombre" id="a_nombre" placeholder="Juan Pérez">
                <i class="ph ph-user"></i>
            </div>

            <div class="input-group">
                <label>Email de Acceso (Login)</label>
                <input type="email" name="admin_email" id="a_email" placeholder="tu@email.com">
                <i class="ph ph-envelope-simple"></i>
            </div>

            <div class="input-group">
                <label>Tu Teléfono Celular</label>
                <input type="tel" name="admin_telefono" id="a_telefono" placeholder="+56 9 xxxx xxxx">
                <i class="ph ph-device-mobile"></i>
            </div>

            <div class="input-group">
                <label>Crear Contraseña</label>
                <input type="password" name="admin_password" id="a_password" placeholder="Mínimo 6 caracteres">
                <i class="ph ph-lock-key"></i>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="button" class="btn-back" onclick="goToStep(1)">Atrás</button>
                <button type="submit" class="btn-submit">Finalizar Registro</button>
            </div>
        </div>

      <?php else: ?>
        
        <div class="form-step active">
            <h2>Únete a Karis</h2>
            <span class="step-title">Crea tu cuenta gratis</span>
            
            <div class="input-group">
                <label>Nombre Completo</label>
                <input type="text" name="admin_nombre" placeholder="Tu nombre" required>
                <i class="ph ph-user"></i>
            </div>
            <div class="input-group">
                <label>Correo Electrónico</label>
                <input type="email" name="admin_email" placeholder="nombre@correo.com" required>
                <i class="ph ph-envelope-simple"></i>
            </div>
            <div class="input-group">
                <label>Teléfono</label>
                <input type="tel" name="admin_telefono" placeholder="+56 9 ..." required>
                <i class="ph ph-phone"></i>
            </div>
            <div class="input-group">
                <label>Contraseña</label>
                <input type="password" name="admin_password" placeholder="******" required>
                <i class="ph ph-lock-key"></i>
            </div>
            <button type="submit" class="btn-submit">Registrarme</button>
        </div>

      <?php endif; ?>

      <div class="links">
        ¿Ya tienes cuenta? <a href="login.php?type=<?php echo $type; ?>">Inicia Sesión</a>
      </div>
    </form>
  </div>
</div>

<script>
function goToStep(step) {
    // Validacion simple para no avanzar vacio
    if(step === 2) {
        const nombre = document.getElementById('e_nombre').value;
        const rut = document.getElementById('e_rut').value;
        if(nombre === "" || rut === "") {
            alert("Por favor completa al menos el nombre y RUT del negocio.");
            return;
        }
    }

    // Ocultar todos
    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.dot').forEach(el => el.classList.remove('active'));

    // Mostrar actual
    document.getElementById('step' + step).classList.add('active');
    document.getElementById('dot' + step).classList.add('active');
}
</script>

</body>
</html>
<?php
// panel_cliente.php
session_start();
require_once '../config/db.php'; // Ajusta la ruta si 'config' está un nivel atrás o en el mismo

// 1. SEGURIDAD: Verificar sesión y rol
// En login.php definiste: $_SESSION['type'] = 'cliente' y $_SESSION['user_id']
if (!isset($_SESSION['user_id']) || $_SESSION['type'] !== 'cliente') {
    header("Location: login.php?type=cliente");
    exit();
}

$user_id = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['nombre'];
$mensaje = "";

// 2. ACTUALIZAR PERFIL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nuevo_nombre = trim($_POST['nombre']);
    $nuevo_telefono = trim($_POST['telefono']);
    $nuevo_email = trim($_POST['email']);
    $nueva_direccion = trim($_POST['direccion']);

    // Validamos que el email no esté duplicado por otro usuario (opcional pero recomendado)
    // Actualizamos tabla 'clientes' según karis.sql
    $stmt = $conn->prepare("UPDATE clientes SET nombre=?, telefono=?, email=?, direccion=? WHERE id=?");
    $stmt->bind_param("ssssi", $nuevo_nombre, $nuevo_telefono, $nuevo_email, $nueva_direccion, $user_id);

    if ($stmt->execute()) {
        $_SESSION['nombre'] = $nuevo_nombre; // Actualizar sesión
        $mensaje = "<div class='alert success'>Perfil actualizado correctamente.</div>";
    } else {
        $mensaje = "<div class='alert error'>Error al actualizar: " . $conn->error . "</div>";
    }
    $stmt->close();
}

// 3. OBTENER DATOS DEL CLIENTE
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 4. OBTENER DATOS DE EMPRESAS (Para el Mapa)
// Traemos las empresas para ubicarlas. 
// NOTA: Como no hay coordenadas, usaremos 'direccion' en el frontend.
$sql_empresas = "SELECT id, nombre, direccion, rubro, telefono FROM empresas WHERE estado = 'activo' OR estado IS NULL";
$res_empresas = $conn->query($sql_empresas);
$empresas = [];
while ($row = $res_empresas->fetch_assoc()) {
    $empresas[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Cliente | Karis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Outfit:wght@700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root {
            /* Color TEAL extraído de tu login.php para clientes */
            --theme: #00bfa5; 
            --bg-dark: #0f1115;
            --bg-card: #181b21;
            --text-main: #ffffff;
            --text-muted: #9ca3af;
            --border: rgba(255,255,255,0.1);
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
        }

        /* Layout Grid */
        .layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background-color: var(--bg-card);
            border-right: 1px solid var(--border);
            padding: 30px;
            display: flex;
            flex-direction: column;
        }

        .brand {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            color: var(--text-main);
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand span { color: var(--theme); }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-muted);
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            transition: 0.3s;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(0, 191, 165, 0.1);
            color: var(--theme);
        }
        .nav-item i { font-size: 1.2rem; }

        /* Contenido Principal */
        .main { padding: 40px; overflow-y: auto; }

        h1 { font-family: 'Outfit', sans-serif; margin-top: 0; font-size: 2rem; }
        .section-title { margin-top: 40px; margin-bottom: 20px; font-size: 1.2rem; border-bottom: 1px solid var(--border); padding-bottom: 10px; color: var(--theme); }

        /* Formularios y Cards */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .input-group { margin-bottom: 0; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: var(--text-muted); }
        .input-group input {
            width: 100%;
            padding: 12px;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: white;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box; /* Importante para que no se salga del contenedor */
        }
        .input-group input:focus { outline: none; border-color: var(--theme); }

        .btn-save {
            background: var(--theme);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 20px;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-save:hover { filter: brightness(1.1); }

        /* Mapa */
        #map {
            height: 450px;
            width: 100%;
            border-radius: 12px;
            z-index: 1; /* Para que no tape menús si los hubiera */
        }

        /* Alertas */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert.success { background: rgba(0, 191, 165, 0.1); color: var(--theme); border: 1px solid var(--theme); }
        .alert.error { background: rgba(220, 38, 38, 0.1); color: #fca5a5; border: 1px solid #7f1d1d; }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <i class="ph ph-shopping-bag-open" style="color:var(--theme);"></i>
            KARIS <span>Cliente</span>
        </div>
        
        <nav>
            <a href="#" class="nav-item active">
                <i class="ph ph-user"></i> Mi Perfil
            </a>
            <a href="#mapa-section" class="nav-item">
                <i class="ph ph-map-trifold"></i> Mapa de Locales
            </a>
            <a href="logout.php" class="nav-item" style="margin-top: auto; color: #ef4444;">
                <i class="ph ph-sign-out"></i> Cerrar Sesión
            </a>
        </nav>
    </aside>

    <main class="main">
        <h1>Hola, <?php echo htmlspecialchars($cliente['nombre']); ?></h1>
        <p style="color:var(--text-muted);">Bienvenido a tu espacio personal.</p>

        <?php echo $mensaje; ?>

        <div class="card">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
                <i class="ph ph-pencil-simple" style="font-size:1.5rem; color:var(--theme);"></i>
                <h3 style="margin:0;">Editar Información</h3>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-grid">
                    <div class="input-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($cliente['telefono']); ?>">
                    </div>
                    <div class="input-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Dirección Principal</label>
                        <input type="text" name="direccion" id="direccionInput" value="<?php echo htmlspecialchars($cliente['direccion']); ?>" placeholder="Ej: Av. Providencia 1234, Santiago">
                    </div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="ph ph-floppy-disk"></i> Guardar Cambios
                </button>
            </form>
        </div>

        <div id="mapa-section">
            <h2 class="section-title">Locales Disponibles Cerca</h2>
            <div class="card" style="padding:0; overflow:hidden;">
                <div id="map"></div>
            </div>
        </div>

    </main>
</div>

<script>
    // --- INICIALIZACIÓN DEL MAPA ---
    // Coordenadas iniciales (Por defecto: Centro de Santiago, ajusta según tu región principal)
    var map = L.map('map').setView([-33.4489, -70.6693], 13);

    // Capa visual (Dark Mode para coincidir con tu diseño)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    // Icono personalizado (Color Teal)
    var tealIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    // --- LÓGICA DE GEOCODIFICACIÓN (Porque la BD no tiene lat/long) ---
    var empresas = <?php echo json_encode($empresas); ?>;

    function ubicarEmpresa(empresa) {
        if (!empresa.direccion) return;

        // API de Nominatim (OpenStreetMap) para convertir texto a coordenadas
        var url = "https://nominatim.openstreetmap.org/search?format=json&q=" + encodeURIComponent(empresa.direccion);

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    var lat = data[0].lat;
                    var lon = data[0].lon;

                    L.marker([lat, lon], {icon: tealIcon}).addTo(map)
                        .bindPopup(`
                            <div style="color:#333; font-family:'Inter',sans-serif;">
                                <strong style="font-size:1.1rem; color:#00bfa5;">${empresa.nombre}</strong><br>
                                <span style="font-size:0.9rem; color:#666;">${empresa.rubro || 'Comercio'}</span><br>
                                <small>${empresa.direccion}</small><br>
                                ${empresa.telefono ? '📞 ' + empresa.telefono : ''}
                            </div>
                        `);
                }
            })
            .catch(err => console.error("Error geocodificando: " + empresa.nombre));
    }

    // Ejecutar ubicación para cada empresa con un pequeño delay para no saturar la API gratuita
    empresas.forEach((empresa, index) => {
        setTimeout(() => {
            ubicarEmpresa(empresa);
        }, index * 800); // 800ms de retraso entre cada petición
    });

</script>

</body>
</html>
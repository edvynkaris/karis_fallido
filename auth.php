<?php
// auth.php - Sistema de Control de Acceso Karis

function tiene_permiso($permiso_requerido) {
    // 1. Si es ADMIN (Dueño), tiene permiso para TODO (Modo Dios)
    if ($_SESSION['rol'] === 'admin') {
        return true;
    }

    // 2. Si es VENDEDOR, revisamos su lista específica
    if (isset($_SESSION['permisos_usuario'])) {
        $mis_permisos = $_SESSION['permisos_usuario']; // Array cargado al login
        
        // Si el array contiene el permiso exacto, pasa
        if (in_array($permiso_requerido, $mis_permisos)) {
            return true;
        }
    }

    // 3. Si no cumple nada, denegado
    return false;
}

// Función auxiliar para bloquear pantalla si no tiene permiso (Redirección forzada)
function exigir_permiso($permiso) {
    if (!tiene_permiso($permiso)) {
        die("<div style='font-family:sans-serif; text-align:center; padding:50px; color:#dc2626;'>
                <h1>⛔ Acceso Denegado</h1>
                <p>No tienes permiso para realizar esta acción: <b>$permiso</b></p>
                <a href='panel_pyme.php'>Volver al inicio</a>
             </div>");
    }
}
?>
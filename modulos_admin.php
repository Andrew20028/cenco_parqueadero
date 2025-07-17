<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
date_default_timezone_set('America/Bogota'); // Hora local Colombia

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: index.php");
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
    header("Location: index.php");
    exit;
}

// Obtener nombre
$nombreCompleto = $_SESSION['nombre_completo'] ?? 'Usuario';

// Saludo dinámico
$hora = date("H");
if ($hora >= 5 && $hora < 12) {
    $saludo = "¡Buenos días";
} elseif ($hora >= 12 && $hora < 18) {
    $saludo = "¡Buenas tardes";
} else {
    $saludo = "¡Buenas noches";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador - ParkSystem Cencosud</title>
    <link rel="stylesheet" href="/css/modulos_admin.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="/img/logo2.jpg" alt="Logo">
            <h1>Panel de Administrador</h1>
        </div>
        <div class="user-menu">
            <a href="?logout=1" class="logout-btn">Cerrar Sesión</a>

        </div>
    </header>

    <div class="container">


        <div class="modules">
            <div class="module users">
                <div class="icon-container users">
                    <img src="/img/G_usuarios.avif" alt="Gestión de Usuarios">
                </div>
                <h3>Gestión de Usuarios</h3>
                <p>Crea, edita y elimina usuarios del sistema</p>
                <a href="G_Usuarios.php" class="admin-btn">Administrar</a>
            </div>

            <div class="module reports">
                <div class="icon-container reports">
                    <img src="/img/RyE.png" alt="Reportes y Estadísticas">
                </div>
                <h3>Reportes y Estadísticas</h3>
                <p>Analiza el uso de espacios y genera informes</p>
                <a href="R_Estadistica.php" class="admin-btn">Ver Reportes</a>
            </div>

            <div class="module config">
                <div class="icon-container config">
                    <img src="/img/ajustes.png" alt="Configuración">
                </div>
                <h3>Configuración</h3>
                <p>Ajusta parámetros y configuraciones del sistema</p>
                <a href="config.php" class="admin-btn">Configurar</a>
            </div>

            <div class="module parking">
                <div class="icon-container parking">
                    <img src="/img/G_parqueaderos.png" alt="Gestión de Parqueaderos">
                </div>
                <h3>Gestión de Parqueaderos</h3>
                <p>Configura espacios, pisos y áreas de parqueo</p>
                <a href="Ges_Parqueadero.php" class="admin-btn">Administrar</a>
            </div>

            <div class="module reservations">
                <div class="icon-container reservations">
                    <img src="/img/Reportes.avif" alt="Reservas Activas">
                </div>
                <h3>Reservas Activas</h3>
                <p>Gestiona todas las reservas actuales del sistema</p>
                <a href="R_Activas.php" class="admin-btn">Ver Reservas</a>
            </div>
        </div>
    </div>
</body>
</html>
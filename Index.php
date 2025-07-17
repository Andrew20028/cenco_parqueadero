<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'Conexion.php';

$username = isset($_SESSION['cached_username']) ? $_SESSION['cached_username'] : '';
$password = isset($_SESSION['cached_password']) ? $_SESSION['cached_password'] : '';
$remember = isset($_SESSION['cached_remember']) ? $_SESSION['cached_remember'] : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = htmlspecialchars($_POST['usuario']);
    $contrasena = htmlspecialchars($_POST['password']);
    $remember = isset($_POST['remember']);

    // Guardar en caché (sesión)
    $_SESSION['cached_username'] = $usuario;
    $_SESSION['cached_password'] = $contrasena;
    $_SESSION['cached_remember'] = $remember;

    // Preparar y ejecutar consulta
    $query = $conn->prepare("SELECT u.id, u.rol_id, u.Contraseña, r.nombreRol 
                             FROM usuarios u
                             JOIN roles r ON u.rol_id = r.id 
                             WHERE u.usuario = ?");
    $query->bind_param("s", $usuario);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($contrasena, $user['Contraseña'])) {
            // Guardar datos del usuario en la sesión
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['rol_id'] = $user['rol_id'];
            $_SESSION['rol_nombre'] = $user['nombreRol'];

            // Redirigir según rol (asegúrate que no se ha enviado salida antes de esto)
            switch ($user['rol_id']) {
                case 1: // Admin
                    header('Location: modulos_admin.php');
                    exit;
                case 2: // Usuario
                    header('Location: modulos_usuario.php');
                    exit;
                case 3: // Portero
                    header('Location: modulos_portero.php');
                    exit;
                default:
                    $error = "Rol no reconocido.";
            }
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ParkSystem - Iniciar Sesión</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="login-container">
        <h2>ParkSystem</h2>
        <h3>Sistema de Gestión de Parqueaderos</h3>
        <div class="form-container">
            <form method="POST" action="">
                <h4>Iniciar Sesión</h4>
                <?php if ($error): ?>
                    <p style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; font-size: 14px; margin: 10px 0;">
                        <?php echo htmlspecialchars($error); ?>
                    </p>
                <?php endif; ?>
                <div class="input-group">
                    <label>Usuario</label>
                    <input type="text" name="usuario" value="<?php echo htmlspecialchars($username); ?>" placeholder="Ingrese su usuario" required>
                </div>
                <div class="input-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="Ingrese su contraseña" required>
                    <a href="recuperar_contrasena.php" class="forgot-password">¿Olvidó contraseña?</a>
                </div>
                <div class="remember">
                    <input type="checkbox" name="remember" <?php echo $remember ? 'checked' : ''; ?>>
                    <label>Recordar mi sesión</label>
                </div>
                <button type="submit">Ingresar Sistema</button>
            </form>
        </div>
        <p>No tiene cuenta? <a href="con_admin.php">Contacte al administrador</a></p>
        <p>© 2025 ParkSystem. Todos los derechos reservados a CENCOSUD.</p>
    </div>
</body>
</html>

<?php
// Limpiar caché manualmente
if (isset($_GET['clear_cache'])) {
    unset($_SESSION['cached_username']);
    unset($_SESSION['cached_remember']);
    header('Location: index.php');
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>

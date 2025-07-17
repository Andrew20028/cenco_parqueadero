<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'Conexion.php';

// Establecer zona horaria de Colombia
date_default_timezone_set('America/Bogota');

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Verificar si el token es válido y no ha expirado
if ($token) {
    $query = $conn->prepare("SELECT id, reset_expires FROM usuarios WHERE reset_token = ?");
    if ($query === false) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }
    $query->bind_param("s", $token);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $expires = new DateTime($user['reset_expires']);
        $now = new DateTime();

        if ($now > $expires) {
            $error = "El enlace ha expirado.";
        } else {
            // Procesar el formulario si se envía
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nueva_contrasena = htmlspecialchars($_POST['nueva_contrasena']);

                // Validar que la contraseña no esté vacía (puedes agregar más validaciones)
                if (empty($nueva_contrasena)) {
                    $error = "Por favor, ingrese una nueva contraseña.";
                } else {
                    // Hashear la nueva contraseña
                    $contrasena_hasheada = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

                    // Actualizar la contraseña y limpiar el token
                    $update_query = $conn->prepare("UPDATE usuarios SET Contraseña = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                    if ($update_query === false) {
                        die("Error en la preparación de la actualización: " . $conn->error);
                    }
                    $update_query->bind_param("si", $contrasena_hasheada, $user['id']);
                    $update_query->execute();
                    $update_query->close();

                    $success = "Contraseña cambiada con éxito. Serás redirigido en 5 segundos...";
                }
            }
        }
    } else {
        $error = "Token inválido.";
    }
    $query->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ParkSystem - Cambiar Contraseña</title>
    <link rel="stylesheet" href="css/index.css">
    <script>
        // Redirigir a index.php después de 5 segundos si hay éxito
        window.onload = function() {
            if ('<?php echo $success; ?>' !== '') {
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 5000); // 5000 milisegundos = 5 segundos
            }
        };
    </script>
</head>
<body>
    <div class="login-container">
        <h2>ParkSystem</h2>
        <h3>Sistema de Gestión de Parqueaderos</h3>
        <div class="form-container">
            <h4>Cambiar Contraseña</h4>
            <?php if ($error): ?>
                <p style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; font-size: 14px; margin: 10px 0;">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p style="color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; font-size: 14px; margin: 10px 0;">
                    <?php echo htmlspecialchars($success); ?>
                </p>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" name="nueva_contrasena" placeholder="Ingrese su nueva contraseña" required>
                    </div>
                    <button type="submit" class="btn-primary">Cambiar Contraseña</button>
                </form>
            <?php endif; ?>
        </div>
        <p>© 2023 ParkSystem. Todos los derechos reservados.</p>
    </div>
</body>
</html>
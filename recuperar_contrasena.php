<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'Conexion.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

// Habilitar depuración solo si se pasa ?debug=1 en la URL (para desarrollo)
$debug = isset($_GET['debug']) && $_GET['debug'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = htmlspecialchars($_POST['correo']);

    // Verificar si el correo existe en la base de datos
    $query = $conn->prepare("SELECT id, Nombre_apellido FROM usuarios WHERE Correo_Electronico = ?");
    if ($query === false) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }
    $query->bind_param("s", $correo);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        // Generar un token único y tiempo de expiración
        $token = bin2hex(random_bytes(32)); // Token seguro
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expira en 1 hora

        // Guardar token y tiempo de expiración en la base de datos
        $update_query = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE id = ?");
        if ($update_query === false) {
            die("Error en la preparación de la actualización: " . $conn->error);
        }
        $update_query->bind_param("ssi", $token, $expires, $user_id);
        $update_query->execute();
        $update_query->close();

        // Configurar PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP para Gmail
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';/*smtp.office365.com para outlook */
            $mail->SMTPAuth = true;
            $mail->Username = 'andreslopez20028@gmail.com'; // Tu correo Gmail
            $mail->Password = 'izpw fhcp lhuo hhdu'; // Tu contraseña de aplicación
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Habilitar depuración solo en modo debug
            if ($debug) {
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function($str, $level) {
                    echo "Debug nivel $level; mensaje: $str<br>";
                };
            }

            // Remitente y destinatario
            $mail->setFrom('no-reply@parksystem.com', 'ParkSystem');
            $mail->addAddress($correo, $user['Nombre_apellido']);

            // Contenido del correo
            $mail->isHTML(false);
            $mail->Subject = 'Recuperacion de Contrasenia - ParkSystem';
            $reset_link = "http://localhost/parqueadero/cambio_contrasenia.php?token=" . $token;
            $mail->Body = "Hola " . $user['Nombre_apellido'] . ",\n\n"
                        . "Haga clic en el siguiente enlace para restablecer su contrasenia:\n"
                        . $reset_link . "\n\n"
                        . "Este enlace expira en 1 hora.\n\n"
                        . "Saludos,\nEquipo ParkSystem CENCOSUD";

            $mail->send();
            $success = "Se han enviado instrucciones a su correo electrónico."
                        . " Si no recibe el correo, revise su carpeta de spam o intente de nuevo más tarde.";
        } catch (Exception $e) {
            $error = "Error al enviar el correo. Intente de nuevo. Detalle: " . $mail->ErrorInfo;
        }
    } else {
        $error = "El correo electrónico no está registrado.";
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
    <title>ParkSystem - Recuperar Contraseña</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="login-container">
        <h2>ParkSystem</h2>
        <h3>Sistema de Gestión de Parqueaderos</h3>
        <div class="form-container">
            <form method="POST" action="">
                <h4>Recuperar Contraseña</h4>
                <?php if ($error): ?>
                    <p style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; font-size: 14px; margin: 10px 0;">
                        <?php echo htmlspecialchars($error); ?>
                    </p>
                <?php endif; ?>
                <?php if ($success): ?>
                    <p style="color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; font-size: 14px; margin: 10px 0;">
                        <?php echo htmlspecialchars($success); ?>
                    </p>
                <?php endif; ?>
                <p>Ingrese su correo electrónico y enviaremos instrucciones para restablecer su contraseña.</p>
                <div class="input-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo" placeholder="ejemplo@correo.com" required>
                </div>
                <button type="submit" class="btn-primary">Enviar Instrucciones</button>
                <a href="index.php" class="btn-secondary">Volver al Login</a>
            </form>
        </div>
        <p>© 2025 ParkSystem. Todos los derechos reservados a CENCOSUD.</p>
    </div>
</body>
</html>
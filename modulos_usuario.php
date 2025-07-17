<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php'); // Aquí estaba el error: antes estaba 'login.php'
    exit;
}

// Aquí puedes colocar el contenido de la página protegida
echo "<h1>Bienvenido, " . htmlspecialchars($_SESSION['rol_nombre']) . "</h1>";
?>

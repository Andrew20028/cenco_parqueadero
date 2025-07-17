<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Conexion.php';

// Conectar a la base de datos
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener todos los usuarios
$query = $conn->prepare("SELECT id, Contraseña FROM usuarios");
$query->execute();
$result = $query->get_result();

while ($user = $result->fetch_assoc()) {
    $id = $user['id'];
    $contrasena_plana = $user['Contraseña'];

    // Hashear la contraseña si no está hasheada (puedes agregar una condición para verificar)
    $contrasena_hasheada = password_hash($contrasena_plana, PASSWORD_DEFAULT);

    // Actualizar la contraseña hasheada en la base de datos
    $update_query = $conn->prepare("UPDATE usuarios SET Contraseña = ? WHERE id = ?");
    $update_query->bind_param("si", $contrasena_hasheada, $id);
    $update_query->execute();
    $update_query->close();

    echo "Usuario ID $id: Contraseña hasheada y actualizada.<br>";
}

$query->close();
$conn->close();

echo "Proceso de hash completado.";
?>
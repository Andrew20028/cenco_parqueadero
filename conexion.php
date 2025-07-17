<?php
// Parámetros de conexión
$servername = "localhost"; // Nombre del servidor (puede ser 'localhost' o una IP)
$username = "root"; // Tu usuario de la base de datos
$password = ""; // Tu contraseña de la base de datos
$database = "parqueadero"; // Nombre de tu base de datos

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $database);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

?>
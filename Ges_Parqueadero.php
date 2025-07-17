<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'conexion.php';

// Verificación de sesión y permisos
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

// --- PROCESAMIENTO DE FORMULARIOS ---
// Procesar guardado de configuración de pisos
$validation_error = '';
$nomenclatura_converted_message = '';
if (isset($_POST['guardar'])) {
    $tipo = $_POST['tipo'];
    $cantidad = (int)$_POST['cantidad_pisos'];
    $last_final = 0;

    $stmt = $conn->prepare("INSERT INTO cupos (pisos, nomenclatura, id_puesto, estado, vehiculo) VALUES (?, ?, ?, 0, ?) ON DUPLICATE KEY UPDATE pisos=VALUES(pisos), nomenclatura=VALUES(nomenclatura), id_puesto=VALUES(id_puesto), estado=VALUES(estado), vehiculo=VALUES(vehiculo)");
    $stmt->bind_param("isss", $piso, $nomenclatura, $id_puesto, $vehiculo);

    for ($i = 1; $i <= $cantidad; $i++) {
        $piso = $i;
        $nomenclatura_input = trim($_POST["nomenclatura_$i"]);
        $nomenclatura = strtoupper($nomenclatura_input); // Convertir a mayúsculas
        if ($nomenclatura_input !== $nomenclatura) {
            $nomenclatura_converted_message .= "La nomenclatura del Piso $i ($nomenclatura_input) fue convertida a mayúsculas ($nomenclatura).<br>";
        }
        $inicio = (int)$_POST["inicio_$i"];
        $fin = (int)$_POST["fin_$i"];
        $vehiculo = ($tipo == 'carros') ? 'C' : 'M';

        // Verificar si ya existe este piso
        $check_piso = $conn->query("SELECT MIN(id_puesto) as inicio, MAX(id_puesto) as fin FROM cupos WHERE pisos = $i AND vehiculo = '$vehiculo'");
        $existe = $check_piso->fetch_assoc();

        // Validaciones
        if ($inicio <= $last_final) {
            $validation_error = "El Espacio Inicial del Piso $i ($inicio) debe ser mayor al Espacio Final del Piso anterior ($last_final).";
            break;
        }
        if ($fin < $inicio) {
            $validation_error = "El Espacio Final del Piso $i ($fin) debe ser mayor o igual al Espacio Inicial ($inicio).";
            break;
        }

        if ($existe) {
            // Piso existe, actualizar espacios si fin es mayor
            $current_fin = $existe['fin'];
            if ($fin > $current_fin) {
                for ($j = $current_fin + 1; $j <= $fin; $j++) {
                    $id_puesto = $j;
                    $stmt->execute();
                }
            }
        } else {
            // Piso nuevo, insertar todos los espacios
            for ($j = $inicio; $j <= $fin; $j++) {
                $id_puesto = $j;
                $stmt->execute();
            }
        }
        $last_final = $fin;
    }
    $stmt->close();

    if (empty($validation_error)) {
        echo "<script>alert('Configuración guardada exitosamente');</script>";
    }
}
// --- MANEJO AJAX PARA CUPOS ---
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: text/html; charset=utf-8');

    $tipo = $_GET['tipo'] ?? 'carros';
    $piso = strtoupper(trim($_GET['piso'] ?? ''));
    $estado = $_GET['estado'] ?? 'todos';
    $vehiculo = $tipo === 'carros' ? 'C' : 'M';

    $where = "vehiculo = '$vehiculo'";
    if ($piso !== '' && $piso !== 'TODOS') {
        $where .= " AND nomenclatura = '" . $conn->real_escape_string($piso) . "'";
    }
    if ($estado !== 'todos') {
        $where .= " AND estado = " . (int)$estado;
    }

    $query = "SELECT * FROM cupos WHERE $where ORDER BY " . ($piso !== 'TODOS' ? 'id_puesto' : 'nomenclatura, id_puesto');
    $result = $conn->query($query);
    $cupos = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($cupos as $cupo) {
        $color = match ((int)$cupo['estado']) {
            0 => 'green',
            1 => 'red',
            2 => 'purple',
            3 => 'yellow',
            default => 'gray'
        };
        $nomenclatura = htmlspecialchars($cupo['nomenclatura']);
        $id = (int)$cupo['id_puesto'];
        $label = ($piso !== 'TODOS') ? $id : "$nomenclatura-$id";
        echo "<div class=\"$color\" onclick=\"ocuparEspacio('$nomenclatura', $id)\">$label</div>";
    }
    exit;
}
// Procesar configuración de estados predefinidos
if (isset($_POST['aplicar_estado'])) {
    $nomenclatura = strtoupper(trim($_POST['nomenclatura_piso'])); // Asegurar mayúsculas
    $inicio = $_POST['desde_espacio'];
    $fin = $_POST['hasta_espacio'];
    $estado = $_POST['estado_predeterminado'];
    $nombre = $_POST['nombre_reservado'];
    $motivo = $_POST['motivo_mantenimiento'];

    $stmt = $conn->prepare("UPDATE cupos SET estado = ?, nombre = ?, motivo = ? WHERE nomenclatura = ? AND id_puesto BETWEEN ? AND ?");
    $stmt->bind_param("isssii", $estado, $nombre, $motivo, $nomenclatura, $inicio, $fin);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Estado aplicado exitosamente');</script>";
}

// Procesar ocupación de espacio
if (isset($_POST['ocupar'])) {
    $nomenclatura = strtoupper(trim($_POST['nomenclatura_ocupar'])); // Asegurar mayúsculas
    $id_puesto = $_POST['id_puesto_ocupar'];
    $nombre = $_POST['nombre'];
    $placa = $_POST['placa'];
    $contacto = $_POST['contacto'];

    $stmt = $conn->prepare("UPDATE cupos SET estado = 1, nombre = ?, placa = ?, contacto = ? WHERE nomenclatura = ? AND id_puesto = ? AND estado = 0");
    $stmt->bind_param("sssii", $nombre, $placa, $contacto, $nomenclatura, $id_puesto);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Espacio ocupado exitosamente');</script>";
}

// --- DATOS PARA RENDERIZADO COMPLETO ---
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'carros';
$piso_seleccionado = isset($_GET['piso']) ? strtoupper(trim($_GET['piso'])) : '';
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : '';

$where = "vehiculo = '" . ($tipo == 'carros' ? 'C' : 'M') . "'";
if ($piso_seleccionado && $piso_seleccionado != 'TODOS') {
    $where .= " AND nomenclatura = '$piso_seleccionado'";
}
if ($estado_filtro && $estado_filtro != 'todos') {
    $where .= " AND estado = '$estado_filtro'";
}
$result = $conn->query("SELECT * FROM cupos WHERE $where");
$cupos = $result->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$estadisticas = ['disponibles' => 0, 'ocupados' => 0, 'reservados' => 0, 'mantenimiento' => 0];
foreach ($cupos as $cupo) {
    $estado = $cupo['estado'];
    if ($estado == 0) $estadisticas['disponibles']++;
    elseif ($estado == 1) $estadisticas['ocupados']++;
    elseif ($estado == 2) $estadisticas['reservados']++;
    elseif ($estado == 3) $estadisticas['mantenimiento']++;
}
// Procesar eliminación de cupos individuales
if (isset($_POST['eliminar'])) {
    $nomenclatura = strtoupper(trim($_POST['nomenclatura_eliminar'])); // Asegurar mayúsculas
    $id_puesto = $_POST['id_puesto_eliminar'];
    $result = $conn->query("SELECT estado FROM cupos WHERE nomenclatura = '$nomenclatura' AND id_puesto = $id_puesto");
    if ($result->num_rows > 0 && $result->fetch_assoc()['estado'] == 0) {
        $conn->query("DELETE FROM cupos WHERE nomenclatura = '$nomenclatura' AND id_puesto = $id_puesto");
        echo "<script>alert('Cupo eliminado exitosamente');</script>";
    } else {
        echo "<script>alert('No se puede eliminar: el cupo no existe o está ocupado');</script>";
    }
}

// Procesar eliminación de pisos completos
if (isset($_POST['eliminar_piso'])) {
    $nomenclatura_piso = strtoupper(trim($_POST['nomenclatura_piso_eliminar'])); // Asegurar mayúsculas
    
    $check_result = $conn->query("SELECT COUNT(*) as ocupados FROM cupos WHERE nomenclatura = '$nomenclatura_piso' AND estado != 0");
    $ocupados = $check_result->fetch_assoc()['ocupados'];
    
    if ($ocupados > 0) {
        echo "<script>alert('No se puede eliminar el piso: hay $ocupados espacios ocupados, reservados o en mantenimiento');</script>";
    } else {
        $conn->query("DELETE FROM cupos WHERE nomenclatura = '$nomenclatura_piso'");
        echo "<script>alert('Piso eliminado exitosamente');</script>";
    }
}


// Obtener placas
$placas_result = $conn->query("SELECT * FROM placas WHERE usuario_id = " . $_SESSION['usuario_id'] . " AND tipo_vehiculo = '" . ($tipo == 'carros' ? 'carro' : 'moto') . "'");
$placas = $placas_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Parqueadero</title>
    <link rel="stylesheet" href="/css/Ges_parqueadero.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="/img/logo2.jpg" alt="Logo">
            <h1>Panel de Administrador</h1>
        </div>
        <div class="user-menu">
            <a href="modulos_admin.php" class="home-btn">Inicio</a>
            <a href="?logout=1" class="logout-btn">Cerrar Sesión</a>
        </div>
    </header>

    <div class="container">
        <!-- Configuración de Pisos -->
        <h2>Configuración de Pisos</h2>
        <div class="nav-tabs">
            <a href="?tipo=carros" class="<?= $tipo == 'carros' ? 'active' : '' ?>">Carros</a>
            <a href="?tipo=motos" class="<?= $tipo == 'motos' ? 'active' : '' ?>">Motos</a>
        </div>
        
        <?php
        // Obtener pisos existentes
        $pisos_existentes = [];
        $result_pisos = $conn->query("SELECT DISTINCT pisos, nomenclatura, MIN(id_puesto) as inicio, MAX(id_puesto) as fin FROM cupos WHERE vehiculo = '" . ($tipo == 'carros' ? 'C' : 'M') . "' GROUP BY pisos, nomenclatura ORDER BY pisos");
        while ($piso_data = $result_pisos->fetch_assoc()) {
            $pisos_existentes[$piso_data['pisos']] = $piso_data;
        }
        $default_cantidad = count($pisos_existentes) > 0 ? count($pisos_existentes) : 1;
        ?>
        
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>Cantidad de Pisos para <?= ucfirst($tipo) ?>:</label>
                    <input type="number" name="cantidad_pisos" min="1" value="<?= isset($_POST['cantidad_pisos']) ? $_POST['cantidad_pisos'] : $default_cantidad ?>" required>
                </div>
            </div>
            <div class="btn-group">
                <button type="submit" name="generar_pisos">Generar Pisos</button>
            </div>
            
            <?php if (isset($_POST['generar_pisos']) || isset($_POST['guardar'])) : ?>
                <?php $cantidad = isset($_POST['cantidad_pisos']) ? (int)$_POST['cantidad_pisos'] : $default_cantidad; ?>
                
                <?php if (!empty($pisos_existentes)): ?>
                    <div class="alert alert-info">
                        <h4>Pisos Existentes:</h4>
                        <?php foreach ($pisos_existentes as $num_piso => $datos): ?>
                            <p>Piso <?= $num_piso ?>: <?= $datos['nomenclatura'] ?> (Espacios <?= $datos['inicio'] ?>-<?= $datos['fin'] ?>)</p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $cantidad; $i++) : ?>
                    <div class="piso-config <?= isset($pisos_existentes[$i]) ? 'existente' : 'nuevo' ?>">
                        <label>Piso de <?= ucfirst($tipo) ?> <?= $i ?></label>
                        <span class="status-badge <?= isset($pisos_existentes[$i]) ? 'existente' : 'nuevo' ?>">
                            <?= isset($pisos_existentes[$i]) ? 'YA EXISTE' : 'NUEVO' ?>
                        </span>
                        
                        <?php if (isset($pisos_existentes[$i])): ?>
                            <input type="hidden" name="nomenclatura_<?= $i ?>" value="<?= $pisos_existentes[$i]['nomenclatura'] ?>">
                            <input type="hidden" name="inicio_<?= $i ?>" value="<?= $pisos_existentes[$i]['inicio'] ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Nomenclatura:</label>
                                    <input type="text" value="<?= $pisos_existentes[$i]['nomenclatura'] ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Espacio Inicial:</label>
                                    <input type="number" value="<?= $pisos_existentes[$i]['inicio'] ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Espacio Final:</label>
                                    <input type="number" name="fin_<?= $i ?>" value="<?= $pisos_existentes[$i]['fin'] ?>" required>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Nomenclatura:</label>
                                    <input type="text" name="nomenclatura_<?= $i ?>" class="nomenclatura-input" value="<?= isset($_POST["nomenclatura_$i"]) ? strtoupper($_POST["nomenclatura_$i"]) : '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Espacio Inicial:</label>
                                    <input type="number" name="inicio_<?= $i ?>" value="<?= isset($_POST["inicio_$i"]) ? $_POST["inicio_$i"] : '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Espacio Final:</label>
                                    <input type="number" name="fin_<?= $i ?>" value="<?= isset($_POST["fin_$i"]) ? $_POST["fin_$i"] : '' ?>" required>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
                
                <input type="hidden" name="tipo" value="<?= $tipo ?>">
                <div class="btn-group">
                    <button type="submit" name="guardar">Guardar Configuración</button>
                </div>
                
                <?php if ($nomenclatura_converted_message): ?>
                    <p class="alert alert-warning"><?= $nomenclatura_converted_message ?></p>
                <?php endif; ?>
                <?php if ($validation_error): ?>
                    <p class="alert alert-danger"><?= $validation_error ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </form>

        <!-- Visualización de Estacionamiento -->
       <h2>Visualización de Estacionamiento</h2>

        <div class="nav-tabs">
            <a href="?tipo=carros<?= $piso_seleccionado ? '&piso='.urlencode($piso_seleccionado) : '' ?><?= $estado_filtro ? '&estado='.$estado_filtro : '' ?>" 
            class="<?= $tipo == 'carros' ? 'active' : '' ?>">Carros</a>
            <a href="?tipo=motos<?= $piso_seleccionado ? '&piso='.urlencode($piso_seleccionado) : '' ?><?= $estado_filtro ? '&estado='.$estado_filtro : '' ?>" 
            class="<?= $tipo == 'motos' ? 'active' : '' ?>">Motos</a>
        </div>

        <form id="filtroForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Seleccionar Piso:</label>
                    <select id="piso" name="piso">
                        <option value="TODOS" <?= $piso_seleccionado == 'TODOS' ? 'selected' : '' ?>>Todos los pisos</option>
                        <?php
                        $pisos = $conn->query("SELECT DISTINCT pisos, nomenclatura FROM cupos WHERE vehiculo = '" . ($tipo == 'carros' ? 'C' : 'M') . "' ORDER BY pisos");
                        while ($p = $pisos->fetch_assoc()) {
                            $selected = ($piso_seleccionado == $p['nomenclatura']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($p['nomenclatura']) . "' $selected>Piso " . $p['pisos'] . " - " . $p['nomenclatura'] . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Filtrar por Estado:</label>
                    <select id="estado" name="estado">
                        <option value="todos" <?= $estado_filtro == 'todos' ? 'selected' : '' ?>>Todos los estados</option>
                        <option value="0" <?= $estado_filtro == '0' ? 'selected' : '' ?>>Disponible</option>
                        <option value="1" <?= $estado_filtro == '1' ? 'selected' : '' ?>>Ocupado</option>
                        <option value="2" <?= $estado_filtro == '2' ? 'selected' : '' ?>>Reservado</option>
                        <option value="3" <?= $estado_filtro == '3' ? 'selected' : '' ?>>En Mantenimiento</option>
                    </select>
                </div>
            </div>
            <input type="hidden" name="tipo" id="tipo" value="<?= $tipo ?>">
        </form>
        
        <div class="stats">
            <p class="green">Disponibles: <?= $estadisticas['disponibles'] ?></p>
            <p class="red">Ocupados: <?= $estadisticas['ocupados'] ?></p>
            <p class="purple">Reservados: <?= $estadisticas['reservados'] ?></p>
            <p class="yellow">En Mantenimiento: <?= $estadisticas['mantenimiento'] ?></p>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const pisoSelect = document.getElementById('piso');
            const estadoSelect = document.getElementById('estado');
            const tipoInput = document.getElementById('tipo');
            const visualizationDiv = document.querySelector('.visualization');

            const actualizarVisualizacion = () => {
                const params = new URLSearchParams({
                    piso: pisoSelect.value,
                    estado: estadoSelect.value,
                    tipo: tipoInput.value,
                    ajax: '1'
                });

                fetch('Ges_parqueadero.php?' + params.toString())
                    .then(response => response.text())
                    .then(html => {
                        visualizationDiv.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error al cargar los cupos:', error);
                    });
            };

            pisoSelect.addEventListener('change', actualizarVisualizacion);
            estadoSelect.addEventListener('change', actualizarVisualizacion);
        });
        </script>


        <div class="visualization">
            <?php
            if ($piso_seleccionado && $piso_seleccionado != 'TODOS') {
                // Filtro por piso específico
                $query = "SELECT * FROM cupos WHERE nomenclatura = '" . $conn->real_escape_string($piso_seleccionado) . "' AND vehiculo = '" . ($tipo == 'carros' ? 'C' : 'M') . "'";
                if ($estado_filtro !== 'todos') {
                    $query .= " AND estado = " . (int)$estado_filtro;
                }
                $query .= " ORDER BY id_puesto";
                $cupos_piso = $conn->query($query);

                while ($cupo = $cupos_piso->fetch_assoc()) {
                    $color = match ((int)$cupo['estado']) {
                        0 => 'green',   // Disponible
                        1 => 'red',     // Ocupado
                        2 => 'purple',  // Reservado
                        3 => 'yellow',  // En Mantenimiento
                        default => 'gray'
                    };
                    $nomenclaturaEscapada = htmlspecialchars($cupo['nomenclatura']);
                    $id = (int)$cupo['id_puesto'];
                    echo "<div class=\"$color\" onclick=\"ocuparEspacio('$nomenclaturaEscapada', $id)\">$id</div>";
                }
            } else {
                // Mostrar todos los pisos
                $query = "SELECT * FROM cupos WHERE vehiculo = '" . ($tipo == 'carros' ? 'C' : 'M') . "'";
                if ($estado_filtro !== 'todos') {
                    $query .= " AND estado = " . (int)$estado_filtro;
                }
                $query .= " ORDER BY nomenclatura, id_puesto";
                $cupos = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

                foreach ($cupos as $cupo) {
                    $color = match ((int)$cupo['estado']) {
                        0 => 'green',
                        1 => 'red',
                        2 => 'purple',
                        3 => 'yellow',
                        default => 'gray'
                    };
                    $nomenclaturaEscapada = htmlspecialchars($cupo['nomenclatura']);
                    $id = (int)$cupo['id_puesto'];
                    echo "<div class=\"$color\" onclick=\"ocuparEspacio('$nomenclaturaEscapada', $id)\">{$nomenclaturaEscapada}-$id</div>";
                }
            }
            ?>
        </div>


        <!-- Formulario de Ocupación -->
        <div id="ocuparForm" class="hidden">
            <h2>Ocupar Espacio</h2>
            <form method="post">
                <input type="hidden" name="nomenclatura_ocupar" id="nomenclatura_ocupar">
                <input type="hidden" name="id_puesto_ocupar" id="id_puesto_ocupar">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre:</label>
                        <input type="text" name="nombre" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Placa del Vehículo:</label>
                        <select name="placa" required>
                            <?php
                            foreach ($placas as $placa) {
                                echo "<option value='{$placa['placa']}'>{$placa['placa']}</option>";
                            }
                            if (empty($placas)) echo "<option value=''>No hay placas registradas</option>";
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Información de Contacto:</label>
                        <input type="text" name="contacto" required>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="button" onclick="document.getElementById('ocuparForm').classList.add('hidden')">Cancelar</button>
                    <button type="submit" name="ocupar">Confirmar</button>
                </div>
            </form>
        </div>

        <!-- Configuración de Espacios Predeterminados -->
        <h2>Configurar Espacios Predeterminados</h2>
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>Nomenclatura del Piso:</label>
                    <select name="nomenclatura_piso" required>
                        <option value="">Seleccione un piso</option>
                        <?php
                        $pisos_config = $conn->query("SELECT DISTINCT nomenclatura FROM cupos WHERE vehiculo = '" . ($tipo == 'carros' ? 'C' : 'M') . "' ORDER BY pisos");
                        while ($p = $pisos_config->fetch_assoc()) {
                            echo "<option value='{$p['nomenclatura']}'>{$p['nomenclatura']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Desde Espacio:</label>
                    <input type="number" name="desde_espacio" required>
                </div>
                <div class="form-group">
                    <label>Hasta Espacio:</label>
                    <input type="number" name="hasta_espacio" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Estado Predeterminado:</label>
                    <select name="estado_predeterminado" required>
                        <option value="0">Disponible</option>
                        <option value="1">Ocupado</option>
                        <option value="2">Reservado</option>
                        <option value="3">En Mantenimiento</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nombre (para reservados):</label>
                    <input type="text" name="nombre_reservado">
                </div>
                <div class="form-group">
                    <label>Motivo (para mantenimiento):</label>
                    <input type="text" name="motivo_mantenimiento">
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" name="aplicar_estado">Aplicar Estado</button>
            </div>
        </form>

        <!-- Formulario de Eliminación de Cupos Individuales -->
        <h2>Eliminar Cupos Individuales</h2>
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>Nomenclatura:</label>
                    <select name="nomenclatura_eliminar" required>
                        <option value="">Seleccione un piso</option>
                        <?php
                        $pisos_eliminar = $conn->query("SELECT DISTINCT nomenclatura FROM cupos WHERE vehiculo = '" . ($tipo == 'carros' ? 'C' : 'M') . "' ORDER BY pisos");
                        while ($p = $pisos_eliminar->fetch_assoc()) {
                            echo "<option value='{$p['nomenclatura']}'>{$p['nomenclatura']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ID Puesto:</label>
                    <input type="number" name="id_puesto_eliminar" required>
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" name="eliminar" class="btn-danger" onclick="return confirm('¿Está seguro de eliminar este cupo?')">Eliminar Cupo</button>
            </div>
        </form>

        <!-- Formulario para Agregar Cupo Individual -->
<h2>Agregar Cupo Individual</h2>
<form method="post">
    <div class="form-row">
        <div class="form-group">
            <label>Piso (Nomenclatura):</label>
            <select name="nomenclatura_agregar" required>
                <option value="">Seleccione un piso</option>
                <?php
                // Se asume que $tipo está definido ('carros' o 'motos')
                $pisos_agregar = $conn->query("SELECT DISTINCT nomenclatura FROM cupos WHERE vehiculo = '" . ($tipo == 'carros' ? 'C' : 'M') . "' ORDER BY pisos");
                while ($p = $pisos_agregar->fetch_assoc()) {
                    echo "<option value='{$p['nomenclatura']}'>{$p['nomenclatura']}</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="btn-group">
        <button type="submit" name="agregar" class="btn-success">Agregar Cupo</button>
    </div>
</form>


        <!-- Formulario de Eliminación de Pisos Completos -->
        <h2>Eliminar Pisos Completos</h2>
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>Nomenclatura del Piso a Eliminar:</label>
                    <select name="nomenclatura_piso_eliminar" required>
                        <option value="">Seleccione un piso</option>
                        <?php
                        $pisos_eliminar_completo = $conn->query("SELECT DISTINCT nomenclatura, pisos FROM cupos WHERE vehiculo = '" . ($tipo == 'carros' ? 'C' : 'M') . "' ORDER BY pisos");
                        while ($p = $pisos_eliminar_completo->fetch_assoc()) {
                            echo "<option value='{$p['nomenclatura']}'>{$p['nomenclatura']} (Piso {$p['pisos']})</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" name="eliminar_piso" class="btn-danger" onclick="return confirm('¿Está seguro de eliminar TODO el piso? Esta acción eliminará todos los espacios del piso seleccionado.')">Eliminar Piso Completo</button>
            </div>
            
            <div class="alert alert-warning">
                <strong>Nota:</strong> Solo se pueden eliminar pisos que no tengan espacios ocupados, reservados o en mantenimiento.
            </div>
        </form>
    </div>

    <script>
        function ocuparEspacio(nomenclatura, id_puesto) {
            document.getElementById('nomenclatura_ocupar').value = nomenclatura;
            document.getElementById('id_puesto_ocupar').value = id_puesto;
            document.getElementById('ocuparForm').classList.remove('hidden');
        }

        // Convertir nomenclatura a mayúsculas en tiempo real
        document.querySelectorAll('.nomenclatura-input').forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
        
    </script>
</body>
</html>
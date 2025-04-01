<?php
// Configuración de la conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistema_tareas";

// Crear conexión
$conn = new mysqli($servername, $username, $password);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Crear base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "<script>console.log('Base de datos creada correctamente o ya existente');</script>";
} else {
    echo "<script>console.log('Error al crear la base de datos: " . $conn->error . "');</script>";
}

// Seleccionar la base de datos
$conn->select_db($dbname);

// Crear tabla si no existe
$sql = "CREATE TABLE IF NOT EXISTS tareas (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha_vencimiento DATE,
    estado TINYINT(1) DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "<script>console.log('Tabla tareas creada correctamente o ya existente');</script>";
} else {
    echo "<script>console.log('Error al crear la tabla: " . $conn->error . "');</script>";
}

// Función para limpiar datos de entrada
function limpiarDatos($datos) {
    $datos = trim($datos);
    $datos = stripslashes($datos);
    $datos = htmlspecialchars($datos);
    return $datos;
}

// Procesar formulario para crear tarea
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "crear") {
    $titulo = limpiarDatos($_POST["titulo"]);
    $descripcion = limpiarDatos($_POST["descripcion"]);
    $fecha_vencimiento = limpiarDatos($_POST["fecha_vencimiento"]);
    
    // Validar que los campos no estén vacíos
    if (empty($titulo)) {
        $mensaje = "El título es obligatorio";
        $tipo_mensaje = "danger";
    } else {
        // Preparar la consulta SQL
        $stmt = $conn->prepare("INSERT INTO tareas (titulo, descripcion, fecha_vencimiento) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $titulo, $descripcion, $fecha_vencimiento);
        
        // Ejecutar la consulta
        if ($stmt->execute()) {
            $mensaje = "Tarea creada correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear la tarea: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        
        // Cerrar la declaración
        $stmt->close();
    }
}

// Procesar formulario para editar tarea
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "editar") {
    $id = limpiarDatos($_POST["id"]);
    $titulo = limpiarDatos($_POST["titulo"]);
    $descripcion = limpiarDatos($_POST["descripcion"]);
    $fecha_vencimiento = limpiarDatos($_POST["fecha_vencimiento"]);
    
    // Validar que los campos no estén vacíos
    if (empty($titulo)) {
        $mensaje = "El título es obligatorio";
        $tipo_mensaje = "danger";
    } else {
        // Preparar la consulta SQL
        $stmt = $conn->prepare("UPDATE tareas SET titulo = ?, descripcion = ?, fecha_vencimiento = ? WHERE id = ?");
        $stmt->bind_param("sssi", $titulo, $descripcion, $fecha_vencimiento, $id);
        
        // Ejecutar la consulta
        if ($stmt->execute()) {
            $mensaje = "Tarea actualizada correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar la tarea: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        
        // Cerrar la declaración
        $stmt->close();
    }
}

// Procesar cambio de estado de tarea
if (isset($_GET["completar"]) && !empty($_GET["completar"])) {
    $id = limpiarDatos($_GET["completar"]);
    
    // Obtener estado actual
    $stmt = $conn->prepare("SELECT estado FROM tareas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $estado_actual = $fila["estado"];
    $stmt->close();
    
    // Cambiar estado (alternar entre 0 y 1)
    $nuevo_estado = $estado_actual ? 0 : 1;
    
    // Preparar la consulta SQL
    $stmt = $conn->prepare("UPDATE tareas SET estado = ? WHERE id = ?");
    $stmt->bind_param("ii", $nuevo_estado, $id);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        $mensaje = "Estado de la tarea actualizado correctamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar el estado de la tarea: " . $stmt->error;
        $tipo_mensaje = "danger";
    }
    
    // Cerrar la declaración
    $stmt->close();
}

// Procesar eliminación de tarea
if (isset($_GET["eliminar"]) && !empty($_GET["eliminar"])) {
    $id = limpiarDatos($_GET["eliminar"]);
    
    // Preparar la consulta SQL
    $stmt = $conn->prepare("DELETE FROM tareas WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        $mensaje = "Tarea eliminada correctamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar la tarea: " . $stmt->error;
        $tipo_mensaje = "danger";
    }
    
    // Cerrar la declaración
    $stmt->close();
}

// Obtener tarea para editar
$tarea_editar = null;
if (isset($_GET["editar"]) && !empty($_GET["editar"])) {
    $id = limpiarDatos($_GET["editar"]);
    
    // Preparar la consulta SQL
    $stmt = $conn->prepare("SELECT * FROM tareas WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    // Ejecutar la consulta
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $tarea_editar = $resultado->fetch_assoc();
    }
    
    // Cerrar la declaración
    $stmt->close();
}

// Obtener tareas pendientes
$sql = "SELECT * FROM tareas WHERE estado = 0 ORDER BY fecha_vencimiento ASC";
$tareas_pendientes = $conn->query($sql);

// Obtener tareas completadas
$sql = "SELECT * FROM tareas WHERE estado = 1 ORDER BY fecha_vencimiento ASC";
$tareas_completadas = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Tareas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .container {
            max-width: 900px;
        }
        .task-card {
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .task-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .task-actions {
            display: flex;
            gap: 10px;
        }
        .task-date {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .task-desc {
            white-space: pre-line;
        }
        .completed-task {
            background-color:rgb(192, 204, 216);
        }
        .completed-task .card-title {
            text-decoration: line-through;
            color: #6c757d;
        }
        .vencida {
            border-left: 5px solid #dc3545;
        }
        .hoy {
            border-left: 5px solid #ffc107;
        }
        .futura {
            border-left: 5px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4 text-center">"SISTEMA DE GESTION DE TAREAS"</h1>
        
        <?php if (isset($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <?php if ($tarea_editar): ?>
                            <h5 class="mb-0">Editar Tarea</h5>
                        <?php else: ?>
                            <h5 class="mb-0">Nueva Tarea</h5>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="accion" value="<?php echo $tarea_editar ? 'editar' : 'crear'; ?>">
                            <?php if ($tarea_editar): ?>
                                <input type="hidden" name="id" value="<?php echo $tarea_editar['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título*</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required 
                                       value="<?php echo $tarea_editar ? $tarea_editar['titulo'] : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo $tarea_editar ? $tarea_editar['descripcion'] : ''; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fecha_vencimiento" class="form-label">Fecha de vencimiento</label>
                                <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" 
                                       value="<?php echo $tarea_editar ? $tarea_editar['fecha_vencimiento'] : ''; ?>">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $tarea_editar ? 'Actualizar Tarea' : 'Crear Tarea'; ?>
                                </button>
                                <?php if ($tarea_editar): ?>
                                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Cancelar</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Tareas Pendientes</h2>
                <?php if ($tareas_pendientes->num_rows > 0): ?>
                    <?php while($tarea = $tareas_pendientes->fetch_assoc()): ?>
                        <?php 
                            $clase_fecha = '';
                            if (!empty($tarea["fecha_vencimiento"])) {
                                $fecha_vencimiento = new DateTime($tarea["fecha_vencimiento"]);
                                $hoy = new DateTime();
                                $hoy->setTime(0, 0, 0); // Establecer la hora a 00:00:00
                                
                                if ($fecha_vencimiento < $hoy) {
                                    $clase_fecha = 'vencida';
                                } elseif ($fecha_vencimiento == $hoy) {
                                    $clase_fecha = 'hoy';
                                } else {
                                    $clase_fecha = 'futura';
                                }
                            }
                        ?>
                        <div class="card task-card <?php echo $clase_fecha; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($tarea["titulo"]); ?></h5>
                                <p class="task-date">
                                    <?php if (!empty($tarea["fecha_vencimiento"])): ?>
                                        Vence: <?php echo date("d/m/Y", strtotime($tarea["fecha_vencimiento"])); ?>
                                    <?php else: ?>
                                        Sin fecha de vencimiento
                                    <?php endif; ?>
                                </p>
                                <p class="card-text task-desc"><?php echo nl2br(htmlspecialchars($tarea["descripcion"])); ?></p>
                                <div class="task-actions">
                                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?completar=' . $tarea["id"]; ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Completar
                                    </a>
                                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?editar=' . $tarea["id"]; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?eliminar=' . $tarea["id"]; ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirm('¿Está seguro de que desea eliminar esta tarea?');">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">No hay tareas pendientes.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <h2>Tareas Completadas</h2>
                <?php if ($tareas_completadas->num_rows > 0): ?>
                    <?php while($tarea = $tareas_completadas->fetch_assoc()): ?>
                        <div class="card task-card completed-task">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($tarea["titulo"]); ?></h5>
                                <p class="task-date">
                                    <?php if (!empty($tarea["fecha_vencimiento"])): ?>
                                        Vence: <?php echo date("d/m/Y", strtotime($tarea["fecha_vencimiento"])); ?>
                                    <?php else: ?>
                                        Sin fecha de vencimiento
                                    <?php endif; ?>
                                </p>
                                <p class="card-text task-desc"><?php echo nl2br(htmlspecialchars($tarea["descripcion"])); ?></p>
                                <div class="task-actions">
                                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?completar=' . $tarea["id"]; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-undo"></i> Marcar como pendiente
                                    </a>
                                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?eliminar=' . $tarea["id"]; ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirm('¿Está seguro de que desea eliminar esta tarea?');">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">No hay tareas completadas.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Cerrar la conexión
$conn->close();
?>
<?php
include("conexion.php");
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

// Verifica conexión
if (!$conexion) {
    die("<script>alert('No se pudo conectar a la base de datos.');</script>");
}

$iduser = $_SESSION['id_usuario'];

// Obtener nombre del alumno
$sql = "SELECT NombreC FROM usuarios WHERE idusuarios = '$iduser'";
$resultado = $conexion->query($sql);
if (!$resultado) {
    die("Error al consultar el nombre: " . $conexion->error);
}
$row = $resultado->fetch_assoc();
$nombre = $row['NombreC'];

// Obtener asistencias y participaciones
$sqlAsistencias = "SELECT fecha, asistencia, participacion FROM asistencias WHERE id_usuario = '$iduser' ORDER BY fecha DESC";
$resultadoAsistencias = $conexion->query($sqlAsistencias);
if (!$resultadoAsistencias) {
    die("Error al consultar asistencias: " . $conexion->error);
}

// Contar faltas
$sqlFaltas = "SELECT COUNT(*) AS faltas FROM asistencias WHERE id_usuario = '$iduser' AND asistencia = 'Falta'";
$resFaltas = $conexion->query($sqlFaltas);
$cantidadFaltas = 0;
if ($resFaltas) {
    $filaFaltas = $resFaltas->fetch_assoc();
    $cantidadFaltas = (int)$filaFaltas['faltas'];
}

// Contar retardos
$sqlRetardos = "SELECT COUNT(*) AS retardos FROM asistencias WHERE id_usuario = '$iduser' AND asistencia = 'Retardo'";
$resRetardos = $conexion->query($sqlRetardos);
$cantidadRetardos = 0;
if ($resRetardos) {
    $filaRetardos = $resRetardos->fetch_assoc();
    $cantidadRetardos = (int)$filaRetardos['retardos'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencias y Participaciones</title>
    <link rel="stylesheet" href="assets/css/alumno.css"/>
    <link rel="icon" href="assets/img/alumno.png" type="alumno.png">

    <script>
    window.onload = function() {
        let faltas = <?php echo $cantidadFaltas; ?>;
        let retardos = <?php echo $cantidadRetardos; ?>;

        // Alerta si tiene 2 retardos
        if (retardos === 2) {
            alert("⚠️ Un retardo más y se convierte en una falta.");
        }

        // Convertir 3 retardos en 1 falta
        let totalFaltas = faltas + Math.floor(retardos / 3);

        if (totalFaltas === 2) {
            alert("⚠️ Solo puedes faltar una vez más.");
        } else if (totalFaltas >= 3) {
            alert("❌ Ya no puedes volver a faltar.");
        }
    }
    </script>
</head>
<body>
    <div class="container mt-5">
        <div class="bienvenida-alumno">
    <div class="contenedor-horario">
        <img src="assets/img/Horario.jpeg" alt="Horario" class="img-horario">
    </div>
    <h3>Bienvenido, <?php echo htmlspecialchars($nombre); ?></h3>
</div>
        </div>
        <h4>Historial de Asistencias y Participaciones</h4>
        <table class="table table-bordered table-striped mt-3">
            <thead class="thead-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Asistencia</th>
                    <th>Participación</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = $resultadoAsistencias->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['fecha']); ?></td>
                        <td><?php echo htmlspecialchars($fila['asistencia']); ?></td>
                        <td><?php echo htmlspecialchars($fila['participacion']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="salir.php" class="btn btn-danger">Cerrar sesión</a>
    </div>
</body>
</html>
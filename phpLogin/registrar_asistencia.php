<?php
include("conexion.php");
session_start();

if (!isset($_SESSION['id_usuario']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id_alumno = $_POST['id_alumno'];
$tipo = $_POST['tipo'];
$fecha = date("Y-m-d");

// Determinar valor para la columna asistencia
$asistencia = null;
$participacion = 'No';

switch ($tipo) {
    case 'asistencia':
        $asistencia = 'Asistencia';
        break;
    case 'retardo':
        $asistencia = 'Retardo';
        break;
    case 'falta':
        $asistencia = 'Falta';
        break;
    case 'participacion':
        $participacion = 'Sí';
        break;
}

// Verifica si ya existe registro del día para ese alumno
$sqlVerifica = "SELECT * FROM asistencias WHERE id_usuario = ? AND fecha = ?";
$stmtVerifica = $conexion->prepare($sqlVerifica);
$stmtVerifica->bind_param("is", $id_alumno, $fecha);
$stmtVerifica->execute();
$resultado = $stmtVerifica->get_result();

if ($resultado->num_rows > 0) {
    // Ya existe: actualizar
    $row = $resultado->fetch_assoc();

    $nuevaAsistencia = ($asistencia !== null) ? $asistencia : $row['asistencia'];
    $nuevaParticipacion = ($tipo === 'participacion') ? 'Sí' : $row['participacion'];

    $sqlActualizar = "UPDATE asistencias SET asistencia = ?, participacion = ? WHERE id_usuario = ? AND fecha = ?";
    $stmtActualizar = $conexion->prepare($sqlActualizar);
    $stmtActualizar->bind_param("ssis", $nuevaAsistencia, $nuevaParticipacion, $id_alumno, $fecha);
    $stmtActualizar->execute();
} else {
    // Insertar nuevo
    $valorAsistencia = ($asistencia !== null) ? $asistencia : 'Sin registro';
    $sqlInsertar = "INSERT INTO asistencias (id_usuario, fecha, asistencia, participacion) VALUES (?, ?, ?, ?)";
    $stmtInsertar = $conexion->prepare($sqlInsertar);
    $stmtInsertar->bind_param("isss", $id_alumno, $fecha, $valorAsistencia, $participacion);
    $stmtInsertar->execute();
}

header("Location: maestro.php");
exit;
?>

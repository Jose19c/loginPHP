<?php
include("conexion.php");
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

$id_maestro = $_SESSION['id_usuario'];
$queryMaestro = "SELECT NombreC FROM usuarios WHERE idusuarios = '$id_maestro' AND tipo = 'maestro'";
$resMaestro = $conexion->query($queryMaestro);
$maestro = $resMaestro->fetch_assoc();
$nombreMaestro = $maestro['NombreC'] ?? 'Maestro';

$queryFechas = "SELECT DISTINCT fecha FROM asistencias ORDER BY fecha";
$resFechas = $conexion->query($queryFechas);
$fechas = [];
while ($row = $resFechas->fetch_assoc()) {
    $fechas[] = $row['fecha'];
}

$queryAlumnos = "SELECT idusuarios, NombreC FROM usuarios WHERE tipo = 'alumno'";
$resAlumnos = $conexion->query($queryAlumnos);
$alumnos = [];
while ($row = $resAlumnos->fetch_assoc()) {
    $alumnos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Maestro</title>
    <link rel="stylesheet" href="assets/css/maestro.css">
    <link rel="icon" href="assets/img/maestro.png" type="maestro.png">
</head>
<body>
    <div class="container">
        <h2>Bienvenido, <?php echo htmlspecialchars($nombreMaestro); ?></h2>
        <h4>Lista de Alumnos</h4>

        <table class="table">
            <thead>
                <tr>
                    <th>Nombre del Alumno</th>
                    <th>Asistencia</th>
                    <th>Participación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alumnos as $alumno): ?>
                <tr>
                    <td><?php echo htmlspecialchars($alumno['NombreC']); ?></td>
                    <td>
                        <form action="registrar_asistencia.php" method="POST" style="display:inline-block;">
                            <input type="hidden" name="id_alumno" value="<?php echo $alumno['idusuarios']; ?>">
                            <input type="hidden" name="tipo" value="asistencia">
                            <button type="submit" class="btn verde">✔ Asistencia</button>
                        </form>
                        <form action="registrar_asistencia.php" method="POST" style="display:inline-block;">
                            <input type="hidden" name="id_alumno" value="<?php echo $alumno['idusuarios']; ?>">
                            <input type="hidden" name="tipo" value="retardo">
                            <button type="submit" class="btn azul">⏱ Retardo</button>
                        </form>
                        <form action="registrar_asistencia.php" method="POST" style="display:inline-block;">
                            <input type="hidden" name="id_alumno" value="<?php echo $alumno['idusuarios']; ?>">
                            <input type="hidden" name="tipo" value="falta">
                            <button type="submit" class="btn rojo">✖ Falta</button>
                        </form>
                    </td>
                    <td>
                        <form action="registrar_asistencia.php" method="POST">
                            <input type="hidden" name="id_alumno" value="<?php echo $alumno['idusuarios']; ?>">
                            <input type="hidden" name="tipo" value="participacion">
                            <button type="submit" class="btn azul">💬 Participación</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button onclick="toggleLista()" id="toggleButton">Mostrar lista</button>

        <div id="listaContainer">
            <h3>Lista</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <?php foreach ($fechas as $fecha): ?>
                            <th><?php echo htmlspecialchars($fecha); ?></th>
                        <?php endforeach; ?>
                        <th>Total Asistencias</th>
                        <th>Total Participaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alumnos as $alumno):
                        $idAlumno = $alumno['idusuarios'];
                        $nombreAlumno = $alumno['NombreC'];

                        $estadosPorFecha = [];
                        foreach ($fechas as $fecha) {
                            $q = "SELECT asistencia FROM asistencias WHERE id_usuario = $idAlumno AND fecha = '$fecha'";
                            $r = $conexion->query($q);
                            $f = $r->fetch_assoc();
                            $estadosPorFecha[$fecha] = $f['asistencia'] ?? '-';
                        }

                        // Nuevo cálculo de asistencias considerando penalización por retardos
                        $queryAsistencias = "SELECT asistencia FROM asistencias WHERE id_usuario = $idAlumno AND (asistencia = 'Asistencia' OR asistencia = 'Retardo')";
                        $resAsistencias = $conexion->query($queryAsistencias);

                        $asistencias = 0;
                        $retardos = 0;

                        while ($row = $resAsistencias->fetch_assoc()) {
                            if ($row['asistencia'] === 'Asistencia') {
                                $asistencias++;
                            } elseif ($row['asistencia'] === 'Retardo') {
                                $asistencias++; // se cuenta como asistencia
                                $retardos++;
                            }
                        }

                        $penalizacion = floor($retardos / 3);
                        $totalAsistencias = $asistencias - $penalizacion;
                        if ($totalAsistencias < 0) $totalAsistencias = 0;

                        $queryParticipaciones = "SELECT COUNT(*) AS total FROM asistencias WHERE id_usuario = $idAlumno AND participacion = 'Si'";
                        $totalParticipaciones = $conexion->query($queryParticipaciones)->fetch_assoc()['total'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($nombreAlumno); ?></td>
                        <?php foreach ($fechas as $fecha): ?>
                            <td><?php echo htmlspecialchars($estadosPorFecha[$fecha]); ?></td>
                        <?php endforeach; ?>
                        <td><?php echo $totalAsistencias; ?></td>
                        <td><?php echo $totalParticipaciones; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <a href="salir.php" class="btn rojo">Cerrar sesión</a>
    </div>

    <script>
        function toggleLista() {
            const container = document.getElementById('listaContainer');
            const button = document.getElementById('toggleButton');

            if (!container.classList.contains('visible')) {
                container.classList.add('visible');
                button.textContent = 'Ocultar lista';
            } else {
                container.classList.remove('visible');
                button.textContent = 'Mostrar lista';
            }
        }
    </script>
</body>
</html>
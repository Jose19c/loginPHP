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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Maestro</title>
    <link rel="stylesheet" href="assets/css/maestro.css">
    <link rel="icon" href="assets/img/maestro.png" type="image/png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
</head>
<body class="img-bg" style="background: url('assets/img/mae_fondo.jpg') no-repeat center center fixed; background-size: cover;">
    <div class="container">
        <h2>Bienvenido, <?php echo htmlspecialchars($nombreMaestro); ?></h2>
        <h4>Lista de Alumnos</h4>

        <!-- Tabla principal envuelta en contenedor responsive -->
        <div class="table-responsive">
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
                            <div class="button-group">
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
                            </div>
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
        </div>

        <!-- Botones de control -->
        <div class="control-buttons">
            <button onclick="toggleLista()" id="toggleButton">Mostrar lista</button>
            <button onclick="descargarExcel()" class="btn azul" style="margin-left:10px; display:none;" id="downloadExcelButton">📊 Descargar Excel</button>
            <button onclick="descargarPDF()" class="btn rojo" style="margin-left:10px; display:none;" id="downloadPDFButton">📄 Descargar PDF</button>
        </div>

        <!-- Container de la lista con tabla responsive -->
        <div id="listaContainer">
            <h3>Lista de Asistencias</h3>
            <div class="table-responsive">
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

                            $queryAsistencias = "SELECT asistencia FROM asistencias WHERE id_usuario = $idAlumno AND (asistencia = 'Asistencia' OR asistencia = 'Retardo')";
                            $resAsistencias = $conexion->query($queryAsistencias);

                            $asistencias = 0;
                            $retardos = 0;

                            while ($row = $resAsistencias->fetch_assoc()) {
                                if ($row['asistencia'] === 'Asistencia') {
                                    $asistencias++;
                                } elseif ($row['asistencia'] === 'Retardo') {
                                    $asistencias++;
                                    $retardos++;
                                }
                            }

                            $penalizacion = floor($retardos / 3);
                            $totalAsistencias = $asistencias - $penalizacion;
                            if ($totalAsistencias < 0) $totalAsistencias = 0;

                            $queryParticipaciones = "SELECT SUM(participacion) AS total FROM asistencias WHERE id_usuario = $idAlumno";
                            $resParticipaciones = $conexion->query($queryParticipaciones);
                            $participacionesData = $resParticipaciones->fetch_assoc();
                            $totalParticipaciones = $participacionesData['total'] ?? 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($nombreAlumno); ?></td>
                            <?php foreach ($fechas as $fecha): ?>
                                <td class="estado-cell"><?php echo htmlspecialchars($estadosPorFecha[$fecha]); ?></td>
                            <?php endforeach; ?>
                            <td><strong><?php echo $totalAsistencias; ?></strong></td>
                            <td><strong><?php echo $totalParticipaciones; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="logout-section">
            <a href="salir.php" class="btn rojo">Cerrar sesión</a>
        </div>
    </div>

    <script>
    // Función para verificar si hay overflow horizontal
    function checkTableOverflow() {
        const responsiveTables = document.querySelectorAll('.table-responsive');
        responsiveTables.forEach(container => {
            const table = container.querySelector('table');
            if (table.scrollWidth > container.clientWidth) {
                container.setAttribute('scrollable', 'true');
            } else {
                container.removeAttribute('scrollable');
            }
        });
    }

    function toggleLista() {
        const container = document.getElementById('listaContainer');
        const button = document.getElementById('toggleButton');
        const downloadExcelButton = document.getElementById('downloadExcelButton');
        const downloadPDFButton = document.getElementById('downloadPDFButton');

        if (!container.classList.contains('visible')) {
            container.classList.add('visible');
            button.textContent = 'Ocultar lista';
            downloadExcelButton.style.display = 'inline-block';
            downloadPDFButton.style.display = 'inline-block';
            
            // Verificar overflow después de mostrar
            setTimeout(checkTableOverflow, 100);
        } else {
            container.classList.remove('visible');
            button.textContent = 'Mostrar lista';
            downloadExcelButton.style.display = 'none';
            downloadPDFButton.style.display = 'none';
        }
    }

    function obtenerFechaFormatoEspañol() {
        const fecha = new Date();
        const dia = String(fecha.getDate()).padStart(2, '0');
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const anio = fecha.getFullYear();
        return `${dia}/${mes}/${anio}`;
    }

    function generarNombreArchivo() {
        const nombreMaestro = "<?php echo addslashes($nombreMaestro); ?>";
        const fecha = obtenerFechaFormatoEspañol().replace(/\//g, '-');
        return `Lista de asistencias de ${nombreMaestro} - ${fecha}`;
    }

    function descargarExcel() {
        const tabla = document.querySelector('#listaContainer table');
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(tabla, { cellStyles: true });

        const colWidths = [];
        const rows = tabla.rows;
        for (let c = 0; c < rows[0].cells.length; c++) {
            let maxLength = 10;
            for (let r = 0; r < rows.length; r++) {
                const cellText = rows[r].cells[c]?.innerText || '';
                if (cellText.length > maxLength) maxLength = cellText.length;
            }
            colWidths.push({ wch: maxLength + 2 });
        }
        ws['!cols'] = colWidths;

        // Estilos de borde y centrado
        Object.keys(ws).forEach(key => {
            if (key[0] !== '!') {
                ws[key].s = {
                    alignment: { horizontal: "center", vertical: "center", wrapText: true },
                    border: {
                        top: { style: "thin", color: { auto: 1 } },
                        bottom: { style: "thin", color: { auto: 1 } },
                        left: { style: "thin", color: { auto: 1 } },
                        right: { style: "thin", color: { auto: 1 } }
                    }
                };
            }
        });

        XLSX.utils.book_append_sheet(wb, ws, 'Lista de Asistencia');
        XLSX.writeFile(wb, generarNombreArchivo() + '.xlsx');
    }

    async function descargarPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('landscape');

        // Cargar imagen y continuar cuando esté lista
        const image = await cargarImagen();

        if (image) {
            const imgWidth = 60;
            const imgHeight = 30;
            const pageWidth = doc.internal.pageSize.getWidth();
            doc.addImage(image, 'PNG', pageWidth - imgWidth - 20, 10, imgWidth, imgHeight);
        }

        doc.setFontSize(12);
        doc.text("Lista de Asistencias", 14, 20);
        doc.text("Maestro: <?php echo addslashes($nombreMaestro); ?>", 14, 30);
        doc.text("Fecha: " + obtenerFechaFormatoEspañol(), 14, 40);

        const tabla = document.querySelector('#listaContainer table');
        const headers = [];
        const body = [];

        const ths = tabla.querySelectorAll('thead tr th');
        ths.forEach(th => headers.push(th.innerText));

        const filas = tabla.querySelectorAll('tbody tr');
        filas.forEach(tr => {
            const rowData = [];
            tr.querySelectorAll('td').forEach(td => rowData.push(td.innerText));
            body.push(rowData);
        });

        doc.autoTable({
            head: [headers],
            body: body,
            startY: 50,
            theme: 'grid',
            styles: {
                halign: 'center',
                valign: 'middle',
                fontSize: 8,
                cellPadding: 2
            },
            headStyles: {
                fillColor: [108, 92, 231],
                textColor: 255
            }
        });

        doc.save(generarNombreArchivo() + '.pdf');
    }

    // Carga imagen de una URL y la convierte en base64
    function cargarImagen() {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function () {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                canvas.getContext('2d').drawImage(img, 0, 0);
                resolve(canvas.toDataURL('image/png'));
            };
            img.onerror = () => resolve(null); // Si falla la imagen, continuar sin ella
            img.src = "assets/img/ites.png";
        });
    }

    // Verificar overflow al cargar la página y al redimensionar
    window.addEventListener('load', checkTableOverflow);
    window.addEventListener('resize', checkTableOverflow);
    </script>

    <style>
    /* Estilos adicionales para mejorar la UX */
    .button-group {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        justify-content: center;
    }
    
    .control-buttons {
        margin: 20px 0;
        text-align: center;
    }
    
    .logout-section {
        margin-top: 30px;
        text-align: center;
    }
    
    .estado-cell {
        font-weight: bold;
    }
    
    /* Colores para estados */
    .estado-cell:contains("Asistencia") { color: #00b894; }
    .estado-cell:contains("Retardo") { color: #0984e3; }
    .estado-cell:contains("Falta") { color: #d63031; }
    
    @media (max-width: 480px) {
        .button-group {
            flex-direction: column;
        }
        
        .button-group .btn {
            width: 100%;
            margin: 2px 0;
        }
        
        .control-buttons {
            text-align: center;
        }
        
        .control-buttons .btn {
            margin: 5px;
            display: block;
            width: 100%;
        }
    }
    </style>
</body>
</html>

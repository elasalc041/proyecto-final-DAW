<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

// Activa las sesiones
session_name("sesion-privada");
session_start();

//verificar que ha entrado por enlace 
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Location: ../index.php");
}else{
    //verificar se ha usado rally para entrar
    if (!isset($_GET["rally"])) 
    {
        header("Location: ../index.php");
    }else{
        //capturamos sesion y  usuario
        $email = "";
        $nombre = "";
        if (isset($_SESSION["email"])){
            $email = $_SESSION["email"];            

            $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

            //consulta obtener usuario conectado
            $consulta = " SELECT id_usuario, nombre, rol_id FROM usuarios WHERE email = ?";

            $consulta = $conexion->prepare($consulta);			

            $consulta->bindParam(1, $email);

            $consulta->execute();
            
            $resultado = $consulta->fetch();

            $nombre = $resultado["nombre"];

            $consulta= null;
            $conexion = null;
        }

        $rally = $_GET["rally"];

        //Conectamos a la BBDD para comprobar rally

        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
    
        // Montamos la consulta a ejecutar

        $consulta = "SELECT * FROM rally WHERE id_rally = ?";

        $consulta = $conexion->prepare($consulta);

        $consulta->bindParam(1, $rally);

        $consulta->execute();

        // comprobamos si hay algún registro 
        if ($consulta->rowCount() == 0)
        {
            //Si no lo hay, desconectamos y volvemos al listado 
            $consulta = null;
            $conexion = null;
            header("Location: ../index.php");
        }

        $registro = $consulta->fetch();
		$titulo =  $registro["titulo"];
        $fecha_ini = $registro["fecha_ini"];
		$fecha_fin = $registro["fecha_fin"];

        $consulta = null;
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Ranking Rally</title>
            <link rel="stylesheet" type="text/css" href="../css/estilos.css">
            <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
            <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        </head>
        <body>
            <header class="sticky-top bg-white shadow-sm">
                <nav class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
                    <a href='<?php echo "rally.php?rally=$rally"; ?>'class="btn btn-dark">Volver</a>
                <?php if ($email != ""): ?>
                    <div class="text-end">
                        <span class="me-3 fw-bold">Bienvenido/a <?php echo $nombre ?></span>
                        <a href="../ControlAcceso/cerrar-sesion.php"  class="btn btn-danger">Salir</a>
                    </div>
                <?php endif; ?>
                </nav>
            </header>
            <main class="container">
                <h1 class="text-primary-emphasis text-center my-3"><?php echo $titulo ?></h1>                                
                <h5 class='text-center fst-italic'>Fecha inicio <?php echo formatoFecha($fecha_ini) . "  |  Fecha fin " . formatoFecha($fecha_fin); ?> </h5>               
                <div class="row my-4 justify-content-around">	    
                    <section class="col-md-5 col-12 shadow rounded p-4">
                        <h3 class="my-3">Ranking fotografías</h3>
                        <?php
                        //fotos del rally ordenadas por puntuación
                        $select = "SELECT f.*, u.nombre, u.apellidos FROM fotos f JOIN usuarios u 
                        ON f.usuario_id = u.id_usuario
                        WHERE f.rally_id = :id AND estado= 'aceptada' ORDER BY f.puntos desc";

                        $consulta = $conexion->prepare($select);

                        $consulta->bindParam(":id", $rally);

                        $consulta->execute();

                        // comprobamos si algún registro 
                        if ($consulta->rowCount() == 0)
                        {
                            echo "<h5>No hay imágenes disponibles en estos momentos</h5>" . PHP_EOL;
                        }else{
                            $pos = 0;
                            echo "<div class='row g-4'>" . PHP_EOL;
                            while ($resultado = $consulta->fetch(PDO::FETCH_ASSOC)) {
                                $pos++;                           
								echo "<div class='card h-100 border-light p-0'>" . PHP_EOL;
                                    echo "<div class='row g-0'>" . PHP_EOL;
                                        echo "<div class='col-6'>" . PHP_EOL;
                                            echo "<img src='../$resultado[url]' class='img-fluid object-fit-cover rounded-start'  alt='Foto $resultado[id_foto]'></img>" . PHP_EOL;
                                        echo "</div>" . PHP_EOL;
                                        echo "<div class='col-6'>" . PHP_EOL; 
                                            echo "<div class='card-body d-flex flex-column'>" . PHP_EOL;
                                                echo "<p class='card-text fs-4'><strong>#$pos</strong> <span class='fotoId'>Foto$resultado[id_foto]</span></p>" . PHP_EOL;                               
                                                echo "<p class='card-text fst-italic text-secondary participante'>$resultado[nombre] $resultado[apellidos]</p>" . PHP_EOL;
                                                echo "<p class='card-text'><strong>Votos:</strong> <span class='puntos'>$resultado[puntos]</span></p>" . PHP_EOL;                                            
                                            echo "</div>" . PHP_EOL; 
                                        echo "</div>" . PHP_EOL; 
                                    echo "</div>" . PHP_EOL; 
                                echo "</div>" . PHP_EOL; 
                            } 
                            echo "</div>" . PHP_EOL;    
                        }
                        
                        ?>
                    </section>
                    <section class="col-md-5 col-12 shadow rounded p-4">
                        <h3 class="my-3">Ranking participantes</h3>
                        <?php
                         //obtener la foto con más puntos de cada usuario
                         $consulta =
                          "SELECT SUM(f.puntos) as puntos, u.nombre, u.apellidos, u.img FROM fotos f, usuarios u
                         WHERE f.usuario_id= u.id_usuario AND f.rally_id = :id
                         GROUP BY u.id_usuario
                         ORDER BY SUM(f.puntos) DESC;";

                        $consulta = $conexion->prepare($consulta);

                        $consulta->bindParam(":id", $rally);

                        $consulta->execute();

                        // comprobamos si algún registro 
                        if ($consulta->rowCount() == 0)
                        {
                            echo "<h5>No hay imágenes disponibles en estos momentos</h5>" . PHP_EOL;
                        }else{
                            $pos = 0;
                            echo "<div class='row g-4'>" . PHP_EOL;
                            while ($resultado = $consulta->fetch(PDO::FETCH_ASSOC)) {
                                $pos++;
                                echo "<div class='card h-100 border-light p-0'>" . PHP_EOL;                                
                                    echo "<div class='row g-0'>" . PHP_EOL;
                                        echo "<div class='col-1'>" . PHP_EOL;                                            
                                            echo "<p class='card-text fs-4'><strong>#$pos</strong></p>" . PHP_EOL;                                            
                                        echo "</div>". PHP_EOL;
                                        echo "<div class='col-2'>" . PHP_EOL;
                                            if ($resultado["img"] != null) {
                                                echo "<img class='avatar' src='../$resultado[img]' alt='Foto perfil usuario'/>" . PHP_EOL ;
                                            }else{
                                                echo "<img class='avatar' src='../img/avatar.svg' alt='Foto avatar'/>" . PHP_EOL; 
                                            } 
                                        echo "</div>". PHP_EOL;
                                        echo "<div class='col-4'>" . PHP_EOL;                                            
                                            echo "<p class='card-text fst-italic text-secondary'>$resultado[nombre] $resultado[apellidos]</p>" . PHP_EOL;
                                        echo "</div>". PHP_EOL;                            
                                        echo "<div class='col-5'>" . PHP_EOL;     
                                            echo "<p class='card-text'><strong>Votos totales:</strong> $resultado[puntos]</p>" . PHP_EOL;
                                        echo "</div>". PHP_EOL;        
                                    echo "</div>". PHP_EOL;                              
                                echo "</div>". PHP_EOL;                                
                            } 
                            echo "</div>". PHP_EOL;   
                        }

                        ?>
                    </section>
                </div>
                <div class="row g-4 justify-content-center my-4">
                <!-- Gráfico de Pastel -->
                <section class="col-12 col-md-8 col-lg-6">
                    <div class="card shadow-sm p-4">
                    <h5 class="text-center mb-3">Votos por Usuario</h5>
                    <div id="graficoPie"></div>
                    </div>
                </section>

                <!-- Gráfico de Columnas -->
                <section class="col-12 col-md-8 col-lg-6">
                    <div class="card shadow-sm p-4">
                    <h5 class="text-center mb-3">Puntos por Participante</h5>
                    <div id="graficoColumnas"></div>
                    </div>
                </section>
                </div>

            </main>
        
        <?php
            $consulta= null;
            $conexion = null;

            include '../utiles/footer.php';
        ?>
    <script>
        //ejecutar con el documento completamente cargado
        document.addEventListener('DOMContentLoaded', function () {
            //captura nombre y puntos de las fotos participantes en el rally
            const participantes = document.querySelectorAll(".participante");
            const puntos = document.querySelectorAll(".puntos");
            const fotos = document.querySelectorAll(".fotoId");

            const miArray = [];

            for (let index = 0; index < participantes.length; index++) {
                const participante = {
                    nombre: participantes[index].textContent,
                    fotos:[{
                        foto: fotos[index].textContent,
                        puntos: parseInt(puntos[index].textContent)
                    }],
                    puntosTotales: parseInt(puntos[index].textContent)                                      
                };

                // Buscar si ya existe un participante con el mismo nombre
                const existente = miArray.find(p => p.nombre === participante.nombre);

                if (existente) {
                    existente.puntosTotales += participante.fotos[0].puntos;
                    existente.fotos.push(participante.fotos[0]);
                }else{
                    miArray.push(participante);
                }
            }
            
            console.log(miArray);
           

            // cargamos API de google charts.
            google.charts.load('current', {'packages':['corechart']});

            google.charts.setOnLoadCallback(drawChartPie);

            google.charts.setOnLoadCallback(drawChartColumnas);

            // Callback que crea y rellena de datos, instancia el grafico, pasa los datos y dibuja
            function drawChartPie() {

                const container = document.getElementById('graficoPie');
                const width = container.offsetWidth;
                const height = width;


                const options = {width: width, height: height};

                // crea la tabla de datos.
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'Participante');
                data.addColumn('number', 'Votos');
                
                miArray.forEach(element => {
                    data.addRows([
                        [element.nombre, element.puntosTotales]
                    ]);
                });
               
                // instancia y dibuja el grafico.
                const chart = new google.visualization.PieChart(document.getElementById('graficoPie'));
                chart.draw(data, options);
            }

            function drawChartColumnas() {
                const data = new google.visualization.DataTable();

                const container = document.getElementById('graficoColumnas');
                const width = container.offsetWidth;
                const height = width;
                
                data.addColumn('string', 'Participante');

                // Obtener todas las fotos 
                const setFotos = new Set();
                miArray.forEach(p => {
                    p.fotos.forEach(f => setFotos.add(f.foto));
                });

                 // Añadir una columna por cada foto
                 setFotos.forEach(nombreFoto => {
                    data.addColumn('number', nombreFoto);
                });

               // Crear filas por participante
                const filas = miArray.map(participante => {
                    const fila = [participante.nombre];
                    setFotos.forEach(nombreFoto => {
                        const fotoObj = participante.fotos.find(f => f.foto === nombreFoto);
                        fila.push(fotoObj ? fotoObj.puntos : 0);
                    });
                    return fila;
                });

                data.addRows(filas);

                const options = {
                    isStacked: true,                    
                    width: width,
                    height: height,
                    hAxis: {
                        title: 'Participantes'
                    },
                    vAxis: {
                        title: 'Puntos',
                        format: '0'
                    },
                    legend: { position: 'top', maxLines: 3 }
                };

                const chart = new google.visualization.ColumnChart(document.getElementById('graficoColumnas'));
                chart.draw(data, options);
            }
        }); 
        
        window.addEventListener('resize', () => {
            drawChartColumnas();
            drawChartPie(); 
        });
    </script>
  
            </body>
        </html>
<?php        
    }
}
?>
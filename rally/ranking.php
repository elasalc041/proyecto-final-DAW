<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
//verificar que ha entrado por enlace 
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Location: ../index.php");
}else{
    //verificar se ha usado rally para entrar
    if (!isset($_GET["rally"])) 
    {
        header("Location: ../index.php");
    }else{
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
            <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        </head>
        <body>	
            <header>
                <nav>
                    <a href='<?php echo "rally.php?rally=$rally"; ?>' class='estilo_enlace'><button>Volver</button></a>
                    <a href="../ControlAcceso/cerrar-sesion.php" class='estilo_enlace'><button>Salir</button></a>
                </nav>
            </header>
            <main class="contenido">
                <?php echo "<h1>Rally $rally - $titulo  $fecha_ini | $fecha_fin</h1>" . PHP_EOL; ?>                
                <div class="rankings">	
                    <h2>Rankings y gráficos</h2>
                    <section class="ranking-fotos">
                        <h3>Ranking fotografías</h3>
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
                            echo "<h3>No hay imágenes disponibles en estos momentos</h3>" . PHP_EOL;
                        }else{
                            $pos = 0;
                            while ($resultado = $consulta->fetch(PDO::FETCH_ASSOC)) {
                                $pos++;
                                echo "<article class='foto'>" . PHP_EOL;
                                echo "<img src='../$resultado[url]' alt='Foto $resultado[id_foto]'></img>" . PHP_EOL;
                                echo "<p>#$pos <span class='fotoId'>Foto$resultado[id_foto]</span></p>" . PHP_EOL;                               
                                echo "<p class='participante'>$resultado[nombre] $resultado[apellidos]</p>" . PHP_EOL;
                                echo "<p>Votos <span class='puntos'>$resultado[puntos]</span></p>" . PHP_EOL;
                                echo "</article>". PHP_EOL;
                                
                            } 
                                
                        }
                        
                        ?>
                    </section>
                    <section class="ranking-usuarios">
                    <h3>Ranking participantes</h3>
                        <?php
                         //obtener la foto con más puntos de cada usuario
                         $consulta = 
                         "SELECT f.puntos, u.nombre, u.apellidos, u.img FROM fotos f, usuarios u
                         WHERE f.usuario_id= u.id_usuario
                         AND f.puntos = (
                             SELECT MAX(puntos) FROM fotos
                             WHERE usuario_id = f.usuario_id AND rally_id = :id
                         )
                         AND id_foto = (
                             SELECT MIN(id_foto) FROM fotos
                             WHERE usuario_id = f.usuario_id AND puntos = f.puntos AND rally_id = :id
                         )
                         AND f.estado = 'aceptada'
                         ORDER BY f.puntos DESC;";


                        $consulta = $conexion->prepare($consulta);

                        $consulta->bindParam(":id", $rally);

                        $consulta->execute();

                        // comprobamos si algún registro 
                        if ($consulta->rowCount() == 0)
                        {
                            echo "<h3>No hay imágenes disponibles en estos momentos</h3>" . PHP_EOL;
                        }else{
                            $pos = 0;
                            while ($resultado = $consulta->fetch(PDO::FETCH_ASSOC)) {
                                $pos++;
                                echo "<article class='usuario'>" . PHP_EOL;
                                echo "<p>#$pos</p>" . PHP_EOL;                               
                                echo "<p>$resultado[nombre] $resultado[apellidos]</p>" . PHP_EOL;
                                echo "<p>Votos $resultado[puntos]</p>" . PHP_EOL;
                                if ($resultado["img"] != null) {
                                    echo "<img class='avatar' src='../$resultado[img]' alt='Foto perfil usuario'/>" . PHP_EOL ;
                                }else{
                                    echo "<img class='avatar' src='../img/avatar.svg' alt='Foto avatar'/>" . PHP_EOL; 
                                }                              
                                echo "</article>". PHP_EOL;
                                
                            } 
                                
                        }

                        ?>

                    </section>
                </div>
                <div class="graficos">
                    <section id="graficoPie"></section>
                    <section id="graficoColumnas"></section>
                </div>
            </main>
        
        <?php
            $consulta= null;
            $conexion = null;

            include '../utiles/footer.php';
        ?>
    <script type="text/javascript">
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

                // crea la tabla de datos.
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'Participante');
                data.addColumn('number', 'Votos');
                
                miArray.forEach(element => {
                    data.addRows([
                        [element.nombre, element.puntosTotales]
                    ]);
                });
               

                // opciones del grafico
                const options = {'title':'Votos obtenidos por cada usuario',
                                'width':800,
                                'height':600};

                // instancia y dibuja el grafico.
                const chart = new google.visualization.PieChart(document.getElementById('graficoPie'));
                chart.draw(data, options);
            }

            function drawChartColumnas() {
                const data = new google.visualization.DataTable();
                
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
                    title: 'Puntos por participante y foto',
                    width: 900,
                    height: 600,
                    isStacked: true,
                    hAxis: {
                        title: 'Participantes'
                    },
                    vAxis: {
                        title: 'Puntos'
                    },
                    legend: { position: 'top', maxLines: 3 }
                };

                const chart = new google.visualization.ColumnChart(document.getElementById('graficoColumnas'));
                chart.draw(data, options);
            }

        });      
    </script>
  
            </body>
        </html>
<?php        
    }
}
?>
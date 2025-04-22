<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();

if (!isset($_SESSION["email"])) {
    header("Location: ../index.php");
} else {
    $email = $_SESSION["email"];

    //obtener datos del usuario
    $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

    $consulta = "SELECT nombre, apellidos, id_usuario, img, descripcion FROM usuarios WHERE email = :email;";

    $consulta = $conexion->prepare($consulta);

    // Ejecuta consulta
    $consulta->execute([
        "email" => $email
    ]);

    // Guardo el resultado
    $resultado = $consulta->fetch();
   
    $id = (int) $resultado["id_usuario"];
    $nombre = $resultado["nombre"];
    $apellidos = $resultado["apellidos"];
    $img = $resultado["img"];
    $descripcion = $resultado["descripcion"];
    


    // consulta para obtener rallies donde usuario está registrado

    $consulta2 = "SELECT * FROM inscripciones WHERE usuario_id = :usuarioId";

    $consulta2 = $conexion->prepare($consulta2);

    $consulta2->execute([
        "usuarioId" => $id,
    ]);

    $rallies = [];
    while ($registro = $consulta2->fetch(PDO::FETCH_ASSOC)) {
        $rallies[] = $registro["rally_id"]; //guardar los rallies solicitados en un array
    }

    $consulta = null;
    $consulta2 = null;
    $conexion = null;

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Perfil</title>
        <link rel="stylesheet" type="text/css" href="../css/estilos.css">
    </head>

    <body>
        <header>
            <nav>
                <button><a href='../index.php' class='estilo_enlace'>Volver</a></button>
                <button><a href="../ControlAcceso/cerrar-sesion.php" class='estilo_enlace'>Salir</a></button>
            </nav>
        </header>

        <?php

        // Realiza la conexion a la base de datos a través de una función 
    
        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

        // Realiza la consulta a ejecutar en la base de datos para obteneter ofertas
    
        $consulta = "SELECT id_rally, titulo, fecha_ini, fecha_fin FROM rally";

        // Obten el resultado de ejecutar la consulta para poder recorrerlo. El resultado es de tipo PDOStatement
    
        $resultado = resultadoConsulta($conexion, $consulta);

        ?>

        <main class="contenedor">            
            <h1>Mi Perfil</h1>
            <section class="perfil">
                <?php
                    echo "<h3>Usuario: $nombre $apellidos</h3>" . PHP_EOL;
                    echo "<p>$descripcion</p>"  . PHP_EOL;
                    echo "<a href='modificar.php?id=$id'><button>Modificar Perfil</button></a>". PHP_EOL;
                    if ($img != null) {                       
                        echo "<img src='../$img' alt='Foto perfil'></img>" . PHP_EOL;
                    }else{
                        echo "<img src='../img/avatar.svg' alt='Foto avatar anónimo'></img>" . PHP_EOL;
                    }
                ?>                
            </section>
            <section class="registros">
                <article>                    
                    <h2>Rallies Inscrito</h2>
                    
                        <!-- Muestra los datos -->
                        <?php
                        $participado = [];

                        while ($registro = $resultado->fetch(PDO::FETCH_ASSOC)) {

                            $apuntado = false;

                            //recorremos los rallies donde se ha inscrito
                            foreach ($rallies as $valor) {
                                //si el rally actual se enceuntra en los rallies inscritos por el usuario
                                if ($valor === $registro["id_rally"]) {
                                    $apuntado = true;
                                }
                            }

                            //solo mostrar rallies que no haya pasado la fecha aún y se encuentre apuntado
                            if ($registro["fecha_fin"] >= $fechaActual && $apuntado) {

                                //obtener posición de la foto con más puntos del usuario para cada rally
                                $consulta2 = "SELECT usuario_id FROM fotos WHERE rally_id = $registro[id_rally]
                                ORDER BY puntos DESC ";

                                $resultado2 = $conexion->query($consulta2);

                                $ranking = 0;
                                $flag = false;
                                while (($registro2 = $resultado2->fetch(PDO::FETCH_ASSOC)) && !$flag) {
                                    $ranking++;
                                    if ($registro2["usuario_id"] == $id) {
                                       $flag = true;
                                    }
                                }

                                echo "<div class='tarjeta'> " . PHP_EOL;
                                echo "<h3>Rally $registro[id_rally] - $registro[titulo]</h3>" . PHP_EOL;
                                echo "<p> " . formatoFecha($registro["fecha_ini"]) . " | " . formatoFecha($registro["fecha_fin"]) . PHP_EOL;
                                if ($flag) {
                                    echo "Ranking: $ranking</p>" . PHP_EOL;
                                }else{
                                    echo "Ranking: 0</p>" . PHP_EOL;
                                }
                                echo "<a href='../rally/rally.php?rally=$registro[id_rally]' class='estilo_enlace'><button>Ir</button></a>". PHP_EOL;
                                echo "</div> " . PHP_EOL;

                             //concursos ya pasados y estuviera apuntado   
                            }elseif ($registro["fecha_fin"] < $fechaActual && $apuntado) {
                               
                                //obtener posición de la foto con más puntos del usuario para cada rally
                               $consulta2 = "SELECT usuario_id FROM fotos 
                               WHERE rally_id = $registro[id_rally]
                               ORDER BY puntos DESC";

                               $resultado2 = resultadoConsulta($conexion, $consulta2);

                               $ranking = 0;
                               $flag = false;
                               while (($registro2 = $resultado2->fetch(PDO::FETCH_ASSOC)) && !$flag) {
                                   $ranking++;
                                   if ($registro2["usuario_id"] == $id) {
                                      $flag = true;
                                   }
                               }

                               $participado[] = "
                               <h3>Rally $registro[id_rally] - $registro[titulo]</h3>
                               <p> " . formatoFecha($registro["fecha_fin"]) .
                                "| Ranking: $ranking</p> " . PHP_EOL;
                            }

                        }
                        ?>
                </article>
                <article>
                    <h2>Rallies Participado</h2>
                    <?php
                        foreach ($participado as $valor) {
                            echo "<div class='tarjeta'>" . PHP_EOL;
                            echo $valor;
                            echo "</div> " . PHP_EOL;
                        }
                    ?>
                    
                </article>
            </section>
        </main>

        <?php

        $resultado = null;
        $resultado2 = null;
        $conexion = null;

        include '../utiles/footer.php';

}  //cierre del if
?>


</body>
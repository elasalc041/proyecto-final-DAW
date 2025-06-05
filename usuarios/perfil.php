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
        <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
        <link rel="stylesheet" type="text/css" href="../css/estilos.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
    </head>

    <body>
        <header class="sticky-top bg-white shadow-sm">
            <nav class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
                <a href='../index.php'class="btn btn-dark">Inicio</a>
            <?php if ($email != ""): ?>
                <div class="text-end">
                    <span class="me-3 fw-bold">Bienvenido/a <?php echo $nombre ?></span>
                    <a href="../ControlAcceso/cerrar-sesion.php"  class="btn btn-danger">Salir</a>
                </div>
            <?php endif; ?>
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

        <main class="container">            
            <h1 class="text-primary-emphasis mt-4">Mi Perfil</h1>
            <section class="row align-items-center g-4 mb-4">
                <div class="col-12 col-md-6">
                    <div class="card shadow-sm p-4 h-100">
                    <?php
                        echo "<h3 class='card-title mb-3'>$nombre $apellidos</h3>" . PHP_EOL;
                        echo "<p class='card-text'>$descripcion</p>"  . PHP_EOL;
                        echo "<a href='modificar.php?id=$id' class='btn btn-dark mt-3 mx-auto'>Modificar datos</a>". PHP_EOL;
                        
                    ?> 
                    </div>
                </div>
                <div class="col-12 col-md-6 d-flex justify-content-center align-items-center">
                    <div class="card p-4 border-light" style="min-height: 250px; min-width: 250px;">
                <?php  
                        if ($img != null && $img != "") {                       
                            echo "<img src='../$img' alt='Foto perfil' class='img-fluid' style='max-width: 250px; height: auto;'>" . PHP_EOL;
                        }else{
                            echo "<img src='../img/avatar.svg' alt='Foto perfil' class='img-fluid' style='max-width: 250px; height: auto;'>" . PHP_EOL;
                        }
                ?>
                    </div>
                </div>
            </section>
            <section class="row justify-content-around g-4 my-4">
                <div class="col-12 col-md-5 shadow rounded p-4">                    
                    <h2 class="my-3">Rallies Inscrito</h2>
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

                             //obtener posición de la foto con más puntos del usuario para cada rally
                                $consulta2 = 
                                "SELECT * FROM fotos f
                                WHERE puntos = (
                                    SELECT MAX(puntos) FROM fotos
                                    WHERE usuario_id = f.usuario_id AND rally_id = $registro[id_rally] 
                                )
                                AND id_foto = (
                                    SELECT MIN(id_foto) FROM fotos
                                    WHERE usuario_id = f.usuario_id AND puntos = f.puntos AND rally_id = $registro[id_rally] 
                                )
                                AND estado = 'aceptada'
                                ORDER BY puntos DESC;";

                                $resultado2 = $conexion->query($consulta2);

                            //solo mostrar rallies que no haya pasado la fecha aún y se encuentre apuntado
                            if ($registro["fecha_fin"] >= $fechaActual && $apuntado) {

                                $ranking = 0;
                                $flag = false;
                                $votos = 0;
                                while (($registro2 = $resultado2->fetch(PDO::FETCH_ASSOC)) && !$flag) {
                                    $ranking++;
                                    if ($registro2["usuario_id"] == $id) {
                                       $flag = true;
                                       $votos = $registro2["puntos"];
                                    }
                                }

                                echo "<div class='row g-4 mb-4'> " . PHP_EOL;
                                    echo "<div class='card h-100 border-light p-2'>" . PHP_EOL;
                                        echo "<div class='row g-0'>" . PHP_EOL;
                                            echo "<div class='col-10'>" . PHP_EOL;
                                                echo "<h5 class='card-text'>$registro[titulo]</h5>" . PHP_EOL;
                                                echo "<p class='card-text fst-italic text-secondary'> " . formatoFecha($registro["fecha_ini"]) 
                                                . " | " . formatoFecha($registro["fecha_fin"]) . "</p>" . PHP_EOL;
                                                if ($flag) {
                                                    echo "<p class='card-text'>Puesto foto mayor puntuación: $ranking. <strong>Votos:</strong> $votos</p>" . PHP_EOL;
                                                }else{
                                                    echo "<p class='card-text'>Ninguna foto subida</p>" . PHP_EOL;
                                                }
                                            echo "</div> " . PHP_EOL;
                                            echo "<div class='col-2'>" . PHP_EOL;   
                                                echo "<a href='../rally/rally.php?rally=$registro[id_rally]' class='btn btn-outline-primary mt-3 mx-auto'>Ir</a>". PHP_EOL;
                                            echo "</div> " . PHP_EOL;
                                        echo "</div> " . PHP_EOL;
                                    echo "</div> " . PHP_EOL;
                                echo "</div> " . PHP_EOL;

                             //concursos ya pasados y estuviera apuntado   
                            }elseif ($registro["fecha_fin"] < $fechaActual && $apuntado) {

                               $ranking = 0;
                               $flag = false;
                               $votos = 0;
                               while (($registro2 = $resultado2->fetch(PDO::FETCH_ASSOC)) && !$flag) {
                                   $ranking++;
                                   if ($registro2["usuario_id"] == $id) {
                                      $flag = true;
                                      $votos = $registro2["puntos"];
                                   }
                               }

                               $noFoto= "<p class='card-text'>Ninguna foto subida</p>" . PHP_EOL;
                               if ($flag) {
                                  $noFoto= "<p class='card-text'>Puesto foto mayor puntuación: $ranking. <strong>Votos:</strong> $votos</p>" . PHP_EOL;
                                }

                               $participado[] = "
                               <div class='col-10'>
                                    <h5 class='card-text'>$registro[titulo]</h5>
                                    <p class='card-text fst-italic text-secondary'> " . formatoFecha($registro["fecha_ini"]) 
                                            . " | " . formatoFecha($registro["fecha_fin"]) . "</p>
                                    $noFoto
                                </div>
                               <div class='col-2'>   
                                    <a href='../rally/rally.php?rally=$registro[id_rally]' class='btn btn-dark mt-3 mx-auto'>Ir</a>
                                </div>"  . PHP_EOL;
                            }
                        }
                        ?>
                </div>
                <div class="col-12 col-md-5 shadow rounded p-4">
                    <h2 class="my-3">Rallies Participado</h2>
                    <?php
                        foreach ($participado as $valor) {
                        echo "<div class='row g-4 mb-4'> " . PHP_EOL;
                            echo "<div class='card h-100 border-light p-2'>" . PHP_EOL;
                                echo "<div class='row g-0'>" . PHP_EOL;
                                    echo $valor;
                                echo "</div> " . PHP_EOL;
                            echo "</div> " . PHP_EOL;
                        echo "</div> " . PHP_EOL;
                        }
                    ?>
                    
                </div>
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
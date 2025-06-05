<?php
require_once("utiles/variables.php");
require_once("utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();

//si entramos con el email, se ha creado la sesion si es correcto y guardamos el email para identificar
    $email = "";
    $perfil = 0;
    $nombre = "";
    $rallies = [];
    if (isset($_SESSION["email"])){
        $email = $_SESSION["email"];            

        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

        //consulta obtener usuario conectado
        $consulta = " SELECT id_usuario, nombre, rol_id FROM usuarios WHERE email = ?";

        $consulta = $conexion->prepare($consulta);			

        $consulta->bindParam(1, $email);

        $consulta->execute();
        
        $resultado = $consulta->fetch();

        // Guardo el rol y el id
        $perfil = (int) $resultado["rol_id"];

        $id = $resultado["id_usuario"];

        $nombre = $resultado["nombre"];

        // consulta para obtener rallies donde usuario está registrado
        $consulta2 = "SELECT * FROM inscripciones WHERE usuario_id = :usuarioId";
        $consulta2 = $conexion->prepare($consulta2);
        $consulta2->execute(["usuarioId" => $id]);

        while ($registro = $consulta2->fetch(PDO::FETCH_ASSOC)) {
            $rallies[] = $registro["rally_id"];
        }

        $consulta = null;
        $consulta2 = null;
        $conexion = null;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma rallies</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="css/estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
</head>
<body>

<header class="sticky-top bg-white shadow-sm">  
    <nav class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
        <?php if ($email != ""): ?>
            <!-- LADO IZQUIERDO: Mi Perfil o Vista Admin -->
            <div>
                <?php if ($perfil === 2): ?>
                    <a href="usuarios/perfil.php" class="btn btn-primary me-2">Mi Perfil</a>
                <?php else: ?>
                    <a href="administrador/listados.php" class="btn btn-primary me-2">Vista Administrador</a>
                <?php endif; ?>
            </div>

            <!-- LADO DERECHO: Bienvenida y botón salir -->
            <div class="text-end">
                <span class="me-3 fw-bold">Bienvenido/a <?php echo $nombre ?></span>
                <a href="ControlAcceso/cerrar-sesion.php" class="btn btn-danger">Salir</a>
            </div>
        <?php else: ?>
            <!-- Si NO hay sesión: izquierda vacía, login a la derecha -->
            <div></div>
            <div class="d-flex flex-column align-items-end">
                <form class="d-flex gap-2 mb-2" action="ControlAcceso/acceso.php" method="post">
                    <input class="form-control" type="text" name="email" placeholder="Email">
                    <input class="form-control" type="password" name="contrasena" placeholder="Contraseña">
                    <button type="submit" class="btn btn-dark">Login</button>
                    <a href="registro/formularioUsuario.php" class="btn btn-warning">Registrarse</a>
                </form>
                <p class="mb-0"><a href="ControlAcceso/recordar.php">¿Olvidó su contraseña?</a></p>
            </div>
        <?php endif; ?>
    </nav>
</header>

    <?php

        // Realiza la conexion a la base de datos a través de una función 

        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

        // consulta para obtener rallies
        
        $consulta = "SELECT r.*, count(i.usuario_id) as registrados FROM rally r
                LEFT JOIN inscripciones i ON r.id_rally = i.rally_id GROUP BY r.id_rally ORDER BY fecha_ini DESC";

        // Obten el resultado de ejecutar la consulta para poder recorrerlo. El resultado es de tipo PDOStatement

        $resultado = resultadoConsulta($conexion, $consulta);
    
    ?>

<main>
    <section class="hero-section d-none d-md-block mb-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <h1>Concursos Fotográficos</h1>
                </div>
            </div>
        </div>
    </section>
    <section class="container">
        <h1>Lista de Rallies</h1>  

                <!-- Muestra los datos -->
        <?php

            if ($resultado->rowCount() === 0) {
                echo "<h3>No hay rallies fotográficos disponibles en estos momentos</h3>" . PHP_EOL;
            }else{
                while ($registro = $resultado->fetch(PDO::FETCH_ASSOC)) {                
                        //solo mostrar rallies que no hayan terminado  
                        if ($registro["fecha_fin"] > $fechaActual) {
                            echo "<a href='rally/rally.php?rally=$registro[id_rally]' class='text-decoration-none'>" . PHP_EOL;
                            echo "<article class='card shadow-sm hover-shadow mt-3 col-12 text-center'>" . PHP_EOL;
                            if ($registro["img"] != null) {
                                echo "<img class='img-fluid' style='height: 18rem; object-fit: cover;' src='$registro[img]' alt='Foto del rally'></img>" . PHP_EOL;
                            }else{
                                echo "<img class='img-fluid' style='height: 18rem; object-fit: cover;'  src='' alt='Foto del rally no disponible'></img>" . PHP_EOL;
                            }
                            echo "<div class='card-body'>" . PHP_EOL;
                            echo "<h3 class='card-title'>$registro[titulo]</h3>" . PHP_EOL;
                            echo "<p class='card-text'>" . formatoFecha($registro["fecha_ini"]) . " | " . formatoFecha($registro["fecha_fin"]) . "</p> 
                            <p class='card-text'>Límite participantes: $registro[participantes]. Participantes registrados: $registro[registrados]</p>" . PHP_EOL;
                            
                            //usuario registrado en la plataforma
                            if ($perfil === 2) {
                                //recorremos los rallies
                                foreach ($rallies as $valor) {
                                //en caso que el rally se encuentre en rallies donde ya se ha inscrito el usuario
                                    if ($valor === $registro["id_rally"]) {
                                        echo "<p  class='card-text' style='color: green'>Estás inscrito en este rally. </p>" . PHP_EOL;                         
                                    }               
                                }                            
                            }  
                            echo "</div>". PHP_EOL;
                            echo "</article>". PHP_EOL;
                            echo "</a>". PHP_EOL;
                        }
                }
            }
        ?>   

    </section>
</main>

    <?php
            // Libera el resultado y cierra la conexión
    
        $resultado = null;
        $conexion = null;

        include 'utiles/footer.php';
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>

<?php
require_once("utiles/variables.php");
require_once("utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma rallies</title>
    <link rel="stylesheet" type="text/css" href="css/estilos.css">
</head>
<body>

<header>    

    <?php
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

    ?>

        <nav>
        
            <?php        
        
            //nombre del usuario
            if ($email != "") {
                echo "<h3>Bienvenido $nombre</h3>" . PHP_EOL;

                //perfil de usuario
                if ($perfil === 2) {
                    echo "<a href='usuarios/perfil.php' class='estilo_enlace'><button>Mi Perfil</button></a>" . PHP_EOL;
                }else{
                    //enlace administrador 
                    echo "<a href='administrador/listados.php' class='estilo_enlace'><button>Vista Administrador</button></a>" . PHP_EOL;
                }

                echo "<a href='ControlAcceso/cerrar-sesion.php'><button>Salir</button></a>" . PHP_EOL;
            }
           
            // consulta para obtener rallies donde usuario está registrado

            $consulta2 = "SELECT * FROM inscripciones WHERE usuario_id = :usuarioId";

            $consulta2 = $conexion->prepare($consulta2);

            $consulta2->execute([
                "usuarioId" => $id,
            ]);       
                       
            while ($registro = $consulta2->fetch(PDO::FETCH_ASSOC)) {
                $rallies[] = $registro["rally_id"]; //guardar los rallies solicitados en un array
            }

			$consulta = null;
            $consulta2 = null;
			$conexion = null;

        } else{
           
    ?>

        <!-- Formulario de identificación -->
        <div class="login">
            <h2>Login</h2>
            <form action="ControlAcceso/acceso.php" method="post">
                <p>
                    <input type="text" name="email" placeholder="Email"> 
                </p> 
                <p>
                    <input type="password" name="contrasena" placeholder="Contraseña"> 
                </p>
                <p>
                    <input type="submit" value="Entrar">                     
                    <button><a href='registro/formularioUsuario.php' class='estilo_enlace'>Registrarse</a></button>
                </p>
                <p><a href="ControlAcceso/recordar.php">¿Olvidó su contraseña?</a></p>
            </form>
        </div>
    </nav>
</header>



    <?php
        }

        // Realiza la conexion a la base de datos a través de una función 

        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

        // consulta para obtener rallies

       $consulta = "SELECT id_rally, titulo, fecha_ini, fecha_fin, participantes, count(usuario_id) as registrados, img 
                FROM rally INNER JOIN inscripciones ON id_rally = rally_id ORDER BY fecha_ini DESC";

        // Obten el resultado de ejecutar la consulta para poder recorrerlo. El resultado es de tipo PDOStatement

        $resultado = resultadoConsulta($conexion, $consulta);
    
    ?>

<main class="contenedor">

    <h1>Concursos Fotográficos</h1>
    <div id="hero">            
        <img src="img/hero.jpg" alt="Imagen hero cámara fotos">
    </div> 
    
    <section>
        <h1>Lista de Rallies</h1>  

                <!-- Muestra los datos -->
        <?php

            if ($resultado->rowCount() === 0) {
                echo "<h3>No hay rallies fotográficos disponibles en estos momentos</h3>" . PHP_EOL;
            }else{
                while ($registro = $resultado->fetch(PDO::FETCH_ASSOC)) {                
                        //solo mostrar rallies que no hayan terminado  
                        if ($registro["fecha_fin"] > $fechaActual) {
                            echo "<a href='rally/rally.php?rally=$registro[id_rally]' class='estilo_enlace'>" . PHP_EOL;
                            echo "<article class='tarjeta'>" . PHP_EOL;
                            if ($registro["img"] != null) {
                                echo "<img src='$registro[img]' alt='Foto del rally'></img>" . PHP_EOL;
                            }else{
                                echo "<img src='' alt='Foto del rally no disponible'></img>" . PHP_EOL;
                            }
                            echo "<h3>Rally $registro[id_rally]</h3>" . PHP_EOL;
                            echo "<p>$registro[titulo]. " . formatoFecha($registro["fecha_ini"]) . " | " . formatoFecha($registro["fecha_fin"]) . ".</p> 
                            <p>Límite participantes: $registro[participantes]. Participantes registrados: $registro[registrados]</p>" . PHP_EOL;
                            
                            //usuario registrado en la plataforma
                            if ($perfil === 2) {
                                //recorremos los rallies
                                foreach ($rallies as $valor) {
                                //en caso que el rally se encuentre en rallies donde ya se ha inscrito el usuario
                                    if ($valor === $registro["id_rally"]) {
                                        echo "<p style='color: green'>Estás inscrito en este rally. </p>" . PHP_EOL;                         
                                    }               
                                }                            
                            }  
                        
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
</body>
</html>

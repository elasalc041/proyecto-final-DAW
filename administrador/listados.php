<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();
// Comprueba si existe la sesión "email", en caso contrario vuelve a la página de inicio
if (!isset($_SESSION["email"])){
    header("Location: ../index.php");
} else{
    $email = $_SESSION["email"];

    //obtener el perfil del usuario
    $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

    $consulta = "select * FROM gestores WHERE email = :email;";

    $consulta = $conexion->prepare($consulta);

    // Ejecuta consulta
    $consulta->execute([
        "email" => $email
    ]);

    // Guardo el resultado
    $resultado = $consulta->fetch();

    // Guardo el perfil
    $perfil = (int) $resultado["perfil_id"];

    // Comprueba que el rol sea "Admin", en caso contrario vuelve a la página inicial
	if ($perfil !== 1) {
		header("Location: ../index.php");
	}

    $resultado = null;
    $conexion = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listados</title>
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
</head>
<body>
    <h1>Listado de USUARIOS</h1>

    <?php
        // Realiza la conexion a la base de datos a través de una función 

        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
        
        // Realiza la consulta a ejecutar en la base de datos en una variable

        $consulta = "SELECT usuarios.id, nombre, email, perfil, activo, usuarios.created_at, usuarios.updated_at FROM usuarios, perfiles 
        WHERE perfil_id = perfiles.id";
        
        // Obten el resultado de ejecutar la consulta para poder recorrerlo. El resultado es de tipo PDOStatement
        
        $resultado = resultadoConsulta($conexion, $consulta);

    ?>
        <table border="1" cellpadding="10">
            <thead>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Perfil</th>
                <th>Activo</th>
                <th>Fecha de Alta</th>
                <th>Última Modificación</th>
                <th>Acciones</th>
            </thead>
            <tbody>
                
                <!-- Muestra los datos -->

        <?php
        $resultado->bindColumn(1, $id);
        $resultado->bindColumn(2, $nombre);
        $resultado->bindColumn(3, $email);
        $resultado->bindColumn(4, $perfil);
        $resultado->bindColumn(5, $activo);
        $resultado->bindColumn(6, $fecha_alta);
        $resultado->bindColumn(7, $fecha_modif);


        while ($registro = $resultado->fetch(PDO::FETCH_BOUND)) {
            echo "<tr>" . PHP_EOL;
            echo "<td>$id</td> <td>$nombre</td> <td>$email</td> <td>$perfil</td> <td>$activo</td>  <td>$fecha_alta</td> <td>$fecha_modif</td>
            <td>    <a href='modificarUsuario.php?usuarioId=$id' class='estilo_enlace'>&#9998</a>
                    <a href='borrarUsuario.php?usuarioId=$id' class='confirmacion_borrar'>&#128465</a>  </td>" . PHP_EOL;
            echo "</tr>". PHP_EOL;
        }
    ?>
                
            </tbody>
        </table>


        <div class="contenedor">
            <div class="enlaces">
                <a href="../registro/formularioUsuario.php">Nuevo usuario</a>
            </div>
        </div>


    <h1>Listado de GESTORES</h1>

    <?php
        // Realiza la conexion a la base de datos a través de una función 

        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
        
        // Realiza la consulta a ejecutar en la base de datos en una variable

        $consulta = "SELECT gestores.id, nombre, email, perfil, gestores.created_at, gestores.updated_at FROM gestores, perfiles 
        WHERE perfil_id = perfiles.id AND perfil_id != 1";
        
        // Obten el resultado de ejecutar la consulta para poder recorrerlo. El resultado es de tipo PDOStatement
        
        $resultado = resultadoConsulta($conexion, $consulta);

    ?>
        <table border="1" cellpadding="10">
            <thead>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Perfil</th>
                <th>Fecha de Alta</th>
                <th>Última Modificación</th>
                <th>Acciones</th>
            </thead>
            <tbody>
                
                <!-- Muestra los datos -->

        <?php
        $resultado->bindColumn(1, $id);
        $resultado->bindColumn(2, $nombre);
        $resultado->bindColumn(3, $email);
        $resultado->bindColumn(4, $perfil);
        $resultado->bindColumn(5, $fecha_alta);
        $resultado->bindColumn(6, $fecha_modif);


        while ($registro = $resultado->fetch(PDO::FETCH_BOUND)) {
            echo "<tr>" . PHP_EOL;
            echo "<td>$id</td> <td>$nombre</td> <td>$email</td> <td>$perfil</td> <td>$fecha_alta</td> <td>$fecha_modif</td>
                <td> <a href='modificarGestor.php?gestorId=$id' class='estilo_enlace'>&#9998</a>
                    <a href='borrarGestor.php?gestorId=$id' class='confirmacion_borrar'>&#128465</a>  </td>" . PHP_EOL;
            echo "</tr>". PHP_EOL;
        }
    ?>
                
            </tbody>
        </table>




        <div class="contenedor">
            <div class="enlaces">
                <a href="../registro/formularioGestor.php">Nuevo gestor</a>
            </div>
        </div>


        <h1>Listado de OFERTAS</h1>

    <?php
        // Realiza la conexion a la base de datos a través de una función 

        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
        
        // Realiza la consulta a ejecutar en la base de datos en una variable

        $consulta = "SELECT o.id, usuario_id, nombre, categoria, descripcion, fecha_actividad, aforo, visada, o.created_at, o.updated_at  
                FROM ofertas o INNER JOIN categorias c ON categoria_id = c.id ORDER BY fecha_actividad DESC";
        
        // Obten el resultado de ejecutar la consulta para poder recorrerlo. El resultado es de tipo PDOStatement
        
        $resultado = resultadoConsulta($conexion, $consulta);

    ?>
        <table border="1" cellpadding="10">
            <thead>
                <th>ID</th>
                <th>Ofertante</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Descripción</th>
                <th>Fecha Actividad</th>
                <th>Aforo</th>
                <th>Revisada</th>
                <th>Fecha de Alta</th>
                <th>Última Modificación</th>
                <th>Acciones</th>
            </thead>
            <tbody>
                
                <!-- Muestra los datos -->

        <?php
        $resultado->bindColumn(1, $id);
        $resultado->bindColumn(2, $usuario);
        $resultado->bindColumn(3, $nombre);
        $resultado->bindColumn(4, $categoria);
        $resultado->bindColumn(5, $descripcion);
        $resultado->bindColumn(6, $fechaActividad);
        $resultado->bindColumn(7, $aforo);
        $resultado->bindColumn(8, $visada);
        $resultado->bindColumn(9, $fecha_alta);
        $resultado->bindColumn(10, $fecha_modif);


        while ($registro = $resultado->fetch(PDO::FETCH_BOUND)) {
            echo "<tr>" . PHP_EOL;
            echo "<td>$id</td> <td>$usuario</td> <td>$nombre</td> <td>$categoria</td> <td>$descripcion</td> <td>$fechaActividad</td> <td>$aforo</td> <td>$visada</td> 
            <td>$fecha_alta</td> <td>$fecha_modif</td>
            <td>    <a href='modificarOferta.php?ofertaId=$id' class='estilo_enlace'>&#9998</a>
                    <a href='borrarOferta.php?ofertaId=$id' class='confirmacion_borrar'>&#128465</a>  </td>" . PHP_EOL;
            echo "</tr>". PHP_EOL;
        }
    ?>
                
            </tbody>
        </table>




        <div class="contenedor">
            <div class="enlaces">
                <a href="nuevaOferta.php">Nueva oferta</a>
                <p>    
                     <a href="../ControlAcceso/cerrar-sesion.php">Cerrar sesión</a>
                                     
                    <a href="../index.php">Volver a página de inicio</a>
                 </p>
            </div>
        </div>

    
    <?php

        // Libera el resultado y cierra la conexión
    
        $resultado = null;
        $conexion = null;
    ?>
</body>
</html>

<?php
    }   
        //cierre del if inicial
    ?>
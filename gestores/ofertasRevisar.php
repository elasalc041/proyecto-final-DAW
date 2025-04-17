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

    // Comprueba que el rol sea Gestor, en caso contrario vuelve a la página inicial
	if ($perfil !== 2) {
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
    <title>Listado ofertas Revisar</title>
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
</head>
<body>
<div class="contenedor">
    
    <h1>Listado Actividades por Revisar</h1>

    <?php
        // Realiza la conexion a la base de datos a través de una función 

        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

        // Realiza la consulta a ejecutar en la base de datos para obteneter ofertas

       $consulta = "SELECT o.id as id, nombre, categoria, descripcion, fecha_actividad, aforo, visada  
                FROM ofertas o INNER JOIN categorias c ON categoria_id = c.id ORDER BY fecha_actividad DESC";

        // Obten el resultado de ejecutar la consulta para poder recorrerlo. El resultado es de tipo PDOStatement

        $resultado = resultadoConsulta($conexion, $consulta);
    
    ?>

    <div class="tabla">
         <form action='revisar.php' method='post'>
            <table border="1" cellpadding="10">

                <thead>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Aforo</th>
                    <th>Revisada</th>
                    <th>Acciones</th>
                </thead>
                <tbody>

                        <!-- Muestra los datos -->
                <?php
                    while ($registro = $resultado->fetch(PDO::FETCH_ASSOC)) {          
                            //solo mostrar actividades que no haya pasado la fecha aún y no estén visadas
                            if ($registro["fecha_actividad"] > $fechaActual && $registro["visada"] == 0) {
                                echo "<tr>" . PHP_EOL;
                                echo "<td>$registro[nombre]</td> <td>$registro[categoria]</td> <td>$registro[descripcion]</td>  <td>$registro[fecha_actividad]</td>  
                                <td>$registro[aforo]</td> <td></td> <td> <input type='checkbox' name='$registro[id]' value='1'> </td>" . PHP_EOL;
                                echo "</tr>". PHP_EOL;

                            }                  
                        
                    }
                ?>       


                </tbody>
            
            </table>

               
            <div id='contenedor-boton'>
                <input type='submit' id='btnVisar' value='Visar'></input>
            </div>

         </form>
    </div>

  
         
        <div class="contenedor">
            <div class="enlaces">
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
</div>
</body>
</html>

<?php
    }   
        //cierre del if inicial
    ?>
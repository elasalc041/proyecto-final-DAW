<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();

if (!isset($_SESSION["email"])){
    header("Location: ../index.php");
}else{
    $email = $_SESSION["email"];

    //obtener el id del usuario
    $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

    $consulta = "select * FROM usuarios WHERE email = :email;";

    $consulta = $conexion->prepare($consulta);

    // Ejecuta consulta
    $consulta->execute([
        "email" => $email
    ]);

    // Guardo el resultado
    $resultado = $consulta->fetch();

    // Guardo el id
    $id = (int) $resultado["id"];



    //obtener las ofertas publicadas por el usuario
    $consulta2 = "SELECT o.id as id, nombre, categoria, descripcion, fecha_actividad, aforo, visada  
        FROM ofertas o INNER JOIN categorias c ON categoria_id = c.id
        WHERE usuario_id= :usuario";

    $consulta2 = $conexion->prepare($consulta2);

    // Ejecuta consulta
    $consulta2->execute([
        "usuario" => $id
    ]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oferta Publicadas</title>
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
</head>
<body>


<div class="contenedor">
    <div class="tabla">
        <h1>Plataforma de Pepito01</h1>
        <h2>Listado con tus ofertas</h2>

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
            while ($registro = $consulta2->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>" . PHP_EOL;
                echo "<td>$registro[nombre]</td> <td>$registro[categoria]</td> <td>$registro[descripcion]</td>  <td>$registro[fecha_actividad]</td>  
                <td>$registro[aforo]</td> " . PHP_EOL;
                if ($registro["visada"] == 1) {
                   echo "<td style='color: green'>&#10004</td>" . PHP_EOL;
                   echo "<td></td>" . PHP_EOL;
                }else{     
                    echo "<td></td>" . PHP_EOL;               
                    echo "<td><a href='modificar.php?ofertaId=$registro[id]' class='estilo_enlace'>&#9998</a>
                    <a href='borrar.php?ofertaId=$registro[id]' class='confirmacion_borrar'>&#128465</a>  </td>" . PHP_EOL;
                }

                echo "</tr>". PHP_EOL;
            }
        ?>

            </tbody>
        </table>




    </div>
</div>
<div class="enlaces">
            <a href="../index.php">Volver al inicio</a>
            <a href="nuevaOferta.php">Añadir</a>                      
            <p>
                <a href="../ControlAcceso/cerrar-sesion.php">Cerrar sesión</a>
            </p>
</div>

</body>
<?php

$resultado = null;
$conexion = null;

    }   
        //cierre del if
    ?>
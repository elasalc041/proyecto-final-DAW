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

    $consulta = "SELECT * FROM usuarios WHERE email = :email";

    $consulta = $conexion->prepare($consulta);

    $consulta->execute([
        "email" => $email
    ]);

    $resultado = $consulta->fetch();

    // Guardo el perfil
    $perfil = (int) $resultado["rol_id"];

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
    <title>Vista Administrador</title>
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
</head>
<body>
    <header>
        <nav>
            <a href='../index.php' class='estilo_enlace'><button>Volver</button></a>
            <a href="../ControlAcceso/cerrar-sesion.php" class='estilo_enlace'><button>Salir</button></a>
        </nav>
	</header>
    <main class="contenido">
        <section class="fotos">
            <h1>Fotos pendientes de validación</h1>
            <?php
			//fotos pendientes de validación
            $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

			$select = "SELECT * FROM fotos WHERE estado = 'pendiente' ORDER BY fecha asc";

			$consulta = $conexion->query($select);

			$consulta->execute();

			// comprobamos si algún registro 
			if ($consulta->rowCount() == 0)
			{
				echo "<h3>Ninguna foto pendiente de validar</h3>" . PHP_EOL;
			}else{
				while ($resultado = $consulta->fetch(PDO::FETCH_ASSOC)) {
					echo "<article class='foto'>" . PHP_EOL;
					echo "<img src='../$resultado[url]' alt='Foto $resultado[id_foto]'></img>" . PHP_EOL;
					echo "<p>$resultado[nombre] $resultado[apellidos]</p>" . PHP_EOL;
                    echo "<p>Rally $resultado[rally_id]</p>" . PHP_EOL;
					echo "<a href='revisar.php?id=$resultado[id_foto]&validar=1' class='estilo_enlace'><button>Validar</button></a>" . PHP_EOL;
                    echo "<a href='revisar.php?id=$resultado[id_foto]&validar=0' class='estilo_enlace'><button>Rechazar</button></a>" . PHP_EOL;
					echo "</article>". PHP_EOL;
				}					
			}
			
			?>
        </section>
        <section class="tabla-rallies">
            <h1>Rallies</h1>
            <a href="../rally/nuevo.php"><button>Nuevo rally</button></a>  
            <table border="1" cellpadding="10">
                <tbody>
            <?php

                $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
        
                $consulta = "SELECT r.*, count(i.usuario_id) as registrados FROM rally r
                LEFT JOIN inscripciones i ON r.id_rally = i.rally_id GROUP BY r.id_rally";
        
                $resultado = resultadoConsulta($conexion, $consulta);

                while ($registro = $resultado->fetch()) {
                    echo "<tr>" . PHP_EOL;
                    echo "<td>Rally $registro[id_rally] $registro[titulo]</td> 
                        <td rowspan='2'>    
                            <a href='../rally/modificar.php?rally=$registro[id_rally]' class='estilo_enlace'><button>Modificar</button></a>
                            <a href='../rally/eliminar.php?rally=$registro[id_rally]' class='estilo_enlace'><button>Eliminar</button></a>
                            <a href='../rally/rally.php?rally=$registro[id_rally]' class='estilo_enlace'><button>Ir</button></a> 
                        </td>" . PHP_EOL;
                    echo "</tr>". PHP_EOL;
                    echo "<tr>" . PHP_EOL;
                    echo "<td>$registro[fecha_ini] | $registro[fecha_fin].  $registro[localidad]
                    Límite participantes: $registro[participantes] -- Usuarios inscritos: $registro[registrados]</td>" . PHP_EOL;
                    echo "</tr>". PHP_EOL;
                }

            ?>    
                </tbody>
            </table>
        </section>

        <section class="tabla-usuarios">
            <h1>Usuarios</h1>
            <a href="nuevoUsuario.php"><button>Nuevo usuario</button></a>  
            <table border="1" cellpadding="10">
                <tbody>
            <?php
        
                $consulta = "SELECT id_usuario, nombre, apellidos, email, tfno, fecha, img FROM usuarios WHERE rol_id = 2";
        
                $resultado = resultadoConsulta($conexion, $consulta);

                while ($registro = $resultado->fetch()) {
                    echo "<tr>" . PHP_EOL;
                    echo "<td>$registro[nombre] $registro[apellidos]</td>";
                    if ($registro["img"] != null) {
                        echo "<td rowspan='2'><img class='avatar' src='../$registro[img]' alt='Foto perfil usuario$registro[id_usuario]'/></td>" ;
                    }else{
                        echo "<td rowspan='2'><img class='avatar' src='../img/avatar.svg' alt='Foto avatar'/></td>" ; 
                    }                         
                    echo   "<td rowspan='2'>    
                            <a href='modificarUsuario.php?id=$registro[id_usuario]' class='estilo_enlace'><button>Modificar</button></a>
                            <a href='../usuarios/eliminar.php?id=$registro[id_usuario]' class='estilo_enlace'><button>Eliminar</button></a>
                        </td>" . PHP_EOL;
                    echo "</tr>". PHP_EOL;
                    echo "<tr>" . PHP_EOL;
                    echo "<td>$registro[email] -- $registro[tfno]</td>" . PHP_EOL;
                    echo "</tr>". PHP_EOL;
                }

            ?>    
                </tbody>
            </table>
        </section>
    </main>

    
    <?php
            // Libera el resultado y cierra la conexión
    
        $resultado = null;
        $conexion = null;

        include '../utiles/footer.php';
    ?>
    
</body>
</html>

<?php
    }   
        //cierre del if inicial
    ?>
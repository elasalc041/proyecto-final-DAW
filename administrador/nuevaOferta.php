<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();
// Comprueba si existe la sesión "email", en caso contrario vuelve a la página inicial
if (!isset($_SESSION["email"])){
    header("Location: ../index.php");
} else{
	$email = $_SESSION["email"];

	//obtener el id del usuario
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


	if ($perfil != 1) {
		$resultado = null;
		$conexion = null;
		header("Location: ../index.php");
  		exit();
	}

	$resultado = null;
	$conexion = null;


	// Crea las variables necesarias para introducir los campos y comprobar errores.
        $errores = [];
    	$conexionRealizada = false;
    	$nombreOferta = "";
    	$descripcionOferta = "";
		$categoria = "";
		$fecha = "";
    	$aforo = 0;

		//si entramos por el formulario
    	if ($_SERVER["REQUEST_METHOD"]=="POST")
    	{
		    
		    $conexionRealizada = true;
		    
		 // Obtenemos los diferentes campos del formulario a partir de la función "obtenerValorCampo"
		 $idOfertante = obtenerValorCampo("id");		  
		 $categoria = obtenerValorCampo("categoria_id");
		 $nombreOferta = obtenerValorCampo("nombre");
		 $descripcionOferta = obtenerValorCampo("descripcion");
		 $fecha = obtenerValorCampo("fecha_actividad");
		 $aforo = obtenerValorCampo("aforo");
		 $visada = 0;
		    
	    	//-----------------------------------------------------
	        // Validaciones
	        //-----------------------------------------------------
			  // ID debe ser número entero positivo
			  if (!validarEnteroPositivo($idOfertante))
			  {
				  $errores["id"] = "Campo id debe ser positivo";
				  $idOfertante = ""; 
			  }else 
			  {
				  // Comprobar existe un ofertante con el id.
				  //Para ello, te conectas a la bbdd, ejecutas un SELECT y comprueba si id existe y pertenece a un ofertante.
				  $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
				  $consulta = "SELECT * FROM usuarios WHERE id = '$idOfertante' AND perfil_id = 3";
  
				  $consulta = $conexion->query($consulta);
				  
				  // comprobamos si, al ejecutar la consulta, tenemos más de 0 registro. En tal caso, generar el mensaje de error.
				  if ($consulta->rowCount() == 0)
				  {
					  //Msj Error
					  $errores["id"] = "El id no es correcto";
					  $idOfertante = "";
				  }
  
				  $consulta = null;
				  $conexion = null;					
			  }	


	        // Nombre de la oferta debe rellenarse
	        if ($nombreOferta == "")
	        {
	            $errores["nombre"] = "Campo nombre no puede quedar vacío";
				$nombre = ""; 
	        }else 
			{
				// Comprobar que no exita una oferta con ese nombre.
				//Para ello, te conectas a la bbdd, ejecutas un SELECT y comprueba si hay ya una oferta con ese nombre.
				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
				$consulta = "SELECT * FROM ofertas WHERE nombre = '$nombreOferta'";

				$consulta = $conexion->query($consulta);
				
				// comprobamos si, al ejecutar la consulta, tenemos más de 0 registro. En tal caso, generar el mensaje de error.
				if ($consulta->rowCount() > 0)
				{
					//Msj Error
					$errores["nombreRepetido"] = "El nombre de la oferta ya existe";
					$nombre = "";
				}

				$consulta = null;
				$conexion = null;					
			}			
			
			// Nombre de categoría a partir de la función "validarEnteroPositivo", ya que usaremos el id
	        if (!validarEnteroPositivo($categoria))
	        {
	            $errores["categoria"] = "Campo categoría no cumple los requisitos establecidos";
				$categoria = ""; 
	        }
	        
	        // Aforo debe ser positivo
	        if (!validarEnteroPositivo($aforo))
	        {
				$errores["aforo"] = "Campo aforo no cumple los requisitos establecidos";
				$aforo = ""; 
	        }


			// Fecha debe tener el formato adecuado y posterior a la fecha de creación 
	        if (!validarFecha($fecha, 'Y-m-d\TH:i'))
	        {
				$errores["fecha"] = "Campo fecha no cumple los requisitos establecidos";
				$fecha = ""; 
	        }
	        
    	}

  		// Si no hay errores y hemos entrado por el formulario, conectar a la BBDD:
		if ( $conexionRealizada && count($errores) == 0):

			$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
  			
			// consulta a ejecutar (insert)

			$consulta = $conexion->prepare("INSERT INTO ofertas (usuario_id, categoria_id, nombre, descripcion, fecha_actividad, aforo, visada, created_at, updated_at) 
													values (:usuario, :categoria, :nombre, :descripcion, :fecha, :aforo, :visada, :fecha_creado, :fecha_modif)");

			// preparar la consulta (usar bindParam)

			$consulta->bindParam(':usuario', $idOfertante);
			$consulta->bindParam(':categoria', $categoria);
			$consulta->bindParam(':nombre', $nombreOferta);
			$consulta->bindParam(':descripcion', $descripcionOferta);
			$consulta->bindParam(':fecha', $fecha);
			$consulta->bindParam(':aforo', $aforo);
			$consulta->bindParam(':visada',  $visada);
			$consulta->bindParam(':fecha_creado', $fechaActual);
			$consulta->bindParam(':fecha_modif', $fechaActual);
			
			// ejecutar la consulta y captura de la excepcion

			try {
				$consulta->execute();

				$resultado = null;
        		$conexion = null;
			} catch (PDOException $e) {
				exit($e->getMessage());
			}
			
        	// redireccionamos al listado de ofertas del ofertante
  			header("Location: listados.php");
  			exit();  
  

	//Si hay algún error, tenemos que mostrar los errores en la misma página, manteniendo los valores bien introducidos.
  		else:
	?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta nueva oferta</title>
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
</head>
<body>
    <h1>Nueva oferta</h1>

			<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
			<p>
	            <!-- Campo aforo -->
				<label for="ofertante">ID ofertante</label>
	            <input type="number" name="id" placeholder="ID ofertante" value="<?php echo $idOfertante ?>">
	            <?php
	            	if (isset($errores["id"])):
	            ?>
	            	<p class="error"><?php echo $errores["id"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>
	    	<p>
	            <!-- Campo nombre -->
				<label for="nombre">Nombre</label>
	            <input type="text" name="nombre" placeholder="Nombre" value="<?php echo $nombreOferta ?>">
	            <?php
	            	if (isset($errores["nombre"])):
	            ?>
	            	<p class="error"><?php echo $errores["nombre"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>
	        <p>
	            <!-- Campo descripción de la oferta -->
				<label for="descripcion">Descripción</label>
	            <textarea name="descripcion" rows="10" cols="50" placeholder="Descripcion"><?php echo $descripcionOferta ?></textarea>
	        </p>
	        <p>
	            <!-- Campo fecha actividad -->
				<label for="fecha">Fecha Actividad</label>	 
	            <input type="datetime-local" name="fecha_actividad" value="<?php echo $fecha ?>">
	            <?php
	            	if (isset($errores["fecha"])):
	            ?>
	            	<p class="error"><?php echo $errores["fecha"]?></p>
	            <?php
	            	endif;
	            ?>
	        </p>
	        <p>
	            <!-- Campo aforo -->
				<label for="aforo">Aforo</label>
	            <input type="number" name="aforo" placeholder="Aforo" value="<?php echo $aforo ?>">
	            <?php
	            	if (isset($errores["aforo"])):
	            ?>
	            	<p class="error"><?php echo $errores["aforo"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>
	        <p>
	            <!-- Campo categoría -->
	            <select id="categoria" name="categoria_id">
	            	<option value="">Seleccione Categoria</option>
	            <?php
				//Conectar a la base de datos para tomar los posibles valores de las categorias.
	            	$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

				//Usamos un SELECT para traer los valores del id y la categoria 
				//Obtenemos el resultado de la consulta con la función "resultadoConsulta($conexion, $consulta)"

				$consulta = "SELECT * FROM categorias";	            	
	            $resultado = resultadoConsulta($conexion, $consulta);

					while ($row = $resultado->fetch(PDO::FETCH_ASSOC)):
  				?>
  					<option value="<?php echo $row["id"]; ?>" <?php echo $row["id"] == $categoria ? "selected" : ""?>><?php echo $row["categoria"]; ?></option>
  				<?php
  					endwhile;

  					$consulta = null;
        			$conexion = null;
  				?>
  				</select>
  				
	            <?php
	            	if (isset($errores["categoria"])):
	            ?>
	            	<p class="error"><?php echo $errores["categoria"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>
	        <p>
	            <!-- Botón submit -->
	            <input type="submit" value="Guadar">
	        </p>
	    </form>

    <div class="contenedor">
        <div class="enlaces">
            <a href="listados.php">Volver al listado de ofertas</a>
			<p>
                <a href="../ControlAcceso/cerrar-sesion.php">Cerrar sesión</a>
            </p>
        </div>
   </div>
</body>
</html>

	<?php 			
		endif;
		?>
		
<?php
    }   
        //cierre del if inicial
    ?>
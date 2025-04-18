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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Perfil</title>
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
</head>
<body>
<body>	
	<header>
        <nav>
            <a href='perfil.php' class='estilo_enlace'><button>Volver</button></a>
            <a href="../ControlAcceso/cerrar-sesion.php" class='estilo_enlace'><button>Salir</button></a>
        </nav>
	</header>
    <main class="contenedor">	
    <h1>Modificar Perfil</h1>
    <?php
		// crea las variables para la comprobación de los datos y conectamos con la BBDD para obtener y pintar los datos de la id que acabamos de enviar a la página
		$comprobarConexion = false;
		$errores = [];
    	
		//si entramos por el formulario o por el enlace de modificar
    	if (count($_REQUEST) > 0) 
    	{
			//si entramos por enlace para modificar
    		if (isset($_GET["id"])) 
    		{
            	$usuario = $_GET["id"];

            	//Conectamos a la BBDD para obtener datos del usuario

				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
            
        		// Montamos la consulta a ejecutar

				$consulta = "SELECT * FROM usuarios WHERE id_usuario = ?";

				$consulta = $conexion->prepare($consulta);

				$consulta->bindParam(1, $usuario);

				$consulta->execute();

		        // comprobamos si hay algún registro 
				if ($consulta->rowCount() == 0)
				{
					//Si no lo hay, desconectamos y volvemos al perfil 
					$consulta = null;
					$conexion = null;
					header("Location: perfil.php");
				}
				else 
				{
					// Si hay algún registro, Obtenemos el resultado (usamos fetch())
						
					$registro = $consulta->fetch();
					$nombre = $registro["nombre"];					
					$apellidos = $registro["apellidos"];
					$descripcion = $registro["descripcion"];
					$tfno = $registro["tfno"];
					$token = $registro["token"];
					$rutaFoto = $registro["img"];

					$consulta = null;
					$conexion = null;	        
				}
            } 
            else 
            {
				//si entramos por formulario
				$comprobarConexion = true;
		    	// Obtenemos campos introducidos y comenzamos la comprobación de los datos .
			    
				$usuario = obtenerValorCampo("id"); 			   
				$nombre = obtenerValorCampo("nombre");  
				$apellidos = obtenerValorCampo("apellidos");  
				$descripcion = obtenerValorCampo("descripcion");
				$tfno = obtenerValorCampo("tfno");
				$token = obtenerValorCampo("token");
				$rutaFoto = obtenerValorCampo("imagen_actual");
			 

				 //-----------------------------------------------------
		        // Validaciones
		        //-----------------------------------------------------
				// Comprueba que el id proveniente del formulario se corresponde con uno que tengamos 
				//conectamos a la bbdd

				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

				$consulta = "SELECT * FROM usuarios WHERE id_usuario = ?";

				$consulta = $conexion->prepare($consulta);

				$consulta->bindParam(1, $usuario);

				$consulta->execute();

				// comprobamos si algún registro 
				if ($consulta->rowCount() == 0)
				{
					//Si no lo hay, desconectamos y volvemos al listado original
					$consulta = null;
					$conexion = null;
					header("Location: perfil.php");
				}
		        
	        	// Nombre y apellidos del usuario  debe rellenarse
				if ($nombre == "")
				{
					$errores["nombre"] = "Campo nombre no puede quedar vacío";
					$nombre = ""; 
				}
				
				if ($apellidos == "")
				{
					$errores["apellidos"] = "Campo apellidos no puede quedar vacío";
					$apellidos = ""; 
				}

				if ($tfno != null && validarEnteroLimites($tfno, 600000000, 799999999))
				{
					$errores["tfno"] = "Campo teléfono no es correcto.";
					$tfno = null;
				}

				// Si el teléfono está vacío, lo establecemos como null
				if (trim($tfno) === "") {
					$tfno = null;
				}
				
				
				// Comprobación de imagen subida

				if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
					$directorioSubida = "uploads/usuarios/$usuario/perfil/";
					$nombreOriginal = basename($_FILES['imagen']['name']);
					$rutaFoto = $directorioSubida . $nombreOriginal;

					
					// Generamos nombre único
					//$extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
					//$nombreImagen = uniqid() . "." . strtolower($extension);
					//$rutaFinal = $directorioSubida . $nombreImagen;


					// Validar tipo y tamaño
					$tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/svg'];
					if (!in_array($_FILES['imagen']['type'], $tiposPermitidos)) {
						$errores['imagen'] = "Solo se permiten imágenes JPEG, PNG, SVG o GIF.";
					} elseif ($_FILES['imagen']['size'] > 2 * 1024 * 1024) {
						$errores['imagen'] = "La imagen no puede superar los 2MB.";
					} else {
						move_uploaded_file($_FILES['imagen']['tmp_name'], "../$rutaFoto");
					}
				}

		       
			}
		    
    	} 
    	
  	?>

  	<?php
		//modificar clave
		$emailEncode = urlencode($email);
        $tokenEncode = urlencode($token);

		echo "
		<div>
			<a href='../ControlAcceso/establecerUsuario.php?email=$emailEncode&token=$tokenEncode'><button>Cambiar Clave</button></a>
		</div>
		";		

  		//Si hay errores, pintarlos en el correspondiente campo:
		  if (!$comprobarConexion || count($errores) > 0):
			{ 
  	?>
  		<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
	    	<input type="hidden" name="id" value="<?php echo $usuario ?>">
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<input type="hidden" name="imagen_actual" value="<?php echo $rutaFoto ?>">
	    	<p>
	            <!-- Campo nombre -->
				<label for="nombre">Nombre: </label>
	            <input type="text" name="nombre" placeholder="Nombre" value="<?php echo $nombre ?>" required>
	            <?php
					if (isset($errores["nombre"])):
						{	
	            ?>
	            	<p class="error"><?php echo $errores["nombre"] ?></p>
	            <?php
						}
	            	endif;
	            ?>
	        </p>
			<p>
	            <!-- Campo Apellidos -->
				<label for="apellidos">Apellidos: </label>
	            <input type="text" name="apellidos" placeholder="Apellidos" value="<?php echo $apellidos ?>" required>
	            <?php
					if (isset($errores["apellidos"])):
						{	
	            ?>
	            	<p class="error"><?php echo $errores["apellidos"] ?></p>
	            <?php
						}
	            	endif;
	            ?>
	        </p>
			<p>
	            <!-- Campo tfno -->
				<label for="tfno">Teléfono: </label>
	            <input type="number" name="tfno" placeholder="6xxxxxxxx" value="<?php echo $tfno ?>" min="600000000" max="799999999">
	            <?php
	            	if (isset($errores["tfno"])):
	            ?>
	            	<p class="error"><?php echo $errores["tfno"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>
			<p>
	            <!-- Descripción -->
				<label for="descripcion">Descripción: </label>
	            <textarea name="descripcion" rows="5" cols="33" placeholder="Añade descripción de ti mismo ..." maxlength="600" value="<?php echo $descripcion ?>">
				<?php echo $descripcion ?></textarea>
	        </p>
			<p>
				<!-- Imagen de perfil -->
				<label for="imagen">Imagen de perfil:</label>
				<input type="file" name="imagen" accept="image/*">
				<?php
					if (isset($errores["imagen"])):
						{	
	            ?>
	            	<p class="error"><?php echo $errores["imagen"] ?></p>
	            <?php
						}
	            	endif;
	            ?>
			</p>			
	        <p>
	            <!-- Botón submit -->
	            <input type="submit" value="Modificar">
	        </p>
	    </form>
  	<?php
		// Si no hay errores
		}
  		else:
  			//Nos conectamos a la BBDD

			  $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
			
			// Creamos una variable con la consulta "UPDATE" a ejecutar

			$consulta = "UPDATE usuarios SET nombre= :nombre, nombre= :apellidos, descripcion= :descripcion, tfno= :tfno, img= :foto
  							WHERE id_usuario = :id";
			
			// preparamos la consulta (bindParam)

			$resultado = $conexion->prepare($consulta);

			$resultado->bindParam(":nombre", $nombre);			
			$resultado->bindParam(":apellidos", $apellidos);
			$resultado->bindParam(":descripcion", $descripcion);
			$resultado->bindParam(":tfno", $tfno);
			$resultado->bindParam(":foto", $rutaFoto);
			$resultado->bindParam(":id", $usuario);

			// ejecutamos la consulta 
			try 
			{
				$resultado->execute();
			}
			catch (PDOException $exception)
			{
           		exit($exception->getMessage());
        	}

			$resultado = null;

			$conexion = null;

        	// redireccionamos al perfil
  			header("Location: perfil.php");
  			
    	endif;
    ?>
	</main>
</body>
</html>

<?php
    }   
        //cierre del if inicial
    ?>
<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

		// Crea las variables necesarias para introducir los campos y comprobar errores.
		$nombre = "";
		$emailNuevo = "";
		$apellidos = "";
		$descripcion = null;
		$tfno = null;	
		$errores = [];
		$conexionRealizada = false;	


    	if ($_SERVER["REQUEST_METHOD"]=="POST")
    	{
			$conexionRealizada = true;
			
		    // Obtenemos el valor de los campos del formulario a partir de la función "obtenerValorCampo"

			$nombre = obtenerValorCampo("nombre");  	
			$emailNuevo = obtenerValorCampo("email");  	
			$apellidos = obtenerValorCampo("apellidos");
			$tfno = obtenerValorCampo("tfno");
			$descripcion = obtenerValorCampo("descr");
		    
	    	//-----------------------------------------------------
	        // Validaciones
	        //-----------------------------------------------------
			//nombre y apellidos no puede quedar vacío
			if ($nombre == "")
	        {
				$errores["nombre"] = "Campo nombre no puede quedar vacío.";
				$nombre = "";
	        }             
			if ($apellidos == "")
	        {
				$errores["apellidos"] = "Campo apellidos no puede quedar vacío.";
				$apellidos = "";
	        }

	        // Nombre del email: Debe tener formato de email.
	        if (!validarEmail($emailNuevo)) 
	        {
				$errores["email"] = "El email no tiene un formato correcto";
				$emailNuevo = "";
	        } 
	        else 
	        {
	        	// En caso de que los datos sean correctos, comprobar que no exita un email con ese nombre.
				// Para ello, conectaros a la bbdd, usar el comando SELECT en usuarios y buscar el email que se ha introducido.
				// Si el resultado es distinto de nulo, informar de que el email ya existe.
	        	
				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

				$consulta = "SELECT email FROM usuarios
        					WHERE email = '$emailNuevo'";

				$resultado = resultadoConsulta($conexion, $consulta);

				if ($resultado->fetch() != null) {
					$errores["email"] = "El email ya existe";
					$emailNuevo = "";
				}

				$resultado = null;
        		$conexion = null;

	        }

			if ($tfno != null && validarEnteroLimites($tfno, 600000000, 799999999))
	        {
				$errores["tfno"] = "Campo tetéfono no es correcto.";
				$tfno = null;
	        }

			// Si el teléfono está vacío, lo establecemos como null
			if (trim($tfno) === "") {
				$tfno = null;
			}
	        
    	}
  	
  		// Si no hay errores, creamos sesion y conectar a la BBDD en alta.php:
		if ( $conexionRealizada && count($errores) == 0):

			session_name("sesion-privada");
            session_start();

			$_SESSION["nombre"] = $nombre;
			$_SESSION["emailNuevo"] = $emailNuevo;
            $_SESSION["apellidos"] = $apellidos;
			$_SESSION["descripcion"] = $descripcion;
			$_SESSION["tfno"] = $tfno;

            header("Location: altaUsuario.php");
  
 		//Si hay algún error o primera vez que entra, tenemos que mostrar los errores en la misma página, manteniendo los valores bien introducidos.
  		else:
	?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta nuevo usuario</title>
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
</head>
<body>
    <h1>Alta de un nuevo usuario</h1>

			<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
			<p>
	            <!-- Campo nombre nuevo -->
				<label for="nombre">Nombre*: </label>
	            <input type="text" name="nombre" placeholder="nombre" value="<?php echo $nombre ?>" required>
	            <?php
	            	if (isset($errores["nombre"])):
	            ?>
	            	<p class="error"><?php echo $errores["nombre"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>
			<p>
	            <!-- Campo apellidos -->
				<label for="apellidos">Apellidos*: </label>
	            <input type="text" name="apellidos" placeholder="apellidos" value="<?php echo $apellidos ?>" required>
	            <?php
	            	if (isset($errores["apellidos"])):
	            ?>
	            	<p class="error"><?php echo $errores["apellidos"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>	
	    	<p>
	            <!-- Campo email nuevo -->
				<label for="email">Email*: </label>
	            <input type="email" name="email" placeholder="ejemplo@email.com" value="<?php echo $emailNuevo ?>" required>
	            <?php
	            	if (isset($errores["email"])):
	            ?>
	            	<p class="error"><?php echo $errores["email"] ?></p>
	            <?php
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
				<label for="descr">Descripción: </label>
	            <textarea name="descr" rows="5" cols="33" placeholder="Añade descripción de ti mismo ..." maxlength="600">
				</textarea>
	        </p>
	        <p>
	            <!-- Botón submit -->
	            <input type="submit" value="Guardar">
	        </p>
	    </form>

   <div class="contenedor">
        <div class="enlaces">
            <a href="../index.php">Volver al inicio</a>
        </div>
   </div>
</body>
</html>


	<?php 			
			endif;
		?>


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

    $consulta = "select * FROM usuarios WHERE email = :email;";

    $consulta = $conexion->prepare($consulta);

    // Ejecuta consulta
    $consulta->execute([
        "email" => $email
    ]);

    // Guardo el resultado
    $resultado = $consulta->fetch();

    // Guardo el perfil
    $perfil = (int) $resultado["rol_id"];


	if ($perfil != 1) {
		$resultado = null;
		$conexion = null;
		header("Location: ../index.php");
  		exit();
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
    <title>Modificar usuario</title>
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
</head>
<body>
<body>
	<header>
        <nav>
            <a href='listados.php' class='estilo_enlace'><button>Volver</button></a>
            <a href="../ControlAcceso/cerrar-sesion.php" class='estilo_enlace'><button>Salir</button></a>
        </nav>
	</header>
    <main class="contenedor">	
    <h1>Modificar Usuario</h1>
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

            	//Conectamos a la BBDD para obtener datos el usuario para modificar

				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
            
        		// Montamos la consulta a ejecutar

				$consulta = "SELECT * FROM usuarios WHERE id_usuario = ?";
        	
		        // prepararamos la consulta

				$consulta = $conexion->prepare($consulta);
			
		        // parámetro (usamos bindParam)

				$consulta->bindParam(1, $usuario);
		    
		        // ejecutamos la consulta 

				$consulta->execute();

		        // comprobamos si hay algún registro 
				if ($consulta->rowCount() == 0)
				{
					//Si no lo hay, desconectamos y volvemos al listado 
					$consulta = null;
					$conexion = null;
					header("Location: listados.php");
				}
				else 
				{
					// Si hay algún registro, Obtenemos el resultado (usamos fetch())
						
					$registro = $consulta->fetch();
					$nombre = $registro["nombre"];
					$apellidos = $registro["apellidos"];
					$descripcion = $registro["descripcion"];
					$email = $registro["email"];
					$tfno = $registro["tfno"];
					$activo = $registro["activo"];

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
				$email = obtenerValorCampo("email");
				$descripcion = obtenerValorCampo("descripcion");
				$tfno = obtenerValorCampo("tfno");
				$activo = obtenerValorCampo("activo");
			 

				 //-----------------------------------------------------
		        // Validaciones
		        //-----------------------------------------------------
				// Comprueba que el id proveniente del formulario se corresponde con uno que tengamos 
				//conectamos a la bbdd

				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

				$consulta = "SELECT * FROM usuarios WHERE id_usuario = ?";
        	
		        // prepararamos la consulta

				$consulta = $conexion->prepare($consulta);
			
		        // parámetro (usamos bindParam)

				$consulta->bindParam(1, $usuario);
		    
		        // ejecutamos la consulta 

				$consulta->execute();

				// comprobamos si algún registro 
				if ($consulta->rowCount() == 0)
				{
					//Si no lo hay, desconectamos y volvemos al listado original
					$consulta = null;
					$conexion = null;
					header("Location: listados.php");
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
				
				// Email debe tener formato correcto de email
				if (!validarEmail($email))
				{
					$errores["email"] = "Campo email no tiene formato correcto";
					$email = ""; 
				}else{

					// Comprobar que no exita un email igual.
					//Para ello, te conectas a la bbdd, ejecutas un SELECT .
					$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
					$consulta = "SELECT * FROM usuarios WHERE email = '$email' AND id_usuario != '$usuario'";

					$consulta = $conexion->query($consulta);
		        	
					// comprobamos si, al ejecutar la consulta, tenemos más de 0 registro. En tal caso, generar el mensaje de error.
					if ($consulta->rowCount() > 0)
					{
						//Msj Error
						$errores["email"] = "El email ya existe";
						$email = "";
					}

					$consulta = null;
					$conexion = null;		

				}


				// Activo debe ser 0 o 1
				if (!($activo == 0 || $activo == 1))
				{
					$errores["activo"] = "Campo activo debe ser 0 o 1";
					$activo = ""; 
				}
		        
		       
			}
		    
    	} 
    	
  	?>

  	<?php
  		//Si hay errores, pintarlos en el correspondiente campo:
		  if (!$comprobarConexion || count($errores) > 0):
			{ 
  	?>
  		<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
	    	<input type="hidden" name="id" value="<?php echo $usuario ?>">
			<p>
	            <!-- Campo email -->
				<label for="email">Email: </label>
	            <input type="email" name="email" placeholder="email" value="<?php echo $email ?>" required>
	            <?php
					if (isset($errores["email"])):
						{	
	            ?>
	            	<p class="error"><?php echo $errores["email"] ?></p>
	            <?php
						}
	            	endif;
	            ?>
	        </p>
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
	            <!-- Campo Activo -->
				 <label for="activo">Activo</label>
	            <select id="activo" name="activo" required>
	            	<option value="">Seleccione Activo</option>
					<option value="0" <?php echo 0 == $activo ? "selected" : "" ?>>0</option>
					<option value="1" <?php echo 1 == $activo ? "selected" : "" ?>>1</option>
				</select>
	            <?php
	            	if (isset($errores["activo"])):
	            ?>
	            	<p class="error"><?php echo $errores["activo"] ?></p>
	            <?php
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

			$consulta = "UPDATE usuarios SET nombre= :nombre, apellidos= :apellidos, email= :email, tfno= :tfno, activo= :activo
  							WHERE id_usuario = :id";
			
			// preparamos la consulta (bindParam)

			$resultado = $conexion->prepare($consulta);
			
			$resultado->bindParam(":nombre", $nombre);
			$resultado->bindParam(":apellidos", $apellidos);
			$resultado->bindParam(":email", $email);
			$resultado->bindParam(":tfno", $tfno);;
			$resultado->bindParam(":activo", $activo);;
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

        	// redireccionamos al listado 
  			header("Location: listados.php");
  			
    	endif;
    ?>
	</main>
</body>
</html>

<?php
    }   
        //cierre del if inicial
    ?>
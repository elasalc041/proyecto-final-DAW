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
    <h1>Modificar Usuario</h1>
    <?php
		// crea las variables para la comprobación de los datos y conectamos con la BBDD para obtener y pintar los datos de la id que acabamos de enviar a la página
		$comprobarConexion = false;
		$errores = [];
    	
		//si entramos por el formulario o por el enlace de modificar
    	if (count($_REQUEST) > 0) 
    	{
			//si entramos por enlace para modificar
    		if (isset($_GET["usuarioId"])) 
    		{
            	$usuario = $_GET["usuarioId"];

            	//Conectamos a la BBDD para obtener datos el usuario para modificar

				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
            
        		// Montamos la consulta a ejecutar

				$consulta = "SELECT * FROM usuarios WHERE id = ?";
        	
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
					$email = $registro["email"];
					$perfil = $registro["perfil_id"];
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
				$email = obtenerValorCampo("email");
				$perfil = obtenerValorCampo("perfil");
				$activo = obtenerValorCampo("activo");
			 

				 //-----------------------------------------------------
		        // Validaciones
		        //-----------------------------------------------------
				// Comprueba que el id proveniente del formulario se corresponde con uno que tengamos 
				//conectamos a la bbdd

				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

				$consulta = "SELECT * FROM usuarios WHERE id = ?";
        	
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
		        
	        	// Nombre del usuario debe rellenarse
				if ($nombre == "")
				{
					$errores["nombre"] = "Campo nombre no puede quedar vacío";
					$nombre = ""; 
				}	
		        else 
		        {
		        	// Comprobar que no exita un usuario con ese nombre.
					//Para ello, te conectas a la bbdd, ejecutas un SELECT y comprueba si hay ya ese nombre.
					$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
					$consulta = "SELECT * FROM usuarios WHERE nombre = '$nombre' AND id != '$usuario'";

					$consulta = $conexion->query($consulta);
		        	
					// comprobamos si, al ejecutar la consulta, tenemos más de 0 registro. En tal caso, generar el mensaje de error.
					if ($consulta->rowCount() > 0)
					{
						//Msj Error
						$errores["nombre"] = "El nombre del usuario ya existe";
						$nombre = "";
					}

					$consulta = null;
					$conexion = null;					
		        }


					// Nombre de perfil a partir de la función "validarEnteroPositivo", ya que usaremos el id
				if (!validarEnteroPositivo($perfil))
				{
					$errores["perfil"] = "Campo perfil no cumple los requisitos establecidos";
					$perfil = ""; 
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
					$consulta = "SELECT * FROM usuarios WHERE email = '$email' AND id != '$usuario'";

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
	            <!-- Campo nombre -->
				<label for="nombre">Nombre</label>
	            <input type="text" name="nombre" placeholder="Nombre" value="<?php echo $nombre ?>">
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
	            <!-- Campo email nuevo -->
				 <label for="email">Email</label>
	            <input type="email" name="email" placeholder="email" value="<?php echo $email ?>">
	            <?php
	            	if (isset($errores["email"])):
	            ?>
	            	<p class="error"><?php echo $errores["email"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>
			<p>
	            <!-- Campo perfil -->
				 <label for="perfil">Perfil</label>
	            <select id="perfil" name="perfil">
	            	<option value="">Seleccione Perfil</option>
	            <?php
					//Conectar a la base de datos para tomar los posibles valores de perfiles.
					
	            	$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
					//Usamos un SELECT para traer los valores del id y el nombre de los perfiles.
	            	$consulta = "SELECT id, perfil FROM perfiles";
	            	
	            	$resultado = resultadoConsulta($conexion, $consulta);

  					while ($row = $resultado->fetch(PDO::FETCH_ASSOC)):
//Usamos el $row para darle los valores al desplegable de los perfiles, siendo el id el valor que toma la variable $rol (o como lo hayáis llamado) y el nombre lo que aparece en el desplegable.
						if ($row["id"] == 3 || $row["id"] == 4) {			
						
				?>
  					<option value="<?php echo $row['id']; ?>" <?php echo $row["id"] == $perfil ? "selected" : "" ?>><?php echo $row["perfil"]; ?></option>
  				<?php
						}
  					endwhile;
  					
  					$resultado = null;
        			$conexion = null;
  				?>
  				</select>
  				
	            <?php
	            	if (isset($errores["perfil"])):
	            ?>
	            	<p class="error"><?php echo $errores["perfil"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>

			<p>
	            <!-- Campo Activo -->
				 <label for="activo">Activo</label>
	            <select id="activo" name="activo">
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
	            <input type="submit" value="Guadar">
	        </p>
	    </form>
  	<?php
		// Si no hay errores
		}
  		else:
  			//Nos conectamos a la BBDD

			  $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
			
			// Creamos una variable con la consulta "UPDATE" a ejecutar

			$consulta = "UPDATE usuarios SET nombre= :nombre, email= :email, perfil_id= :perfil, 
							 activo= :activo, updated_at= :fecha_modif
  							WHERE id = :id";
			
			// preparamos la consulta (bindParam)

			$resultado = $conexion->prepare($consulta);
			
			$resultado->bindParam(":nombre", $nombre);
			$resultado->bindParam(":email", $email);
			$resultado->bindParam(":perfil", $perfil);
			$resultado->bindParam(":activo", $activo);
			$resultado->bindParam(":fecha_modif", $fechaActual);
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
    <div class="contenedor">
        <div class="enlaces">
            <a href="listados.php">Volver al listado</a>			
			<p>
				<a href="../ControlAcceso/cerrar-sesion.php">Cerrar sesión</a>
			</p>
        </div>
   	</div>
    
</body>
</html>

<?php
    }   
        //cierre del if inicial
    ?>
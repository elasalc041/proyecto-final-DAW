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
	$nombreAdmin = $resultado["nombre"];


	if ($perfil != 1) {
		$resultado = null;
		$conexion = null;
		header("Location: ../index.php");
  		exit();
	}

	$resultado = null;
	$conexion = null;

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

			if ($tfno != null && !validarEnteroLimites($tfno, 600000000, 799999999))
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
    	

	//Si hay errores o primera vez entramos, pintarlos en el correspondiente campo:
	if (!$comprobarConexion || count($errores) > 0):
	{
?>	
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar usuario</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
</head>
</head>
<body>
<body>
	<header class="sticky-top bg-white shadow-sm">
		<nav class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
			<a href='listados.php'class="btn btn-dark">Volver</a>
			<div class="text-end">
				<span class="me-3 fw-bold">Bienvenido/a <?php echo $nombreAdmin ?></span>
				<a href="../ControlAcceso/cerrar-sesion.php"  class="btn btn-danger">Salir</a>
			</div>
		</nav>
	</header>
    <main class="container">
		<div class="min-vh-100 d-flex justify-content-center align-items-center">	
			<div class="card shadow-sm p-4 my-4">					
    			<h2 class='text-primary-emphasis mb-3'>Modificar Usuario</h2>
				<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
					<input type="hidden" name="id" value="<?php echo $usuario ?>">
					<div class="mb-3">
						<!-- Campo email -->
						<label for="email" class="form-label fw-bold">Email*</label>
						<input type="email"  class="form-control" name="email" placeholder="email" value="<?php echo $email ?>" required>
						<?php
							if (isset($errores["email"])):
								{	
						?>
							<p class="text-danger small"><?php echo $errores["email"] ?></p>
						<?php
								}
							endif;
						?>
					</div>
					<div class="mb-3">
						<!-- Campo nombre -->
						<label for="nombre" class="form-label fw-bold">Nombre*</label>
						<input type="text" name="nombre" class="form-control" placeholder="Nombre" value="<?php echo $nombre ?>" required>
						<?php
							if (isset($errores["nombre"])):
								{	
						?>
							<p class="text-danger small"><?php echo $errores["nombre"] ?></p>
						<?php
								}
							endif;
						?>
					</div>
					<div class="mb-3">
						<!-- Campo Apellidos -->
						<label for="apellidos" class="form-label fw-bold">Apellidos*</label>
						<input type="text" name="apellidos" class="form-control" placeholder="Apellidos" value="<?php echo $apellidos ?>" required>
						<?php
							if (isset($errores["apellidos"])):
								{	
						?>
							<p class="text-danger small"><?php echo $errores["apellidos"] ?></p>
						<?php
								}
							endif;
						?>
					</div>
					<div class="mb-3">
						<!-- Campo tfno -->
						<label for="tfno" class="form-label fw-bold">Teléfono</label>
						<input type="number" name="tfno" class="form-control" placeholder="6xxxxxxxx" value="<?php echo $tfno ?>" min="600000000" max="799999999">
						<?php
							if (isset($errores["tfno"])):
						?>
							<p class="text-danger small"><?php echo $errores["tfno"] ?></p>
						<?php
							endif;
						?>
					</div>
					<div class="mb-3">
						<!-- Descripción -->
						<label for="descripcion" class="form-label fw-bold">Descripción</label>
						<textarea name="descripcion" class="form-control" rows="5" cols="33" placeholder="Añade descripción de ti mismo ..." maxlength="600" ><?php echo $descripcion ?></textarea>
					</div>
					<div class="mb-3">
						<!-- Campo Activo -->
						<label for="activo" class="form-label fw-bold">Activo</label>
						<select id="activo" class="form-select" name="activo" required>
							<option value="">Seleccione Activo</option>
							<option value="0" <?php echo 0 == $activo ? "selected" : "" ?>>0</option>
							<option value="1" <?php echo 1 == $activo ? "selected" : "" ?>>1</option>
						</select>
						<?php
							if (isset($errores["activo"])):
						?>
							<p class="text-danger small"><?php echo $errores["activo"] ?></p>
						<?php
							endif;
						?>
					</div>
					<div class="d-grid">
						<!-- Botón submit -->
						<input type="submit" class="btn btn-dark mx-auto" value="Modificar">
					</div>
				</form>
			</div>
		</div>
	</main>	
	<?php
		include '../utiles/footer.php';
	?>
</body>
</html>
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

<?php
    }   
        //cierre del if inicial
    ?>
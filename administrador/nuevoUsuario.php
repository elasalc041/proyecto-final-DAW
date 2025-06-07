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

			if ($tfno != null && !validarEnteroLimites($tfno, 600000000, 799999999))
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

			$token = bin2hex(openssl_random_pseudo_bytes(16));
			$insert = "INSERT INTO usuarios (nombre, apellidos, email, clave, activo, token, tfno, rol_id, descripcion) VALUES
		(:nombre, :apellidos, :email, :clave, :activo, :token, :tfno, :rol, :descrip)";

			$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

			$consulta = $conexion->prepare($insert);

			// Ejecuta el nuevo registro en la base de datos
			$consulta->execute([
				"nombre" => $nombre,
				"apellidos" => $apellidos,
				"email" => $emailNuevo,
				"clave" => "",
				"activo" => 0,
				"token" => $token,
				"tfno" => $tfno,
				"rol" => 2,
				"descrip" => $descripcion
			]);

			//crear carpeta usuario
			$usuario =  $conexion->lastInsertId();	

			mkdir("../uploads/usuarios/$usuario/", 0777, true);
			mkdir("../uploads/usuarios/$usuario/perfil", 0777, true);
			mkdir("../uploads/usuarios/$usuario/rallies", 0777, true);



			$consulta = null;
			$conexion = null;

            header("Location: listados.php");
  
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
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
</head>
<body>
	<header class="sticky-top bg-white shadow-sm">
		<nav class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
			<a href='listados.php'class="btn btn-dark">Volver</a>
		<?php if ($email != ""): ?>
			<div class="text-end">
				<span class="me-3 fw-bold">Bienvenido/a <?php echo $nombreAdmin ?></span>
				<a href="../ControlAcceso/cerrar-sesion.php"  class="btn btn-danger">Salir</a>
			</div>
		<?php endif; ?>
		</nav>
	</header>
    <main class="container">
    	<div class="min-vh-100 d-flex justify-content-center align-items-center">	
			<div class="card shadow-sm p-4 my-4">
				<h2 class="text-primary-emphasis">Alta de un nuevo usuario</h2>
					<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
					<div class="mb-3">
						<!-- Campo nombre nuevo -->
						<label for="nombre" class="form-label fw-bold">Nombre*</label>
						<input type="text" class="form-control" name="nombre" placeholder="nombre" value="<?php echo $nombre ?>" required>
						<?php
							if (isset($errores["nombre"])):
						?>
							<p class="text-danger small"><?php echo $errores["nombre"] ?></p>
						<?php
							endif;
						?>
					</div>
					<div class="mb-3">
						<!-- Campo apellidos -->
						<label for="apellidos" class="form-label fw-bold">Apellidos*</label>
						<input type="text" class="form-control" name="apellidos" placeholder="apellidos" value="<?php echo $apellidos ?>" required>
						<?php
							if (isset($errores["apellidos"])):
						?>
							<p class="text-danger small"><?php echo $errores["apellidos"] ?></p>
						<?php
							endif;
						?>
					</div>	
					<div class="mb-3">
						<!-- Campo email nuevo -->
						<label for="email" class="form-label fw-bold">Email*</label>
						<input type="email" class="form-control" name="email" placeholder="ejemplo@email.com" value="<?php echo $emailNuevo ?>" required>
						<?php
							if (isset($errores["email"])):
						?>
							<p class="text-danger small"><?php echo $errores["email"] ?></p>
						<?php
							endif;
						?>
					</div>  
					<div class="mb-3">
						<!-- Campo tfno -->
						<label for="tfno" class="form-label fw-bold">Teléfono</label>
						<input type="number" class="form-control" name="tfno" placeholder="6xxxxxxxx" value="<?php echo $tfno ?>" min="600000000" max="799999999">
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
						<label for="descr" class="form-label fw-bold">Descripción</label>
						<textarea class="form-control" name="descr" rows="5" cols="33" placeholder="Añade descripción de ti mismo ..." maxlength="600"><?php echo $descripcion ?></textarea>
					</div>
					<div class="d-grid">
						<!-- Botón submit -->
						<input type="submit" class="btn btn-dark mx-auto" value="Guardar">
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
	endif;
}
?>


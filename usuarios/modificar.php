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

	$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

	//consulta obtener usuario conectado
	$consulta = " SELECT id_usuario, nombre, rol_id FROM usuarios WHERE email = ?";

	$consulta = $conexion->prepare($consulta);			

	$consulta->bindParam(1, $email);

	$consulta->execute();
	
	$resultado = $consulta->fetch();

	// Guardo el rol y el id
	$perfil = (int) $resultado["rol_id"];

	$usuario = $resultado["id_usuario"];

	$nombre = $resultado["nombre"];

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

			if ($tfno != null && !validarEnteroLimites($tfno, 600000000, 799999999))
			{
				$errores["tfno"] = "Campo teléfono no es correcto.";
				$tfno = null;
			}

			// Si el teléfono está vacío, lo establecemos como null
			if (trim($tfno) === "") {
				$tfno = null;
			}
			
			
			// Comprobación de imagen subida en backend

			if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
				$directorioSubida = "uploads/usuarios/$usuario/perfil/";
				$nombreOriginal = basename($_FILES['imagen']['name']);
				$rutaFoto = $directorioSubida . $nombreOriginal;

				
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
	
	
  	//Si entramos por primera vez o hay errores, pintarlos en el correspondiente campo:
	if (!$comprobarConexion || count($errores) > 0):
		{ 

			//modificar clave
			$emailEncode = urlencode($email);
    		$tokenEncode = urlencode($token);
  	?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Perfil</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
</head>
<body>	
	<header class="sticky-top bg-white shadow-sm">
		<nav class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
			<a href='perfil.php'class="btn btn-dark">Volver</a>
			<div class="text-end">
				<span class="me-3 fw-bold">Bienvenido/a <?php echo $nombre ?></span>
				<a href="../ControlAcceso/cerrar-sesion.php"  class="btn btn-danger">Salir</a>
			</div>
		</nav>
	</header>
    <main class="container">		
		<div class="min-vh-100 d-flex justify-content-center align-items-center">	
			<div class="card shadow-sm p-4 my-4">
				<div class='text-center mb-4 d-flex align-items-center gap-3 mb-3 mt-2'>
					<h2 class='d-inline-block text-primary-emphasis'>Modificar Perfil</h2>
					<a href='../ControlAcceso/establecerUsuario.php?email=<?php echo $emailEncode?>&token=<?php echo $tokenEncode?>' class="btn btn-outline-danger">Cambiar Clave</a>
				</div>
				<form id="formulario" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
					<input type="hidden" name="id" value="<?php echo $usuario ?>">
					<input type="hidden" name="token" value="<?php echo $token ?>">
					<input type="hidden" name="imagen_actual" value="<?php echo $rutaFoto ?>">
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
						<!-- Imagen de perfil -->
						<label for="imagen" class="form-label fw-bold">Imagen de perfil</label>
						<input type="file" id="imagen" name="imagen" accept="image/*">
						<?php
							if (isset($errores["imagen"])):
								{	
						?>
							<p class="text-danger small"><?php echo $errores["imagen"] ?></p>
						<?php
								}
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
<script>
	//control de imagen en cliente
	document.getElementById("formulario").addEventListener("submit", (event) => {
		const input = document.getElementById("imagen");
		const file = input.files[0];

		if (file) {
			const tamMax = 2; // Límite de 2 MB
			const formatos = ['image/jpeg', 'image/png', 'image/gif', 'image/svg'];

			if (!formatos.includes(file.type)) {
				alert("Por favor, selecciona un archivo de imagen válido (jpg, png, gif, svg).");
				event.preventDefault();
			}

			if (file.size > tamMax * 1024 * 1024) {
				alert("El archivo es demasiado grande. Debe pesar menos de " + tamMax + " MB.");
				event.preventDefault();
			}
		}
	});
</script>
</html>
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
}   
//cierre del if inicial
?>
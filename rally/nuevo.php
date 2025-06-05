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
	$nombre = $resultado["nombre"];


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
    	$titulo = "";
    	$descripcion = "";
		$participantes = 0;
		$fecha_ini = "";
		$fecha_fin = "";
    	$lim_fotos = 0;
		$tam_foto = 0;
		$formato_foto = "";
		$localidad = "";

		//si entramos por el formulario
    	if ($_SERVER["REQUEST_METHOD"]=="POST")
    	{
		    
		$conexionRealizada = true;
		    
		 // Obtenemos los diferentes campos del formulario a partir de la función "obtenerValorCampo"
		 $titulo = obtenerValorCampo("titulo");
		 $descripcion = obtenerValorCampo("descripcion");
		 $fecha_ini = obtenerValorCampo("fecha_ini");
		 $fecha_fin = obtenerValorCampo("fecha_fin");
		 $participantes = obtenerValorCampo("participantes");
		 $lim_fotos = obtenerValorCampo("lim_fotos");
		 $tam_foto = obtenerValorCampo("tam_foto");
		 $formato_foto = obtenerValorCampo("formato_foto");
		 $localidad = obtenerValorCampo("localidad");
		    
	    	//-----------------------------------------------------
	        // Validaciones
	        //-----------------------------------------------------
	 
	        // Titulo debe rellenarse
	        if ($titulo == "")
	        {
	            $errores["titulo"] = "Campo titulo no puede quedar vacío";
				$titulo = ""; 
	        }else 
			{
				// Comprobar que no exita otro rally con el mismo nombre.
				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
				$consulta = "SELECT * FROM rally WHERE LOWER(titulo) = LOWER('$titulo')";

				$consulta = $conexion->query($consulta);
				
				// comprobamos si, al ejecutar la consulta, tenemos más de 0 registro. En tal caso, generar el mensaje de error.
				if ($consulta->rowCount() > 0)
				{
					//Msj Error
					$errores["titulo"] = "El título del rally ya existe";
					$nombre = "";
				}

				$consulta = null;
				$conexion = null;					
			}
			
			if ($descripcion == "")
	        {
	            $errores["descripcion"] = "Campo descripción no puede quedar vacío";
				$descripcion = ""; 
	        }

			if ($localidad == "")
	        {
	            $errores["localidad"] = "Campo localidad no puede quedar vacío";
				$localidad = ""; 
	        }

			if ($formato_foto == "")
	        {
	            $errores["formato_foto"] = "Debe seleccionarse un tipo de formato de imagen";
				$formato_foto = ""; 
	        }

			// Campos numéricos deben ser enteros positivos 
	        if (!validarEnteroPositivo($participantes))
	        {
	            $errores["participantes"] = "Número participantes debe ser un número entero positivo";
				$participantes = ""; 
	        }

	        if (!validarEnteroPositivo($lim_fotos))
	        {
				$errores["lim_fotos"] = "Número de fotos debe ser un número entero positivo";
				$lim_fotos = ""; 
	        }

			if (!validarEnteroPositivo($tam_foto))
	        {
				$errores["tam_foto"] = "Tamaño de la foto debe ser un número entero positivo";
				$tam_foto = ""; 
	        }


			// Fecha debe tener el formato adecuado y posterior a la fecha de creación 
	        if (!validarDosFechas($fecha_ini, $fecha_fin))
	        {
				$errores["fechas"] = "Fechas no cumplen los requisitos establecidos";
				$fecha_ini = ""; 
				$fecha_fin = ""; 
	        }

			// Comprobación de imagen subida
			$imagenSubida = false;			

			if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {

				// Validar tipo y tamaño
				$tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/svg'];
				if (!in_array($_FILES['imagen']['type'], $tiposPermitidos)) {
					$errores['imagen'] = "Solo se permiten imágenes JPEG, PNG, SVG o GIF.";
				} else {
					$imagenSubida = true;
				}
			}



    	}

  		// Si no hay errores y hemos entrado por el formulario, conectar a la BBDD:
		if ( $conexionRealizada && count($errores) == 0):

			$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
  			
			// consulta a ejecutar (insert)

			$consulta = $conexion->prepare("INSERT INTO rally (titulo, descripcion, fecha_ini, fecha_fin, participantes, lim_fotos, tam_foto, formato_foto, localidad) 
											values (:titulo, :descripcion, :fecha_ini, :fecha_fin, :participantes, :lim_fotos, :tam_fotos, :formato, :localidad)");

			// preparar la consulta (usar bindParam)

			$consulta->bindParam(':titulo', $titulo);
			$consulta->bindParam(':descripcion', $descripcion);
			$consulta->bindParam(':fecha_ini', $fecha_ini);
			$consulta->bindParam(':fecha_fin', $fecha_fin);
			$consulta->bindParam(':participantes', $participantes);
			$consulta->bindParam(':lim_fotos', $lim_fotos);
			$consulta->bindParam(':tam_fotos',  $tam_foto);
			$consulta->bindParam(':formato',  $formato_foto);
			$consulta->bindParam(':localidad',  $localidad);
			
			// ejecutar la consulta y captura de la excepcion

			try {
				$consulta->execute();

				//obtener id del rally recién insertado
				$rally =  $conexion->lastInsertId();				

				//creación de directorio para imágenes de rally
				$directorio = "../uploads/rallies/$rally/";

				if (!is_dir($directorio)) {
					mkdir($directorio);
				}

				//si imagen ha sido subida, cargamos al sevidor y actualizamos registro
				if ($imagenSubida) {

					$directorioSubida = "uploads/rallies/$rally/";
					$nombreOriginal = basename($_FILES['imagen']['name']);
					$rutaFinal = $directorioSubida . $nombreOriginal;

					move_uploaded_file($_FILES['imagen']['tmp_name'], "../$rutaFinal");


					$consulta = $conexion->prepare("UPDATE rally SET img= :urlFoto WHERE id_rally = :rally");

					// preparar la consulta (usar bindParam)

					$consulta->bindParam(':urlFoto', $rutaFinal);

					$consulta->bindParam(':rally', $rally);

					$consulta->execute();
				}

				$consulta = null;
        		$conexion = null;				

			} catch (PDOException $e) {
				exit($e->getMessage());
				
			}
			
        	// redireccionamos a la vista administrador
  			header("Location: ../administrador/listados.php");
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
    <title>Nuevo rally</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
</head>
<body>
	<header class="sticky-top bg-white shadow-sm">
		<nav class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
			<a href='../administrador/listados.php'class="btn btn-dark">Volver</a>
		<?php if ($email != ""): ?>
			<div class="text-end">
				<span class="me-3 fw-bold">Bienvenido/a <?php echo $nombre ?></span>
				<a href="../ControlAcceso/cerrar-sesion.php"  class="btn btn-danger">Salir</a>
			</div>
		<?php endif; ?>
		</nav>
	</header>
    <main class="container">
		<div class="min-vh-100 d-flex justify-content-center align-items-center">	
			<div class="card shadow-sm p-4 my-4">
				<h2 class="text-primary-emphasis mb-3">Nuevo Rally</h2>
				<form id="formulario" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
					<div class="mb-3">
						<!-- Campo fechas -->
						<div class="row gap-3">
							<div class="col-12 col-md-5">					
								<label for="fecha_ini" class="form-label fw-bold">Fecha inicio*</label>	 
								<input type="date" name="fecha_ini" class="form-control" value="<?php echo $fecha_ini ?>" required>
							</div>
							<div class="col-12 col-md-5">					
								<label for="fecha_fin" class="form-label fw-bold">Fecha final*</label>	 
								<input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin ?>" required>
							</div>							 
						 </div>						
						<?php
							if (isset($errores["fechas"])):
						?>
							<p class="text-danger small"><?php echo $errores["fechas"]?></p>
						<?php
							endif;
						?>		
					</div>
					<div class="mb-3">
						<!-- Campo nombre -->
						<label for="titulo" class="form-label fw-bold">Título*</label>
						<input type="text"  class="form-control" name="titulo" placeholder="Título" value="<?php echo $titulo ?>" required>
						<?php
							if (isset($errores["titulo"])):
						?>
							<p class="text-danger small"><?php echo $errores["titulo"] ?></p>
						<?php
							endif;
						?>
					</div>
					<div class="mb-3">
						<!-- Campo localidad -->
						<label for="localidad" class="form-label fw-bold">Localidad*</label>
						<input type="text"  class="form-control" name="localidad" placeholder="Localidad" value="<?php echo $localidad ?>" required>
						<?php
							if (isset($errores["localidad"])):
						?>
							<p class="text-danger small"><?php echo $errores["localidad"] ?></p>
						<?php
							endif;
						?>
					</div>
					<div class="mb-3">
						<!-- Campo descripción -->
						<label for="descripcion" class="form-label fw-bold">Descripción*</label>
						<textarea name="descripcion"  class="form-control" rows="10" cols="50" placeholder="Descripcion" maxlength="600" required><?php echo $descripcion ?></textarea>
					</div>
					<div class="mb-3">
						<!-- número de participantes -->
						<label for="participantes" class="form-label fw-bold">Nº participantes*</label>
						<input type="number"  class="form-control" name="participantes" value="<?php echo $participantes ?>" min="1" max="1000" required>
						<?php
							if (isset($errores["participantes"])):
						?>
							<p class="text-danger small"><?php echo $errores["participantes"] ?></p>
						<?php
							endif;
						?>
					</div>
					<div class="mb-3">
						<!-- número de fotos -->
						<label for="lim_fotos" class="form-label fw-bold">Límite fotos por participante*</label>
						<input type="number"  class="form-control" name="lim_fotos" value="<?php echo $lim_fotos ?>" min="1" max="100" required>
						<?php
							if (isset($errores["lim_fotos"])):
						?>
							<p class="text-danger small"><?php echo $errores["lim_fotos"] ?></p>
						<?php
							endif;
						?>
					</div>
					<div class="mb-3">
						<!-- tamaño de fotos -->
						<label for="tam_foto" class="form-label fw-bold">Tamaño foto permitido*</label>
						<input type="number"  class="form-control" name="tam_foto" value="<?php echo $tam_foto ?>" min="1" max="20000" required>
						<?php
							if (isset($errores["tam_foto"])):
						?>
							<p class="text-danger small"><?php echo $errores["tam_foto"] ?></p>
						<?php
							endif;
						?>
					</div>
					<div class="mb-3">
						<!-- Campo formato imagen -->
						<select id="formato" class="form-select fw-bold" name="formato_foto" required >
							<option value="">Seleccione Formato Foto*</option>
							<option value="png" <?php echo "png" == $formato_foto ? "selected" : "" ?>>PNG</option>
							<option value="jpeg" <?php echo "jpeg" == $formato_foto ? "selected" : "" ?>>JPEG</option>
							<option value="svg" <?php echo "svg" == $formato_foto ? "selected" : "" ?>>SVG</option>
							<option value="gif" <?php echo "gif" == $formato_foto ? "selected" : "" ?>>GIF</option>
						</select>
						
						<?php
							if (isset($errores["formato_foto"])):
						?>
							<p class="text-danger small"><?php echo $errores["formato_foto"] ?></p>
						<?php
							endif;
						?>
					</div>
					<div class="mb-3">
						<!-- Subida de imagen -->
						<label for="imagen" class="form-label fw-bold">Imagen del rally:</label>
						<input type="file" id="imagen"  class="form-control" name="imagen" accept="image/*">
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
						<input type="submit"  class="btn btn-dark mx-auto" value="Registrar">
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
			const tamMax = 10; // Límite de 10 MB
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
		endif;
		?>
		
<?php
    }   
        //cierre del if inicial
    ?>
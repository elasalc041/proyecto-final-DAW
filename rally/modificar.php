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

    $consulta = "SELECT * FROM usuarios WHERE email = :email;";

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

	// crea las variables para la comprobación de los datos y conectamos con la BBDD para obtener y pintar los datos de la id que acabamos de enviar a la página
	$comprobarConexion = false;
	$errores = [];
	
	//si entramos por el formulario o por el enlace de modificar
	if (count($_REQUEST) > 0) 
	{
		//si entramos por enlace para modificar
		if (isset($_GET["rally"])) 
		{
			$rally = $_GET["rally"];

			//Conectamos a la BBDD para obtener datos del rally

			$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
		
			// Montamos la consulta a ejecutar

			$consulta = "SELECT * FROM rally WHERE id_rally = ?";

			$consulta = $conexion->prepare($consulta);

			$consulta->bindParam(1, $rally);

			$consulta->execute();

			// comprobamos si hay algún registro 
			if ($consulta->rowCount() == 0)
			{
				//Si no lo hay, desconectamos y volvemos al listado 
				$consulta = null;
				$conexion = null;
				header("Location: ../administrador/listados.php");
			}
			else 
			{
				// Si hay algún registro, Obtenemos el resultado (usamos fetch())
					
				$registro = $consulta->fetch();
				$titulo =  $registro["titulo"];
				$descripcion = $registro["descripcion"];
				$participantes = $registro["participantes"];
				$fecha_ini = $registro["fecha_ini"];
				$fecha_fin = $registro["fecha_fin"];
				$lim_fotos = $registro["lim_fotos"];
				$tam_foto = $registro["tam_foto"];
				$formato_foto = $registro["formato_foto"];
				$localidad = $registro["localidad"];
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
			
			$rally = obtenerValorCampo("id"); 			   
			$titulo = obtenerValorCampo("titulo");   
			$descripcion = obtenerValorCampo("descripcion");
			$participantes = obtenerValorCampo("participantes");
			$fecha_ini = obtenerValorCampo("fecha_ini");
			$fecha_fin = obtenerValorCampo("fecha_fin");
			$lim_fotos = obtenerValorCampo("lim_fotos");
			$tam_foto = obtenerValorCampo("tam_foto");
			$formato_foto = obtenerValorCampo("formato_foto");
			$localidad = obtenerValorCampo("localidad");
			$rutaFoto = obtenerValorCampo("imagen_actual");
		 

			 //-----------------------------------------------------
			// Validaciones
			//-----------------------------------------------------
			// Comprueba que el id proveniente del formulario se corresponde con uno que tengamos 
			//conectamos a la bbdd

			$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

			$consulta = "SELECT * FROM rally WHERE id_rally = ?";

			$consulta = $conexion->prepare($consulta);

			$consulta->bindParam(1, $rally);

			$consulta->execute();

			// comprobamos si algún registro 
			if ($consulta->rowCount() == 0)
			{
				//Si no lo hay, desconectamos y volvemos al listado original
				$consulta = null;
				$conexion = null;
				header("Location: ../administrador/listados.php");
			}
			
			// Titulo debe rellenarse
	        if ($titulo == "")
	        {
	            $errores["titulo"] = "Campo titulo no puede quedar vacío";
				$titulo = ""; 
	        }else 
			{
				// Comprobar que no exita otro rally con el mismo nombre.
				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
				$consulta = "SELECT * FROM rally WHERE LOWER(titulo) = LOWER('$titulo') AND id_rally != $rally";

				$consulta = $conexion->query($consulta);
				
				// comprobamos si, al ejecutar la consulta, tenemos más de 0 registro. En tal caso, generar el mensaje de error.
				if ($consulta->rowCount() > 0)
				{
					//Msj Error
					$errores["nombreRepetido"] = "El título del rally ya existe";
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

			if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
				$directorioSubida = "uploads/rallies/$rally/";
				$nombreOriginal = basename($_FILES['imagen']['name']);
				$rutaFoto = $directorioSubida . $nombreOriginal;

				// Validar tipo 
				$tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/svg'];
				if (!in_array($_FILES['imagen']['type'], $tiposPermitidos)) {
					$errores['imagen'] = "Solo se permiten imágenes JPEG, PNG, SVG o GIF.";
				} else {
					move_uploaded_file($_FILES['imagen']['tmp_name'], "../$rutaFoto");
				}
			}

		}	

		if ($comprobarConexion && count($errores) == 0){
			//Nos conectamos a la BBDD
			$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
		
			// Creamos una variable con la consulta "UPDATE" a ejecutar

			$consulta = "UPDATE rally SET titulo= :titulo, descripcion= :descripcion, localidad= :localidad, fecha_ini= :fechaInicial, fecha_fin= :fechaFin,
						formato_foto= :formato, participantes= :participantes, lim_fotos= :lim_fotos, tam_foto= :tam_foto, img= :foto
								WHERE id_rally = :rally";
			
			// preparamos la consulta (bindParam)

			$resultado = $conexion->prepare($consulta);

			$resultado->bindParam(":titulo", $titulo);			
			$resultado->bindParam(":localidad", $localidad);
			$resultado->bindParam(":descripcion", $descripcion);
			$resultado->bindParam(":formato", $formato_foto);
			$resultado->bindParam(":participantes", $participantes);
			$resultado->bindParam(":lim_fotos", $lim_fotos);
			$resultado->bindParam(":tam_foto", $tam_foto);
			$resultado->bindParam(":foto", $rutaFoto);
			$resultado->bindParam(":fechaInicial", $fecha_ini);
			$resultado->bindParam(":fechaFin", $fecha_fin);
			$resultado->bindParam(":rally", $rally);

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
				header("Location: ../administrador/listados.php");
				
		}
		else
		{			
	?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Modificar Rally</title>
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
				<h2 class="text-primary-emphasis mb-3">Modificar Rally</h2>
				<form id="formulario" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
					<input type="hidden" name="id" value="<?php echo $rally ?>">
					<input type="hidden" name="imagen_actual" value="<?php echo $rutaFoto ?>">
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
						<input type="text" class="form-control" name="titulo" placeholder="Título" value="<?php echo $titulo ?>" required>
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
						<input type="text" class="form-control" name="localidad" placeholder="Localidad" value="<?php echo $localidad ?>" required>
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
						<textarea name="descripcion" class="form-control" rows="10" cols="50" placeholder="Descripcion" maxlength="600" required><?php echo $descripcion ?></textarea>
					</div>				
					<div class="mb-3">
						<!-- número de participantes -->
						<label for="participantes" class="form-label fw-bold">Nº participantes*</label>
						<input type="number" class="form-control" name="participantes" value="<?php echo $participantes ?>" min="1" max="1000" required>
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
						<input type="number" class="form-control" name="lim_fotos" value="<?php echo $lim_fotos ?>" min="1" max="100" required>
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
						<input type="number" class="form-control" name="tam_foto" value="<?php echo $tam_foto ?>" min="1" max="2000" required>
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
						<select id="formato" name="formato_foto" class="form-select fw-bold" required >
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
						<input type="file" id="imagen" class="form-control" name="imagen" accept="image/*">
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
						<input type="submit"  class="btn btn-dark mx-auto" value="Modificar">
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
			} //cierre else errores
		
    }    //cierre if request
}	//cierre del if inicial
?>
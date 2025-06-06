<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();

//si entramos con el email, se ha creado la sesion si es correcto y guardamos el email para identificar
$email = "";
$perfil = 0;
$nombre = "";
$rallies = [];
if (isset($_SESSION["email"])){
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

	// consulta para obtener rallies donde usuario está registrado

	$consulta2 = "SELECT * FROM inscripciones WHERE usuario_id = :usuarioId";

	$consulta2 = $conexion->prepare($consulta2);

	$consulta2->execute([
		"usuarioId" => $usuario,
	]);       
			   
	while ($registro = $consulta2->fetch(PDO::FETCH_ASSOC)) {
		$rallies[] = $registro["rally_id"]; //guardar los rallies solicitados en un array
	}

	$consulta = null;
	$consulta2 = null;
	$conexion = null;
}


//Si se ha entrado por GET o POST
if (count($_REQUEST) > 0)
{

	//si ha venido a través del enlace con la id del rally
	if (!isset($_GET["rally"]))
	{
		//Evitar que se pueda entrar directamente a la página
		header("Location: ../index.php");
		exit();
	} 	
	else 
	{
		$rally = $_GET["rally"];

		$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

		$select = "SELECT r.*, count(i.usuario_id) as registrados FROM rally r
                LEFT JOIN inscripciones i ON r.id_rally = i.rally_id WHERE r.id_rally = :id GROUP BY r.id_rally";

		$consulta = $conexion->prepare($select);

		$consulta->bindParam(":id", $rally);

		$consulta->execute();

		// comprobamos si algún registro 
		if ($consulta->rowCount() == 0)
		{
			//Si no lo hay, desconectamos y volvemos al index
			$consulta = null;
			$conexion = null;
			header("Location: ../index.php");
		}else{
			$registro = $consulta->fetch();
		}
		
	?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rally</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
</head>
<body>	
	<header class="sticky-top bg-white shadow-sm">
		<nav class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
			<a href='../index.php'class="btn btn-dark">Inicio</a>
		<?php if ($email != ""): ?>
			<div class="text-end">
				<span class="me-3 fw-bold">Bienvenido/a <?php echo $nombre ?></span>
				<a href="../ControlAcceso/cerrar-sesion.php"  class="btn btn-danger">Salir</a>
			</div>
		<?php endif; ?>
		</nav>
	</header>	
	<main class="container">
		<section class="mb-4">	
			<div class='d-flex align-items-center gap-3 mb-3 mt-2'>
				<h1 class='d-inline-block text-primary-emphasis'><?php echo $registro["titulo"] ?></h1>		
				<?php
				//comprobar si es usuario registrado
					if ($email != "") {
						//comprobar si está inscrito al rally
						if (in_array($rally, $rallies)) {
							echo "<a href='../usuarios/borrarse.php?id=$rally' class='btn btn-warning'>Borrarse</a>" . PHP_EOL;
						}else{
							//comprobar límite de inscripciones
							if ($registro["registrados"] < $registro["participantes"]) {						
								echo "<a href='../usuarios/inscribirse.php?id=$rally' class='btn btn-primary'>Inscribirse</a>" . PHP_EOL;
							}else{
								echo "<div class='text-danger fw-bold'>Completo</div> ". PHP_EOL;
							}
						}
					}
				?>
			</div>
			<div>
			<?php if ($registro["img"] != null && $registro["img"] != ""): ?>				
				<img class=" img-rally border border-dark" src="../<?php echo $registro['img']?>" alt='Foto del rally'></img>
			<?php else: ?>
				<img class=' img-rally border border-dark' src='' alt='Foto del rally no disponible'></img>
			<?php endif; ?>
			</div>		
		</section>	
		<section class="container my-5">
			<div class="card shadow-sm border-0">
				<div class="card-body">
					<div class='d-flex align-items-center gap-3 mb-3 mt-2'> 
						<h2 class="card-title mb-3 d-inline-block">Detalles del Rally</h2>
						<a href="ranking.php?rally=<?php echo $rally ?>" class="btn btn-outline-primary">Ver Ranking</a>
					</div>
					<div class="row">
						<div class="col-md-7">
							<p class="mb-1">
								<strong>Fecha de inicio</strong> <?php echo formatoFecha($registro["fecha_ini"]) ?>
							</p>
							<p class="mb-1">
								<strong>Fecha de fin</strong> <?php echo formatoFecha($registro["fecha_fin"]) ?>
							</p>
							<p class="mb-3">
								<strong>Localidad</strong> <?php echo $registro["localidad"] ?>
							</p>

							<p><?php echo $registro["descripcion"] ?></p>
						</div>
						<div class="col-md-5 mt-4 mt-md-0">
						<h5>Requisitos</h5>
							<ul class="list-unstyled ps-3 mb-4">
								<li><strong>Límite de participantes</strong> <?php echo $registro["participantes"] ?></li>
								<li><strong>Participantes registrados</strong> <?php echo $registro["registrados"] ?></li>
								<li><strong>Límite de fotos por usuario</strong> <?php echo $registro["lim_fotos"] ?></li>
								<li><strong>Tamaño máximo por foto</strong> <span id="tamMax"><?php echo $registro["tam_foto"] ?></span> MB</li>
								<li><strong>Formato aceptado</strong> <span id="formato"><?php echo $registro["formato_foto"] ?></span></li>
							</ul>
						</div>						
					</div>
				</div>
			</div>
		</section>
		<section class="container my-4">			
			<?php
				//fotos del usuario registrado
				if (in_array($rally, $rallies)) {
					$select = "SELECT * FROM fotos WHERE rally_id = :rally AND usuario_id = :usuario ORDER BY fecha desc";

					$consulta = $conexion->prepare($select);

					$consulta->bindParam(":rally", $rally);
					$consulta->bindParam(":usuario", $usuario);

					$consulta->execute();
					
					echo "<div class='d-flex align-items-center gap-3 mt-2'>" . PHP_EOL;
					echo "<h3 class='mb-4 d-inline-block'>Tus Fotos</h3>" . PHP_EOL;

					//comprobar número de fotos subidas por usuario y fecha 
					if ($consulta->rowCount() < $registro["lim_fotos"] && $fechaActual >= $registro["fecha_ini"] && $fechaActual <= $registro["fecha_fin"])
					{
						echo "<button class='btn btn-outline-success' onclick='subirImagen()'>Subir nueva foto</button>" . PHP_EOL;
					}
					echo "</div>" . PHP_EOL;

					// comprobamos si hay algún registro 
					if ($consulta->rowCount() == 0)
					{
						echo "<h5>No dispones de ninguna foto aún</h5>" . PHP_EOL;
					}
					else
					{
						echo "<div class='row g-4'>" . PHP_EOL;
						while ($resultado = $consulta->fetch(PDO::FETCH_ASSOC)) {
							echo "<div class='col-lg-4 col-md-6 col-12'>" . PHP_EOL;
								echo "<div class='card h-100 border'>" . PHP_EOL;
									echo "<img src='../$resultado[url]' class='card-img-top img-card' alt='Foto $resultado[id_foto]'  onclick='mostrarImagen(\"../$resultado[url]\")'>" . PHP_EOL;
									echo "<div class='card-body d-flex flex-column'>" . PHP_EOL;
										echo "<div class='mt-auto d-flex justify-content-between'>" . PHP_EOL;
											echo "<p class='card-text'><strong>Estado</strong> $resultado[estado]</p>" . PHP_EOL;
											echo "<p class='card-text'><strong>Votos</strong> $resultado[puntos]</p>" . PHP_EOL;
										echo "</div>" . PHP_EOL;
									//si la foto no ha sido aceptada puede ser eliminada
									if ($resultado["estado"] !== "aceptada") {
										echo "<a onclick='confirmarBorrado(\"borrarFoto.php?id=$resultado[id_foto]&rally=$rally\")' class='btn btn-danger mx-auto'>Eliminar</a>" . PHP_EOL;
									}	
									echo "</div>". PHP_EOL;						
								echo "</div>". PHP_EOL;
							echo "</div>" . PHP_EOL;	
						} 
						echo "</div>" . PHP_EOL;	
					}

				}
			?>
		</section>
		<section class="container my-5">
			<?php
			echo "<h3 class='mb-4'>Fotos subidas por participantes</h3>" . PHP_EOL;
			//fotos generales del rally
			$select = "SELECT f.*, u.nombre, u.apellidos, r.fecha_ini, r.fecha_fin 
			FROM usuarios u JOIN fotos f
			ON f.usuario_id = u.id_usuario
			JOIN rally r
			ON f.rally_id = r.id_rally
			WHERE f.rally_id = :id AND f.estado = 'aceptada' ORDER BY f.fecha desc";

			$consulta = $conexion->prepare($select);

			$consulta->bindParam(":id", $rally);

			$consulta->execute();

			// comprobamos si algún registro 
			if ($consulta->rowCount() == 0)
			{
				echo "<h5>No hay imágenes disponibles en estos momentos</h5>" . PHP_EOL;
			}else{
				echo "<div class='row g-4'>" . PHP_EOL;
				while ($resultado = $consulta->fetch(PDO::FETCH_ASSOC)) {
					//comprobar estado foto 
					if ($resultado["estado"] == "aceptada") {
						echo "<div class='col-lg-4 col-md-6 col-12'>" . PHP_EOL;						
							echo "<div class='card h-100 border'>" . PHP_EOL;
								echo "<img src='../$resultado[url]' class='card-img-top img-card' alt='Foto $resultado[id_foto]' onclick='mostrarImagen(\"../$resultado[url]\")''></img>" . PHP_EOL;								
								echo "<div class='card-body  d-flex flex-column'>" . PHP_EOL;
									echo "<div class='mt-auto d-flex justify-content-between'>" . PHP_EOL;
										echo "<p class='card-text'><strong>Autor</strong> $resultado[nombre] $resultado[apellidos]</p>" . PHP_EOL;
										echo "<p class='card-text'><strong>Votos</strong> $resultado[puntos]</p>" . PHP_EOL;
									echo "</div>" . PHP_EOL;

									if ($fechaActual >= $resultado["fecha_ini"] && $fechaActual <= $resultado["fecha_fin"]) {								
										echo "
										<form class='mx-auto formVotar' action='votar.php' method='POST'>
											<input type='hidden' name='id_foto' value='$resultado[id_foto]'>
											<input type='hidden' name='rally_id' value='$resultado[rally_id]'>
											<button class='btn btn-success' type='submit'>Votar</button>
										</form>
										" . PHP_EOL;
									}
								echo "</div>". PHP_EOL;
							echo "</div>". PHP_EOL;
						echo "</div>" . PHP_EOL;	
					}
				}
				echo "</div>" . PHP_EOL; 					
			}
			
			?>

		</section>

		<!-- El modal de subir imagen -->
		<div class="modal fade" id="modalSubirImg" tabindex="-1">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">				
					<div class="modal-header">
						<h5 class="modal-title">Subir Foto</h5>
						<button class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
					</div>					
					<div class="modal-body">
						<form id="formulario" action="subirFoto.php" method="POST" enctype="multipart/form-data">
						<input type="hidden" name="rally" value="<?php echo $rally ?>">
						<div class="mb-3">
							<label for="imagen" class="form-label">Selecciona una foto:</label>
							<input class="form-control" type="file" name="imagen" id="imagen" accept="image/*" required>

							<?php if (isset($_GET["error"])): ?>
								<p class='text-danger ms-3'><?php echo $_GET["error"] ?></p>
							<?php endif;  ?>

						</div>
						<div class="text-end">
							<button type="submit" class="btn btn-primary">Subir Foto</button>
						</div>
						</form>
					</div>
				</div>
			</div>
		</div>


		<!-- Modal para imagen ampliada -->
		<div class="modal fade" id="modalImagen" tabindex="-1">
			<div class="modal-dialog modal-dialog-centered modal-lg">
				<div class="modal-content bg-transparent border-0">
				<div class="modal-body d-flex justify-content-center align-items-center p-0">
					<img id="imagenAmpliada" src=""  alt="Imagen ampliada">
				</div>
				</div>
			</div>
		</div>

	</main>
	<?php
            // Libera el resultado y cierra la conexión
    
        $consulta = null;
        $conexion = null;

        include '../utiles/footer.php';

		if (isset($_GET["error"])) {
			echo "<script>
			document.addEventListener('DOMContentLoaded', function() {
				var modal = new bootstrap.Modal(document.getElementById('modalSubirImg'));
				modal.show();
			});
			</script>" . PHP_EOL;
		}		
    ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
<script>
	//funcion abre modal imagen
	function mostrarImagen(src) {
		const modal = new bootstrap.Modal(document.getElementById('modalImagen'));
		document.getElementById('imagenAmpliada').src = src;
		modal.show();
	}

		//funcion abre modal subirFoto
	function subirImagen() {
		const modal = new bootstrap.Modal(document.getElementById('modalSubirImg'));
		modal.show();
	}

	//confirmar
	function confirmarBorrado(url) {
		if (confirm("¿Estás seguro de que deseas eliminar esta foto?")) {
			// Si el usuario hace clic en "Aceptar", redirige a la URL de eliminación
			window.location.href = url;
		}
	}

	//implementa votacion controlada en localstorage
	document.querySelectorAll(".formVotar").forEach(formulario => {
		formulario.addEventListener("submit", function (event) {
			event.preventDefault();
			const idFoto = this.id_foto.value;
			const rallyId = this.rally_id.value;
			const rally = `rally_${rallyId}`;

			let votos = JSON.parse(localStorage.getItem(rally)) || [];

			//valida no repetir foto
			if (votos.includes(idFoto)) {
				alert("Ya votaste esta foto.");
				return;
			}

			//valida maximo 3 fotos
			if (votos.length >= 3) {
				alert("Solo puedes votar 3 fotos por rally.");
				return;
			}

			votos.push(idFoto);
			localStorage.setItem(rally, JSON.stringify(votos));

			// enviar el formulario 
			this.submit();		
		});
	});


	//control de imagen en cliente
	document.getElementById("formulario").addEventListener("submit", (event) => {
		const input = document.getElementById("imagen");
		const file = input.files[0];
		
		const tamMaxHtml = document.getElementById("tamMax");
		const formatoHtml = document.getElementById("formato");

		if (file) {

			if (!file.type.includes(formatoHtml.textContent)) {
				alert(`Por favor, selecciona un archivo de imagen aceptado (${formatoHtml.textContent}).`);
				event.preventDefault();
			}

			if (file.size > tamMaxHtml.textContent * 1024 * 1024) {
				alert("El archivo es demasiado grande. Debe pesar menos de " + tamMaxHtml.textContent + " MB.");
				event.preventDefault();
			}
		}
	});


</script>
</html>

<?php
	}
} //cierre del if
?>
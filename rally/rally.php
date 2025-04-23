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
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
</head>
<body>	
	<header>
        <nav>
            <a href='../index.php' class='estilo_enlace'><button>Volver</button></a>
            <a href="../ControlAcceso/cerrar-sesion.php" class='estilo_enlace'><button>Salir</button></a>
        </nav>
	</header>
	<main class="contenido">		
		<?php
		echo "<h1>$registro[titulo]</h1>" . PHP_EOL;
		//comprobar si es usuario registrado
			if ($email != "") {
				//comprobar si está inscrito al rally
				if (in_array($rally, $rallies)) {
					echo "<a href='../usuarios/borrarse.php?id=$rally' class='estilo_enlace'><button>Borrarse</button></a>" . PHP_EOL;
				}else{
					//comprobar límite de inscripciones
					if ($registro["registrados"] < $registro["participantes"]) {						
						echo "<a href='../usuarios/inscribirse.php?id=$rally' class='estilo_enlace'><button>Inscribirse</button></a>" . PHP_EOL;
					}else{
						echo "<div class='completo'>Completo</div> ". PHP_EOL;
					}
				}
			}
			echo "<div class='img-rally'> " . PHP_EOL;
			if ($registro["img"] != null) {				
				echo "<img src='../$registro[img]' alt='Foto del rally'></img>" . PHP_EOL;
			}else{
				echo "<img src='' alt='Foto del rally no disponible'></img>" . PHP_EOL;
			}
			echo "</div> " . PHP_EOL;
		?>
		<section>
			<?php			
		//datos del rally
			echo "
			<p>Fecha de inicio: " . formatoFecha($registro["fecha_ini"]) . ". Fecha fin del concurso: " . formatoFecha($registro["fecha_fin"]) . "
			- $registro[localidad].</p> 
			<p>$registro[descripcion]</p>" . PHP_EOL;
			
			echo "<a href='ranking.php?id=$rally' class='estilo_enlace'><button>Ranking</button></a>". PHP_EOL;
			?>
			<div class="requisitos">
				<h5>Requisitos:</h5>
				<ul>
				<?php
					echo "
					<li>Límite de participantes: $registro[participantes]</li>
					<li>Participantes registrados: $registro[registrados]</li>
					<li>Límite de fotos por usuario: $registro[lim_fotos]</li>
					<li>Tamaño de foto máximo: $registro[tam_foto]MB</li>
					<li>Formato de imagen aceptada: $registro[formato_foto]</li>
					" . PHP_EOL;
				?>
				</ul>
			</div>
		</section>
		<section class="fotos">
			<?php
				//fotos del usuario registrado
				if (in_array($rally, $rallies)) {
					$select = "SELECT * FROM fotos WHERE rally_id = :rally AND usuario_id = :usuario ORDER BY fecha desc";

					$consulta = $conexion->prepare($select);

					$consulta->bindParam(":rally", $rally);
					$consulta->bindParam(":usuario", $usuario);

					$consulta->execute();
					
					echo "<h1>Fotos subidas</h1>" . PHP_EOL;

					//comprobar número de fotos subidas por usuario y fecha 
					if ($consulta->rowCount() < $registro["lim_fotos"] && $fechaActual >= $registro["fecha_ini"] && $fechaActual <= $registro["fecha_fin"])
					{
						echo "<div>" . PHP_EOL;
						echo "<button id='abrirModal'>Subir nueva foto</button>" . PHP_EOL;
						if (isset($_GET["error"])){
							echo "<span  class='error'>$_GET[error]</span>". PHP_EOL;
						}
						echo "</div>" . PHP_EOL;
					}

					// comprobamos si algún registro 
					if ($consulta->rowCount() == 0)
					{
						echo "<h3>No dispones de ninguna foto aún</h3>" . PHP_EOL;
					}
					else
					{
						while ($resultado = $consulta->fetch(PDO::FETCH_ASSOC)) {
							echo "<article class='foto'>" . PHP_EOL;
							echo "<img src='../$resultado[url]' alt='Foto $resultado[id_foto]'></img>" . PHP_EOL;							
							echo "<p>Estado $resultado[estado]</p>" . PHP_EOL;
							echo "<p>Votos $resultado[puntos]</p>" . PHP_EOL;
							//si la foto no ha sido aceptada puede ser eliminada
							if ($resultado["estado"] !== "aceptada") {
								echo "<a href='borrarFoto.php?id=$resultado[id_foto]&rally=$rally' class='estilo_enlace'><button>Eliminar</button></a>" . PHP_EOL;
							}							
							echo "</article>". PHP_EOL;
							
						} 
							
					}

				}
			?>
		</section>
		<section class="fotos">
			<?php
			echo "<h1>Fotos de $registro[titulo]</h1>" . PHP_EOL;
			//fotos generales del rally
			$select = "SELECT f.*, u.nombre, u.apellidos FROM fotos f JOIN usuarios u 
			ON f.usuario_id = u.id_usuario
			WHERE f.rally_id = :id ORDER BY fecha desc";

			$consulta = $conexion->prepare($select);

			$consulta->bindParam(":id", $rally);

			$consulta->execute();

			// comprobamos si algún registro 
			if ($consulta->rowCount() == 0)
			{
				echo "<h3>No hay imágenes disponibles en estos momentos</h3>" . PHP_EOL;
			}else{
				while ($resultado = $consulta->fetch(PDO::FETCH_ASSOC)) {
					//comprobar estado foto 
					if ($resultado["estado"] == "aceptada") {
						echo "<article class='foto'>" . PHP_EOL;
						echo "<img src='../$resultado[url]' alt='Foto $resultado[id_foto]'></img>" . PHP_EOL;
						echo "<p>Votos $resultado[puntos]</p>" . PHP_EOL;
						echo "<p>$resultado[nombre] $resultado[apellidos]</p>" . PHP_EOL;
						echo "
						<form class='formVotar' action='votar.php' method='POST'>
							<input type='hidden' name='id_foto' value='$resultado[id_foto]'>
							<input type='hidden' name='rally_id' value='$resultado[rally_id]'>
							<button class='btnVotar' type='submit'>Votar</button>
						</form>
						" . PHP_EOL;
						echo "</article>". PHP_EOL;
					}
				} 
					
			}
			
			?>

		</section>

		<!-- El modal (inicialmente oculto) -->
		<div id="miModal" class="modal">
			<div class="modal-content">
				<span id="cierreModal">&times;</span>
				<h2>Subir Foto</h2>
				<form action="subirFoto.php" method="POST" enctype="multipart/form-data">
					<input type="hidden" name="rally" value="<?php echo $rally ?>">
					<label for="imagen">Selecciona una foto:</label>
					<input type="file" name="imagen" id="imagen" required>
					<input type="submit" value="Subir Foto">
				</form>
			</div>
		</div>

	</main>
	<?php
            // Libera el resultado y cierra la conexión
    
        $consulta = null;
        $conexion = null;

        include '../utiles/footer.php';
    ?>
</body>
<script>

	// implementa formulario foto modal
	const modal = document.getElementById("miModal");
	const btn = document.getElementById("abrirModal");
	const cierre = document.getElementById("cierreModal");

	// Cuando el usuario haga clic en el botón, modal se vuelve visible
	if (btn != null) {
		btn.addEventListener("click", ()=>{
			modal.style.display = "block";
		});
	}
	

	// Cuando el usuario haga clic en <span> (x), modal vuelve a ser oculto. También cuando se haga clic fuera del modal
	cierre.addEventListener("click", ()=>{
		modal.style.display = "none";
	});

	window.addEventListener("click", function(event) {
		if (event.target == modal) {
			modal.style.display = "none";
		}
	});


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

			//una alerta pararía el proceso
			votos.push(idFoto);
			localStorage.setItem(rally, JSON.stringify(votos));

			// Ahora sí, enviar el formulario manualmente
			this.submit();		
		});
	});


</script>
</html>

<?php
	}
} //cierre del if
?>
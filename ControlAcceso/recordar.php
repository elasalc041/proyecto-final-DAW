<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
	$errores = [];
    $email ="";			
	$mostrarModal = false;  
if ($_SERVER["REQUEST_METHOD"]=="POST")
{		
		// Obtenemos el campo del nombre email

		$email = obtenerValorCampo("email"); 
		
		//-----------------------------------------------------
		// Validaciones
		//-----------------------------------------------------
		// formato email correcto.
		if (!validarEmail($email)) 
		{
			$errores["email"] = "El email no tiene un formato correcto ";
			$email = "";
		} 
		else 
		{
			// En caso de que los datos sean correctos, comprobar que exita el email con ese nombre.
			
			$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

			$consulta = "SELECT * FROM usuarios
						WHERE email = '$email'";

			$resultado = resultadoConsulta($conexion, $consulta);

			if ($resultado->fetch() == null) {
				$errores["email"] = "El email no existe";
				$email = "";
			}

			$resultado = null;
			$conexion = null;

		}

	// Si no hay errores, envío correo electrónico con URL de restablecer la contraseña, el email del usuario y un token:
	if (count($errores) == 0){

		$token = bin2hex(openssl_random_pseudo_bytes(16));

		// Se actualiza la base de datos con un nuevo token
			
		$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

		$update = "UPDATE usuarios SET token = :token WHERE email = :email";
		$consulta = $conexion->prepare($update);

			// Ejecuta actualización
			$consulta->execute([
			"email" => $email,
			"token" => $token
			]);

		$consulta = null;
		$conexion = null;

				/* Envío De Email Con Token */
		// Cabecera
		$headers = [
			"From" => "dwes@php.com",
			"Content-type" => "text/plain; charset=utf-8"
		];

		// Variables para el email
		$emailEncode = urlencode($email);
		$tokenEncode = urlencode($token);
		// Texto del email
		$textoEmail = "
		Hola!\n <br>
		Para reiniciar contraseña entra en el siguiente enlace:\n <br>
		<a href='http://localhost/aplicacion_rallies/ControlAcceso/establecerUsuario.php?email=$emailEncode&token=$tokenEncode'>Pincha aquí</a>
		";

		$mostrarModal = true;
		/*
		// Envio del email
		if (mail($email, 'Establece la contraseña', $textoEmail, $headers)){
			echo "SUCCESS";
		}else{
			echo "ERROR";
		};
		//Redirección a listado 
		header("refresh:3;url=../index.php");
		*/
	}			
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordar contraseña</title>
	<link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
</head>
<body>	
	<header class="sticky-top bg-white shadow-sm">
		<nav class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
			<a href='../index.php'class="btn btn-dark">Inicio</a>
		</nav>
	</header>
	<main class="container my-5">
		<div class="row justify-content-center">			
            <div class="col-6 col-sm-4">
			<div class="card shadow-sm">
				<div class="card-header bg-dark text-white text-center">
                    <h4 class="mb-0">Recordar contraseña</h4>
                </div>
				<div class="card-body">
					<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
					<div class="mb-3">
						<!-- Campo email -->
						 <label for="email" class="form-label fw-bold">Email</label>
						<input type="text" class="form-control" name="email" placeholder="email" value="<?php echo $email ?>" required>
						<?php
							if (isset($errores["email"])):
						?>
							<p class="text-danger small"><?php echo $errores["email"] ?></p>
						<?php
							endif;
						?>
					</div>
					<div class="d-grid">
						<!-- Botón submit -->
						<input type="submit"  class="btn btn-dark mx-auto" value="Recordar">
					</div>
					</form>
				</div>
			</div>
			</div>	
		</div>	
	</main>

<!-- Modal con link -->
<?php if ($mostrarModal): ?>
<div class="modal fade" id="correoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="correoModalLabel">Link establecer contraseña</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <?php echo $textoEmail; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Lanzar el modal -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var modal = new bootstrap.Modal(document.getElementById("correoModal"));
        modal.show();
    });
</script>
<?php endif; ?>
</body>
</html>

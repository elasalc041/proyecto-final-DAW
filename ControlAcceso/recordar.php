<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
		$errores = [];
        $email ="";
		$conexionRealizada = false;	

if ($_SERVER["REQUEST_METHOD"]=="POST")
    {
			$conexionRealizada = true;
			
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
		if ( $conexionRealizada && count($errores) == 0){

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
			http://localhost/aplicacion_rallies/ControlAcceso/establecerUsuario.php?email=$emailEncode&token=$tokenEncode
			";

			echo $textoEmail;
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
 	//Si hay algún error o es la primera vez que conectamos a través del enlace, tenemos que mostrar los errores en la misma página, manteniendo los valores bien introducidos.
  	else{
	?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordar contraseña</title>
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
</head>
<body>
    <h1>Recordar contraseña</h1>

			<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
	    	<p>
	            <!-- Campo email -->
	            <input type="text" name="email" placeholder="email" value="<?php echo $email ?>">
	            <?php
	            	if (isset($errores["email"])):
	            ?>
	            	<p class="error"><?php echo $errores["email"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>       

	        <p>
	            <!-- Botón submit -->
	            <input type="submit" value="Recordar">
	        </p>
	    </form>
</body>
</html>
<?php
    }
?>
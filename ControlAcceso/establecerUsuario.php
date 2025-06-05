<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

//si entra a través del link de activación
if ($_SERVER["REQUEST_METHOD"]=="GET")
{
    //-----------------------------------------------------
    // Variables
    //-----------------------------------------------------
    $email = isset($_REQUEST["email"]) ? urldecode($_REQUEST["email"]) : "";
    $token = isset($_REQUEST["token"]) ? urldecode($_REQUEST["token"]) : "";

    //-----------------------------------------------------
    // COMPROBAR SI SON CORRECTOS LOS DATOS
    //-----------------------------------------------------
    // Conecta con base de datos
    $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

    // Prepara SELECT para obtener la contraseña almacenada del usuario
    $select = "SELECT COUNT(*) as numero FROM usuarios WHERE email = :email AND token= :token";
    $consulta = $conexion->prepare($select);

    // Ejecuta consulta
    $consulta->execute([
    "email" => $email,
    "token" => $token
    ]);

    $resultado = $consulta->fetch();
    $consulta = null;
    $conexion = null;

    // Si no es un usuario válido, le enviamos al formulario de identificación
    if ($resultado["numero"] == 0)
    {    
        header('Location: ../index.php');
        exit();
    }
}
    //-----------------------------------------------------
    // ESTABLECER CONTRASEÑA
    //-----------------------------------------------------
    $errores = [];
    $contrasena1 ="";
    $contrasena2 ="";
    $contrasenaFinal = "";
    $longitudMinima = 4;
    $longitudMaxima = 20;
    $conexionRealizada = false;	

    //si entra a través del formulario
    if ($_SERVER["REQUEST_METHOD"]=="POST")
        {
            $conexionRealizada = true;

            $contrasena1 = obtenerValorCampo("contrasena1");
            $contrasena2 = obtenerValorCampo("contrasena2");
            $email = obtenerValorCampo("email");

            //-----------------------------------------------------
	        // Validaciones
	        //-----------------------------------------------------
	        // formato contraseña correcto.
            if ($contrasena1 === $contrasena2) {
                $contrasenaFinal = $contrasena1;

                if (!validarLongitudCadena($contrasenaFinal, $longitudMinima, $longitudMaxima)) 
                {
                    $errores["contrasena"] = "La contraseña debe tener entre 4 y 20 caracteres ";
                    $contrasenaFinal = "";
                } 
            }else{
                $errores["contrasena"] = "La contraseña no coincide ";
            }

        }  	
        // Si no hay errores, se establece la contraseña y se activa la cuenta:
      if ( $conexionRealizada && count($errores) == 0){

        $hash = password_hash($contrasenaFinal, PASSWORD_BCRYPT);

            //-----------------------------------------------------
            // ACTIVAR CUENTA Y ESTABLECER CONTRASEÑA
            //-----------------------------------------------------

            // Conecta con base de datos
            $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

            // Prepara la actualización
            $update = "UPDATE usuarios SET activo = 1, clave = :hash WHERE email = :email";
            $consulta = $conexion->prepare($update);

            // Ejecuta actualización
            $consulta->execute([
            "email" => $email,
            "hash" => $hash
            ]);

            $consulta = null;
            $conexion = null;

            //-----------------------------------------------------
            // REDIRECCIONAR A LOGIN
            //-----------------------------------------------------
            header('Location: ../index.php');
            exit();
        }
        //Si hay algún error, tenemos que mostrar los errores en la misma página
        else{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Establecer contraseña</title>
         <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
        <link rel="stylesheet" type="text/css" href="../css/estilos.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
    </head>
    <body>
       <main class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white text-center">
                            <h4 class="mb-0">Establecer Contraseña</h4>
                        </div>
                        <div class="card-body">
                            <form id="formulario" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                <!-- Campo oculto email -->
                                <input type="hidden" name="email" value="<?php echo $email ?>">

                                <!-- Contraseña -->
                                <div class="mb-3">
                                    <label for="contrasena1" class="form-label fw-bold">Contraseña</label>
                                    <input type="password" id="contrasena1" name="contrasena1" class="form-control" placeholder="*****" minlength="4" maxlength="20" required>
                                    <?php if (isset($errores["contrasena"])): ?>
                                        <p class="text-danger small"><?php echo $errores["contrasena"] ?></p>
                                    <?php endif; ?>
                                </div>

                                <!-- Repetir contraseña -->
                                <div class="mb-3">
                                    <label for="contrasena2" class="form-label fw-bold">Repite la contraseña</label>
                                    <input type="password" id="contrasena2" name="contrasena2" class="form-control" placeholder="*****" minlength="4" maxlength="20" required>
                                    <?php if (isset($errores["contrasena"])): ?>
                                        <p class="text-danger small"><?php echo $errores["contrasena"] ?></p>
                                    <?php endif; ?>
                                    <div id="errorPass" class="text-danger small" style="display: none;">Las contraseñas no coinciden.</div>
                                </div>

                                <!-- Botón -->
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-dark mx-auto">Establecer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    <script>
        document.getElementById("formulario").addEventListener("submit", function(event) {
            const pass1 = document.getElementById("contrasena1").value;
            const pass2 = document.getElementById("contrasena2").value;
            const errorDiv = document.getElementById("errorPass");

            if (pass1 !== pass2) {
                event.preventDefault();
                errorDiv.style.display = "block";
            } else {
                errorDiv.style.display = "none";
            }
        });
    </script>
    </body>
    </html>
    <?php
        } 

?>

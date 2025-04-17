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
        <link rel="stylesheet" type="text/css" href="../css/estilos.css">
    </head>
    <body>
        <h1>Establecer contraseña</h1>
    
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <p>
                    <!-- Campo oculto email -->
                    <input type="hidden" name="email" value="<?php echo $email ?>">
                    <!-- Campo password -->
                     <label for="contrasena1">Contraseña: </label>
                    <input type="password" name="contrasena1" placeholder="*****">
                    <?php
                        if (isset($errores["contrasena"])):
                    ?>
                        <p class="error"><?php echo $errores["contrasena"] ?></p>
                    <?php
                        endif;
                    ?>
                </p>   
                <p>
                    <!-- Campo password -->
                    <label for="contrasena2">Repite la contraseña: </label>
                    <input type="password" name="contrasena2" placeholder="*****">
                    <?php
                        if (isset($errores["contrasena"])):
                    ?>
                        <p class="error"><?php echo $errores["contrasena"] ?></p>
                    <?php
                        endif;
                    ?>
                </p>      
    
                <p>
                    <!-- Botón submit -->
                    <input type="submit" value="Establecer">
                </p>
            </form>
    </body>
    </html>
    <?php
        } 

?>

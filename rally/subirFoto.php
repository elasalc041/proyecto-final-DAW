<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();

if (!isset($_SESSION["email"])) {
    header("Location: ../index.php");
} else {
    $email = $_SESSION["email"];

    //obtener datos del usuario
    $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

    $consulta = "SELECT id_usuario FROM usuarios WHERE email = :email;";

    $consulta = $conexion->prepare($consulta);

    // Ejecuta consulta
    $consulta->execute([
        "email" => $email
    ]);

    // Guardo el resultado
    $resultado = $consulta->fetch();
   
    $id = (int) $resultado["id_usuario"];

    $consulta = null;
    $conexion = null;

    // Verificar si se ha entrado por el formulario 
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../index.php");
    }else{

        $rally = obtenerValorCampo("rally"); 

        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);


        $consulta = "SELECT * FROM rally WHERE id_rally = :rally;";

        $consulta = $conexion->prepare($consulta);
    
        // Ejecuta consulta
        $consulta->execute([
            "rally" => $rally
        ]);
        
        $resultado = $consulta->fetch();

        //capturar condiciones fotos del rally
        $limite_fotos = $resultado["lim_fotos"];
        $pesoFoto = $resultado["tam_foto"];
        $formatoFoto = $resultado["formato_foto"];

        //numero de fotos ya subidas en el rally
        $consulta = "SELECT * FROM fotos WHERE rally_id = :rally AND usuario_id= :usuario;";

        $consulta = $conexion->prepare($consulta);
    
        // Ejecuta consulta
        $consulta->execute([
            "rally" => $rally,
            "usuario" => $id
        ]);
     


        $nombreFotosSubidas = [];
        $errores = [];

        if($consulta->rowCount() >= $limite_fotos) {
            $consulta = null;
            $conexion = null;
            echo "Alcanzado límite de fotos en el rally para el usuario";
            header("refresh:3;url=rally.php?rally=$rally");
        }else{
            while ($resultado = $consulta->fetch(PDO::FETCH_ASSOC)) {
                $nombreFotosSubidas[] = $resultado["url"];  //guardar nombres de fotos ya subidas para evitar repetir
            }
        }

        $consulta = null;
        $conexion = null;


        //gestión de la imagen en el servidor y errrores
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
            $directorioSubida = "uploads/usuarios/$id/rallies/$rally/";
            $nombreOriginal = basename($_FILES['imagen']['name']);
            $rutaFoto = $directorioSubida . $nombreOriginal;

            //validar nombre no sea repetido
            $nombreRepetido = false;
            foreach ($nombreFotosSubidas as $value) {
                if ($value === $rutaFoto) {
                   $nombreRepetido = true;
                }
            }

            // Validar tipo y tamaño
            
            $tiposPermitidos = ["image/$formatoFoto"];
            if (!in_array($_FILES['imagen']['type'], $tiposPermitidos)) {
                $errores['imagen'] = "Solo se permiten imágenes de tipo $formatoFoto";
            } elseif ($_FILES['imagen']['size'] > $pesoFoto * 1024 * 1024) {
                $errores['imagen'] = "La imagen no puede superar $pesoFoto MB.";
            } elseif ($nombreRepetido) {
                $errores['imagen'] = "Nombre de la imagen es repetido.";         
            } else {
                move_uploaded_file($_FILES['imagen']['tmp_name'], "../$rutaFoto");
            }
        }else{
            header("Location: rally.php?rally=$rally");
        }

        //si ha habido algún error
        if (count($errores) > 0) {
            echo "La imagen no pudo ser cargada. $errores[imagen]";
            header("refresh:3;url=rally.php?rally=$rally");
        }else{

            $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

            $consulta = "INSERT INTO fotos (url, usuario_id, rally_id) VALUES (:img, :usuario, :rally);";

            $resultado = $conexion->prepare($consulta);

			$resultado->bindParam(":img", $rutaFoto);
            $resultado->bindParam(":usuario", $id);
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

            header("Location: rally.php?rally=$rally");
        }


    }

}

?>
<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();
// Comprueba si existe la sesión "email", en caso contrario vuelve a la página de login
if (!isset($_SESSION["email"])){
    header("Location: ../index.php");
} else{

    $email = $_SESSION["email"];

    //obtener el rol del usuario
    $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

    $consulta = "SELECT * FROM usuarios WHERE email = :email;";

    $consulta = $conexion->prepare($consulta);

    // Ejecuta consulta
    $consulta->execute([
        "email" => $email
    ]);

    // Guardo el resultado
    $resultado = $consulta->fetch();

    // Guardo el id del usuario
    $id = (int) $resultado["id_usuario"];

    $resultado = null;
    $conexion = null;

    
    //Si se ha seleccionado un registro para borrar
    if (count($_REQUEST) > 0)
    {

        //si ha venido a través del enlace con el id de la foto
        if (isset($_GET["id"]) && isset($_GET["rally"]))
        {
            $exito = false;

            $foto = $_GET["id"];
            $rally = $_GET["rally"];

            $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

            //comprobar estado de la foto y captura de url
            $select = "SELECT * FROM fotos WHERE id_foto = :id AND rally_id = :rally AND usuario_id =  :usuario; ";

            $consulta = $conexion->prepare($select);

            $consulta->bindParam(":id", $foto);
            $consulta->bindParam(":rally", $rally);    
            $consulta->bindParam(":usuario", $id);

            $consulta->execute();

            $resultado = $consulta->fetch();

            $estado = $resultado["estado"];
            $url = $resultado["url"];

            if ($estado == "aceptada") {
                header("Location: rally.php?rally=$rally");
                exit();
            }

            //borrado de la foto si todo es correcto
            $delete = "DELETE FROM fotos WHERE id_foto = :id AND rally_id = :rally AND usuario_id =  :usuario; ";

            $resultado = $conexion->prepare($delete);

            $resultado->bindParam(":id", $foto);
            $resultado->bindParam(":rally", $rally);    
            $resultado->bindParam(":usuario", $id);

            try {
                $resultado->execute();                 
                
                $exito = true;

                $resultado = null;
                $consulta = null;
				$conexion = null;

            } catch (PDOException $e) {
                echo $e->getMessage();
            }

            if ( $exito) {
                if (is_file("../$url")) {
                    unlink("../$url"); // Elimina el archivo 
                }  
            }
            
            //En ambos casos, redireccionar al listado original.

            header("Location: rally.php?rally=$rally");
            exit();
        } 
        
    } 
    //Evitar que se pueda entrar directamente a la página .../borrar.php, redireccionando en tal caso a la página del listado
    else 
    {
        header("Location: ../index.php");
        exit();
    }
}
?>
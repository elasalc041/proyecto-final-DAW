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

    $consulta = "select * FROM usuarios WHERE email = :email;";

    $consulta = $conexion->prepare($consulta);

    // Ejecuta consulta
    $consulta->execute([
        "email" => $email
    ]);

    // Guardo el resultado
    $resultado = $consulta->fetch();

    // Guardo el id del usuario
    $id = (int) $resultado["id"];

    $resultado = null;
    $conexion = null;

    
    //Si se ha seleccionado un registro para borrar
    if (count($_REQUEST) > 0)
    {

        //si ha venido a través del enlace con la id de la oferta 
        if (isset($_GET["ofertaId"]))
        {
            $exito = false;

            $idOferta = $_GET["ofertaId"];

            $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

            $consulta = "DELETE FROM solicitudes WHERE oferta_id = :oferta AND usuario_id =  :usuario";

            $resultado = $conexion->prepare($consulta);

            $resultado->bindParam(":oferta", $idOferta);

            $resultado->bindParam(":usuario", $id);

            try {
                $resultado->execute();
                $resultado = null;
				$conexion = null;

                $exito = true;
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
            
            //En ambos casos, redireccionar al listado original.

            header("Location: ofertasSolicitadas.php");
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
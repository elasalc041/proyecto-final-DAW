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
    $id = (int) $resultado["id_usuario"];

    $resultado = null;
    $conexion = null;

    
    //Si se ha seleccionado un registro para borrar
    if (count($_REQUEST) > 0)
    {

        //si ha venido a través del enlace con el id del rally
        if (isset($_GET["id"]))
        {
            $exito = false;

            $rally = $_GET["id"];

            $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

            $consulta = "DELETE FROM inscripciones WHERE rally_id = :rally AND usuario_id =  :usuario"; //borra la inscripcion

            $resultado = $conexion->prepare($consulta);

            $resultado->bindParam(":rally", $rally);

            $resultado->bindParam(":usuario", $id);


            $consulta2 = "DELETE FROM fotos WHERE rally_id = :rally AND usuario_id =  :usuario"; //borra las fotos subidas

            $resultado2 = $conexion->prepare($consulta2);
    
            $resultado2->bindParam(":rally", $rally);
    
            $resultado2->bindParam(":usuario", $id);

            try {
                $resultado->execute();
                $resultado2->execute();                   

                $exito = true;

                $resultado = null;
                $resultado2 = null;
				$conexion = null;

            } catch (PDOException $e) {
                echo $e->getMessage();
            }

            if ($exito) {
                //borrado directorio fotos
                borrarDirectorio("../uploads/usuarios/$id/rallies/$rally/");
            }
            
            //redireccionar al listado original.
            header("Location: ../rally/rally.php?rally=$rally");
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
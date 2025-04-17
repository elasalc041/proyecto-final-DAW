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

            //si se ha alcanzado aforo máximo no puede inscribirse
            //guardo numero Solicitudes
            $consulta1 = "SELECT * FROM solicitudes WHERE oferta_id= :oferta";

            $consulta1 = $conexion->prepare($consulta1);
            
            $consulta1->execute([
                "oferta" => $idOferta
            ]);
            
            $numSolicitudes = $consulta1->rowCount();
           

            // Guardo el aforo
            $consulta2 = "SELECT * FROM ofertas WHERE id= :oferta";

            $consulta2 = $conexion->prepare($consulta2);
            
            $consulta2->execute([
                "oferta" => $idOferta
            ]);

            $resultado = $consulta2->fetch();
            
            $aforo = (int) $resultado["aforo"];


            $consulta1 = null;
            $consulta2 = null;


            if ($numSolicitudes >= $aforo)
			{
					//desconectamos y volvemos al listado original
					
					$conexion = null;
                    echo "Aforo completo";  
					header("refresh:3;url=../index.php");
			}else{                


                $insert = $conexion->prepare("INSERT INTO solicitudes (oferta_id, usuario_id, fecha_solicitud, created_at, updated_at) 
                values (:oferta, :usuario, :fechaSolicitud, :fechaCreada, :fechaModif)");

                // preparar la consulta (usar bindParam)
                

                $insert->bindParam(":oferta", $idOferta);

                $insert->bindParam(":usuario", $id);

                $insert->bindParam(":fechaSolicitud", $fechaActual);

                $insert->bindParam(":fechaCreada", $fechaActual);

                $insert->bindParam(":fechaModif", $fechaActual);


                // ejecutar la consulta 
                try {
                    $insert->execute();
                    $resultado = null;
                    $conexion = null;

                    $exito = true;
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }

                //Si todo ha ido bien, mostrar mensaje
                if ($exito) 
                { 
                    echo "Registro en la actividad con éxito";            
                } 
                //Si no ha ido bien, mostrar mensaje 
                else 
                {
                    echo "No se ha podido registrar en la actividad";  
                }
                
                //En ambos casos, redireccionar al listado original tras 3 segundos.

                header("refresh:3;url=../index.php");
                exit();
            }
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
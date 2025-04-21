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

         //si ha venido a través del enlace con la id de la oferta 
        if (isset($_GET["id"]))
        {
            $exito = false;

            $rally = $_GET["id"];

            $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

            //si se ha alcanzado aforo máximo no puede inscribirse
            //guardo numero Inscripciones
            $consulta1 = "SELECT * FROM inscripciones WHERE rally_id= :rally";

            $consulta1 = $conexion->prepare($consulta1);
            
            $consulta1->execute([
                "rally" => $rally
            ]);
            
            $numInscritos = $consulta1->rowCount();
           

            // Guardo el aforo
            $consulta2 = "SELECT * FROM rally WHERE id_rally= :rally";

            $consulta2 = $conexion->prepare($consulta2);
            
            $consulta2->execute([
                "rally" => $rally
            ]);

            $resultado = $consulta2->fetch();
            
            $participantes = (int) $resultado["participantes"];


            $consulta1 = null;
            $consulta2 = null;


            if ($numInscritos >= $participantes)
			{
					//desconectamos y volvemos al listado original
					
					$conexion = null;
                    echo "Aforo completo";  
					header("refresh:3;url=../rally/rally.php?rally=$rally");
			}else{                


                $insert = $conexion->prepare("INSERT INTO inscripciones (rally_id, usuario_id, fecha) 
                values (:rally, :usuario, :fechaSolicitud)");

                // preparar la consulta (usar bindParam)
                

                $insert->bindParam(":rally", $rally);

                $insert->bindParam(":usuario", $id);

                $insert->bindParam(":fechaSolicitud", $fechaActual);


                // ejecutar la consulta 
                try {
                    $insert->execute();
                    $resultado = null;
                    $conexion = null;

                    $exito = true;


                    //creación de directorio para imágenes subidas al rally
				    $directorio = "../uploads/usuarios/$id/rallies/$rally/";

                    if (!is_dir($directorio)) {
                        mkdir($directorio);
                    }

                } catch (PDOException $e) {
                    echo $e->getMessage();
                }

                //Si todo ha ido bien, mostrar mensaje
                if ($exito) 
                { 
                    echo "Inscripción realizada con éxito";            
                } 
                //Si no ha ido bien, mostrar mensaje 
                else 
                {
                    echo "No se ha podido inscribir";  
                }
                
                //En ambos casos, redireccionar al listado original tras 3 segundos.

                header("refresh:1;url=../rally/rally.php?rally=$rally");
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
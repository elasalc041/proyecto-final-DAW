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

	//obtener el id del usuario
    $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

    $consulta = "select * FROM gestores WHERE email = :email;";

    $consulta = $conexion->prepare($consulta);

    // Ejecuta consulta
    $consulta->execute([
        "email" => $email
    ]);

    // Guardo el resultado
    $resultado = $consulta->fetch();

    // Guardo el perfil
    $perfil = (int) $resultado["perfil_id"];


	if ($perfil != 1) {
		$resultado = null;
		$conexion = null;
		header("Location: ../index.php");
  		exit();
	}else{

        $resultado = null;
        $conexion = null;


        //Si se ha seleccionado un registro para borrar
        if (count($_REQUEST) > 0)
        {

            if (isset($_GET["ofertaId"]))
            {
                $exito = false;

                $oferta = $_GET["ofertaId"];

                $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

                $consulta = "DELETE FROM ofertas WHERE id = :id";

                $resultado = $conexion->prepare($consulta);

                $resultado->bindParam(":id", $oferta);

                try {
                    $resultado->execute();
                    $resultado = null;
                    $conexion = null;

                    $exito = true;
                } catch (PDOException $e) {
                    exit($e->getMessage());
                }

                //Si todo ha ido bien, mostrar mensaje
                if ($exito) 
                {  
                    echo "Oferta borrada con éxito";              
                } 
                //Si no ha ido bien, mostrar mensaje 
                else 
                {
                    echo "No se ha podido borrar la oferta";
                }
                
                //En ambos casos, redireccionar al listado original tras 3 segundos.
                header("refresh:3;url=listados.php");
                exit();
            } 
        } 
            //Evitar que se pueda entrar directamente a la página .../borrar.php, redireccionando
        else 
        {
            header("Location: ../index.php");
            exit();
        }

    }
    
}   
//cierre del if
?>

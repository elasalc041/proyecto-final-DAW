<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

    // Verificar si se ha entrado por el formulario 
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../index.php");
    }else{

        $foto = obtenerValorCampo("id_foto"); 
        $rally = obtenerValorCampo("rally_id"); 

        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

        //numero de fotos ya subidas en el rally
        $consulta = "SELECT * FROM fotos WHERE id_foto = :id AND estado= 'aceptada';";

        $consulta = $conexion->prepare($consulta);
    
        // Ejecuta consulta
        $consulta->execute([
            "id" => $foto
        ]);

        if ($consulta->rowCount() == 0) {
            $conexion = null;
            $consulta = null;
            echo "No existe la foto";
            header("refresh:3;url=rally.php?rally=$rally");
        }

        


        $consulta = "UPDATE fotos SET puntos= puntos+1 WHERE id_foto = :id;";

        $consulta = $conexion->prepare($consulta);
    
        $consulta->bindParam(":id", $foto);

         // ejecutamos la consulta 
         try 
         {
             $consulta->execute();
         }
         catch (PDOException $exception)
         {
             exit($exception->getMessage());
         }
        
       

        $consulta = null;
        $conexion = null;

        header("Location: rally.php?rally=$rally");
       



    }


?>
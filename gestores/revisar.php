<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();
// Comprueba si existe la sesión "email", en caso contrario vuelve a la página inicial
if (!isset($_SESSION["email"])){
    header("Location: ../index.php");
} else{
    //comprobamos que entramos por el formulario
    if (!$_SERVER["REQUEST_METHOD"]=="POST")
    {
        header("Location: ../index.php");

    } else{
       
        $arrayID_actividades = [];
        
        //asignar los valores de ID enviadas al formulario a un nuevo array
        foreach ($_POST as $key => $value) {
            $arrayID_actividades[] = $key;
        }


        //Nos conectamos a la BBDD

		$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

        foreach ($arrayID_actividades as $value) {     
            
			
              // Creamos una variable con la consulta "UPDATE" a ejecutar
  
              $consulta = "UPDATE ofertas SET visada= 1, updated_at= :fecha_modif WHERE id = :id";
              
              // preparamos la consulta (bindParam)
  
              $resultado = $conexion->prepare($consulta);
  
              $resultado->bindParam(":fecha_modif", $fechaActual);
              $resultado->bindParam(":id", $value);
  
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

        }

        $conexion = null;
  
        // redireccionamos al listado
        header("Location: ofertasRevisar.php");

    }

}
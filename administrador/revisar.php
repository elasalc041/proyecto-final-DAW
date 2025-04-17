<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();
// Comprueba si existe la sesión "email", en caso contrario vuelve a la página inicial
if (!isset($_SESSION["email"])) {
    header("Location: ../index.php");
} else {
    $email = $_SESSION["email"];

    //obtener el perfil del usuario
    $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

    $consulta = "SELECT * FROM usuarios WHERE email = :email";

    $consulta = $conexion->prepare($consulta);

    $consulta->execute([
        "email" => $email
    ]);

    $resultado = $consulta->fetch();

    // Guardo el perfil
    $perfil = (int) $resultado["rol_id"];

    // Comprueba que el rol sea "Admin", en caso contrario vuelve a la página inicial
    if ($perfil !== 1) {
        header("Location: ../index.php");
    }

    $resultado = null;
    $conexion = null;


    //comprobar venir por el enlace
    if (count($_REQUEST) > 0) {

        if (!(isset($_GET["id"]) && isset($_GET["validar"]))) {
            //Evitar que se pueda entrar directamente a la página
            header("Location: ../index.php");
            exit();
        } else {

            $foto = $_GET["id"];
            $validar = $_GET["validar"];

            //Nos conectamos a la BBDD

            $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

            if ($validar == 0) {

                // rechazada si validar = 0

                $consulta = "UPDATE fotos SET estado= 'rechazada' WHERE id = :id";

            } else {

                // aceptada si validar = 1

                $consulta = "UPDATE fotos SET estado= 'aceptada' WHERE id = :id";

            }


            $resultado = $conexion->prepare($consulta);

            $resultado->bindParam(":id", $foto);

            // ejecutamos la consulta 
            try {
                $resultado->execute();
            } catch (PDOException $exception) {
                exit($exception->getMessage());
            }

            $resultado = null;

            $conexion = null;

            // redireccionamos al listado
            header("Location: listados.php");

        }

    }else{
        header("Location: ../index.php");
        exit();
    }

}
<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

// Activa las sesiones
session_name("sesion-privada");
session_start();
// Comprueba si existen los valores guardados del formulario en la sesion, en caso contrario vuelve
if (!isset($_SESSION["emailNuevo"]) || !isset($_SESSION["apellidos"])  || !isset($_SESSION["nombre"])){
    header("Location: ../index.php");
} else{
    $nombre = $_SESSION["nombre"];
    $email = $_SESSION["emailNuevo"];
    $apellidos = $_SESSION["apellidos"];
    $descripcion = $_SESSION["descripcion"];
    $tfno = $_SESSION["tfno"];

    //-----------------------------------------------------
// Crear cuenta
//-----------------------------------------------------
    
        /* Registro En La Base De Datos */
        // Prepara INSERT
        $token = bin2hex(openssl_random_pseudo_bytes(16));
        $insert = "INSERT INTO usuarios (nombre, apellidos, email, clave, activo, token, tfno, rol_id, descripcion) VALUES
    (:nombre, :apellidos, :email, :clave, :activo, :token, :tfno, :rol, :descrip)";

        $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

        $consulta = $conexion->prepare($insert);

        // Ejecuta el nuevo registro en la base de datos
        $consulta->execute([
            "nombre" => $nombre,
            "apellidos" => $apellidos,
            "email" => $email,
            "clave" => "",
            "activo" => 0,
            "token" => $token,
            "tfno" => $tfno,
            "rol" => 2,
            "descrip" => $descripcion
        ]);

        //crear carpeta usuario
        $select = "SELECT id_usuario FROM usuarios WHERE email= :email";

        $consulta = $conexion->prepare($select);

        $consulta->execute([
            "email" => $email
        ]);

        $resultado = $consulta->fetch();
   
        $id = (int) $resultado["id_usuario"];

		mkdir("../uploads/usuarios/$id/", 0777, true);
        mkdir("../uploads/usuarios/$id/perfil", 0777, true);
        mkdir("../uploads/usuarios/$id/rallies", 0777, true);




        $consulta = null;
        $conexion = null;

        /* Envío De Email Con Token */
        // Cabecera
        $headers = [
            "From" => "dwes@php.com",
            "Content-type" => "text/plain; charset=utf-8"
        ];

        // Variables para el email
        $emailEncode = urlencode($email);
        $tokenEncode = urlencode($token);
        // Texto del email
        $textoEmail = "
    Hola!\n <br>
    Has sido registrado/a en nuestra plataforma Rallies Fotográficos.\n <br>
    Para activar entra en el siguiente enlace:\n <br>
    http://localhost/aplicacion_rallies/ControlAcceso/establecerUsuario.php?email=$emailEncode&token=$tokenEncode
    ";

    session_destroy();

    header("Location: ../ControlAcceso/establecerUsuario.php?email=$emailEncode&token=$tokenEncode");
}

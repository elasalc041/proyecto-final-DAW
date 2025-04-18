<?php 
// Incluye ficheros de variables y funciones
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

// Comprobamos que nos llega a través del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Variables del formulario

    if (isset($_REQUEST['email']) && isset($_REQUEST['contrasena'])) {
        if (validarEmail($_REQUEST['email'])) {
            $email = obtenerValorCampo("email");
            $passwdLogin = obtenerValorCampo("contrasena");
        }else{
            $email = ""; 
        }    
    }else{
        $email = ""; 
        $passwdLogin = ""; 
    }


    // conexión a base de datos y consulta en usuarios
    $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
    
    $consulta = " SELECT email, clave, activo FROM usuarios  WHERE email = ?";
    		
    $consulta = $conexion->prepare($consulta);

        $consulta->bindParam(1, $email);
                
        // ejecutamos la consulta 

        $consulta->execute();


        // comprobamos si hay algún registro 
        if ($consulta->rowCount() == 0)
        {
        //Si no lo hay, desconectamos y volvemos al inicio
            $consulta = null;
            $conexion = null;
            print'<p style="color: red">El email o la contraseña es incorrecta.</p>';
            header("refresh:3;url=../index.php");
            exit();
        }
        else 
        {
        // Si hay algún registro, Obtenemos el resultado (usamos fetch())
            $registro = $consulta->fetch();
            $emailBBDD = $registro["email"];
            $hash = $registro["clave"];            

            $consulta = null;
            $conexion = null;
                      
            //controlar que usuario es activo
            if ((int) $registro["activo"] !== 1) {
                print'<p style="color: red">Tu cuenta aún no está activa. ¿Has comprobado tu bandeja de correo?</p>';
                header("refresh:3;url=../index.php");
            }else{
                // Comprobamos si los datos son correctos
                if (password_verify($passwdLogin, $hash)) {
                    // Si son correctos, creamos la sesión
                    session_name("sesion-privada");
                    session_start();
                    $_SESSION['email'] = $email;
                    // Redireccionamos a la página privada
                    header('Location: ../index.php');
                    exit();
                    }
                    else {
                    // Si no son correctos, informamos al usuario
                    print'<p style="color: red">El email o la contraseña es incorrecta.</p>';
                    header("refresh:3;url=../index.php");
                    exit();
                }
            }
            
        }
}
?>
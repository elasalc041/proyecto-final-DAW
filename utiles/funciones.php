<?php
	
/**
 * FUNCIONES DE VALIDACIÓN
 */

	/*
    * Función que devuelve el valor del campo recibido como párametro
    * @param {string} $campo - Nombre del campo a comprobar en el REQUEST
    * @param {string} $valor - Valor del campo recibido como parámetro
    */
    function obtenerValorCampo(string $campo): string{
        // Comprobamos si nos llega el nombre del campo en el REQUEST
        if (!isset($_REQUEST[$campo])) 
        {
          $valor = "";
        } 
        else 
        {
          // Limpiamos el campo de etiquetas y espacios
          $valor = trim(strip_tags($_REQUEST[$campo]));
        }
        return $valor;
    }

    /*
    * Método que valida si el campo está dentro de los límites indicado
    * tiene una longitud mínima de tres caracteres
    * @param {string} $texto - Texto a validar
    * @param {int} $minimo - Longitud mínimo que puede tener
    * @param {int} $maximo - Longitud máxima que puede tener
    * @return {boolean}
    */
    function validarLongitudCadena (string $texto, int $minimo, int $maximo): bool
    {
      $validacion = false;
      if(strlen($texto) >= $minimo && strlen($texto) <= $maximo)
      {
        $validacion = true;
      }
      return $validacion;
    }

    /*
    * Método que valida si es un número entero es positivo 
    * @param {string} - Número a validar
    * @return {bool}
    */
    function validarEnteroPositivo(string $numero): bool
    {
        return (filter_var($numero, FILTER_VALIDATE_INT) === FALSE || $numero <= 0) ? False : True;
    }


    /*
    * Método que valida si el texto tiene un formato válido de E-Mail
    * @param {string} - Email
    * @return {bool}
    */
    function validarEmail(string $texto): bool
    {
        return (filter_var($texto, FILTER_VALIDATE_EMAIL) === FALSE) ? False : True;
    }

    /*
    * Método que valida si es un número entero y está entre unos límites
    * @param {string} - $numero Número a validar
    * @param {int} - $limiteInferior Límite inferior
    * @param {int} - $limiteSuperior Límite superior
    * @param {string} - Número a validar
    * @return {bool}
    */
    function validarEnteroLimites(string $numero, int $limiteInferior , int $limiteSuperior): bool
    {
        return (filter_var($numero, FILTER_VALIDATE_INT,  ["options" => ["min_range" => $limiteInferior, "max_range" => $limiteSuperior]]) === False) ? False : True;
    }

    /*
    * Método que valida si es un número decimal positivo
    * @param {string} - Número a validar
    * @return {bool}
    */
    function validarDecimalPositivo(string $numero): bool
    {
        return (filter_var($numero, FILTER_VALIDATE_FLOAT) === FALSE || $numero <= 0) ? False : True;
    }


        /**
    * Método que valida si un texto no está vacío
    * @param {string} - Texto a validar
    * @return {boolean}
    */
    function validarRequerido(string $texto): bool
    {
    return !(trim($texto) == "");
    }



        /**
    * Método que valida si el formato fecha es correcto y posterior a fecha actual
    * @param {string} - Fecha a validar en formato aaaa-mm-dd
    * @return {boolean}
    */
    function validarFecha($date, $format = 'Y-m-d H:i:s'): bool 
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date && $date > date('Y-m-d');
}

    /**
    * Método que valida si el formato fecha es correcto, posterior a fecha actual y una fecha es anterior a al otra
    * @param {string} - Fecha a validar en formato aaaa-mm-dd
    * @param {string} - Fecha a validar en formato aaaa-mm-dd
    * @return {boolean}
    */

    function validarDosFechas($fecha1, $fecha2) {
      $f1 = DateTime::createFromFormat('Y-m-d', $fecha1);
      $f2 = DateTime::createFromFormat('Y-m-d', $fecha2);
      $hoy = new DateTime(); // Fecha actual

      // Validar que ambas fechas sean válidas
      if (!$f1 || !$f2) {
          return false;
      }

      // Verificar que ambas fechas sean futuras
      if ($f1 < $hoy->format("Y-m-d") || $f2 < $hoy->format("Y-m-d")) {
          return false;
      }

      // Verificar que la primera fecha sea anterior o igual a la segunda
      return $f1 <= $f2;
    }



/**
 * FIN FUNCIONES DE VALIDACIÓN
 */


/**
 * FUNCIONES TRABAJAR CON BBDD
 */
	

    function conectarPDO(string $host, string $user, string $password, string $bbdd): PDO 
    {
        try 
        {
          $mysql="mysql:host=$host;dbname=$bbdd;charset=utf8";
          $conexion = new PDO($mysql, $user, $password);
          // set the PDO error mode to exception
          $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
        } 
        catch (PDOException $exception) 
        {
           exit($exception->getMessage());
        }
        return $conexion;    
    }


	
	function resultadoConsulta (PDO $conexion, string $consulta): PDOStatement 
    {
		$resultado = $conexion->query($consulta);
		return $resultado;
	}


/**
 * FIN FUNCIONES TRABAJAR CON BBDD
 */




 /**
 * FUNCIONES VARIAS
 */

    /**
    * Método que formatea fecha para obtener formato fecha española
    * @param {string} - Fecha a formatear en formato aaaa-mm-dd
    * @return {string} - Fecha formato dd-mm-aaaa
    */
    function formatoFecha($fechaFormatoIngles): string
{
  $array = explode("-", $fechaFormatoIngles);
  $fechaFormateada = implode("-", array_reverse($array));
  return  $fechaFormateada;
}

  /**
      * Método que elimina un directorio dado a través  de la ruta y todo su contenido 
      * @param {string} - Ruta del directorio
      * @return {bool} - 
      */
  function borrarDirectorio($ruta) {
    if (!is_dir($ruta)) return false;

    $archivos = array_diff(scandir($ruta), ['.', '..']);
    foreach ($archivos as $archivo) {
        $archivoRuta = $ruta . DIRECTORY_SEPARATOR . $archivo;
        if (is_dir($archivoRuta)) {
            borrarDirectorio($archivoRuta); // Llamada recursiva
        } else {
            unlink($archivoRuta); // Borra archivo
        }
    }

    return rmdir($ruta); // Finalmente, borra el directorio
  }


/**
 * FIN FUNCIONES VARIAS
 */

	
?>
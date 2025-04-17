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
	}

	$resultado = null;
	$conexion = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar oferta</title>
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
</head>
<body>
<body>
    <h1>Modificar oferta</h1>
    <?php
		// crea las variables para la comprobación de los datos y conectamos con la BBDD para obtener y pintar los datos de la id que acabamos de enviar a la página
		$comprobarConexion = false;
		$errores = [];
    	
		//si entramos por el formulario o por el enlace de modificar
    	if (count($_REQUEST) > 0) 
    	{
			//si entramos por enlace para modificar
    		if (isset($_GET["ofertaId"])) 
    		{
            	$oferta = $_GET["ofertaId"];

            	//Conectamos a la BBDD para obtener datos de la oferta para modificar

				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
            
        		// Montamos la consulta a ejecutar

				$consulta = "SELECT * FROM ofertas WHERE id = ?";
        	
		        // prepararamos la consulta

				$consulta = $conexion->prepare($consulta);
			
		        // parámetro (usamos bindParam)

				$consulta->bindParam(1, $oferta);
		    
		        // ejecutamos la consulta 

				$consulta->execute();

		        // comprobamos si hay algún registro 
				if ($consulta->rowCount() == 0)
				{
					//Si no lo hay, desconectamos y volvemos al listado 
					$consulta = null;
					$conexion = null;
					header("Location: listados.php");
				}
				else 
				{
					// Si hay algún registro, Obtenemos el resultado (usamos fetch())
						
					$registro = $consulta->fetch();
					$ofertante = $registro["usuario_id"];
					$nombre = $registro["nombre"];
					$descripcion = $registro["descripcion"];
					$fecha = $registro["fecha_actividad"];
					$aforo = $registro["aforo"];
					$visada = $registro["visada"];
					$categoria = $registro["categoria_id"];

					$consulta = null;
					$conexion = null;	        
				}
            } 
            else 
            {
				//si entramos por formulario
				$comprobarConexion = true;
		    	// Obtenemos campos introducidos y comenzamos la comprobación de los datos .
			    
				$oferta = obtenerValorCampo("id");
				$ofertante = obtenerValorCampo("ofertante");  			   
				$nombre = obtenerValorCampo("nombre");  
				$descripcion = obtenerValorCampo("descripcion");
				$fecha = obtenerValorCampo("fecha");
				$aforo = obtenerValorCampo("aforo");
				$visada = obtenerValorCampo("visada");
				$categoria = obtenerValorCampo("categoria_id");
			 

				 //-----------------------------------------------------
		        // Validaciones
		        //-----------------------------------------------------
				// Comprueba que el id proveniente del formulario se corresponde con uno que tengamos 
				//conectamos a la bbdd

				$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

				$consulta = "SELECT * FROM ofertas WHERE id = ?";
        	
		        // prepararamos la consulta

				$consulta = $conexion->prepare($consulta);
			
		        // parámetro (usamos bindParam)

				$consulta->bindParam(1, $oferta);
		    
		        // ejecutamos la consulta 

				$consulta->execute();

				// comprobamos si algún registro 
				if ($consulta->rowCount() == 0)
				{
					//Si no lo hay, desconectamos y volvemos al listado original
					$consulta = null;
					$conexion = null;
					header("Location: listados.php");
				}
		        
	        	// Nombre de la oferta debe rellenarse
				if ($nombre == "")
				{
					$errores["nombre"] = "Campo nombre no puede quedar vacío";
					$nombre = ""; 
				}	
		        else 
		        {
		        	// Comprobar que no exita una oferta con ese nombre.
					//Para ello, te conectas a la bbdd, ejecutas un SELECT y comprueba si hay ya una oferta con ese nombre.
					$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
					$consulta = "SELECT * FROM ofertas WHERE nombre = '$nombre' AND id != '$oferta'";

					$consulta = $conexion->query($consulta);
		        	
					// comprobamos si, al ejecutar la consulta, tenemos más de 0 registro. En tal caso, generar el mensaje de error.
					if ($consulta->rowCount() > 0)
					{
						//Msj Error
						$errores["nombre"] = "El nombre de la oferta ya existe";
						$nombre = "";
					}

					$consulta = null;
					$conexion = null;					
		        }



				// ID debe ser número entero positivo
				if (!validarEnteroPositivo($ofertante))
				{
					$errores["ofertante"] = "Campo usuario debe ser positivo";
					$usuario = ""; 
				}else 
				{
					// Comprobar existe un ofertante con el id.
					//Para ello, te conectas a la bbdd, ejecutas un SELECT y comprueba si id existe y pertenece a un ofertante.
					$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
					$consulta = "SELECT * FROM usuarios WHERE id = '$ofertante' AND perfil_id = 3";
	
					$consulta = $conexion->query($consulta);
					
					// comprobamos si, al ejecutar la consulta, tenemos 0 registro. En tal caso, generar el mensaje de error.
					if ($consulta->rowCount() == 0)
					{
						//Msj Error
						$errores["ofertante"] = "El id del usuario no es correcto";
						$usuario = "";
					}
	
					$consulta = null;
					$conexion = null;					
				}	



					// Nombre de categoría a partir de la función "validarEnteroPositivo", ya que usaremos el id
				if (!validarEnteroPositivo($categoria))
				{
					$errores["categoria"] = "Campo categoría no cumple los requisitos establecidos";
					$categoria = ""; 
				}
				
				// Aforo debe ser positivo
				if (!validarEnteroPositivo($aforo))
				{
					$errores["aforo"] = "Campo aforo no cumple los requisitos establecidos";
					$aforo = ""; 
				}


				// Visada debe ser 0 o 1
				if (!($visada == 0 || $visada == 1))
				{
					$errores["visada"] = "Campo visada debe ser 0 o 1";
					$visada = ""; 
				}


				// Fecha debe tener el formato adecuado y posterior a la fecha de creación 
				if (!validarFecha($fecha, 'Y-m-d\TH:i'))
				{
					$errores["fecha"] = "Campo fecha no cumple los requisitos establecidos";
					$fecha = ""; 
				}
		        
		       
			}
		    
    	} 
    	
  	?>

  	<?php
  		//Si hay errores, pintarlos en el correspondiente campo:
		  if (!$comprobarConexion || count($errores) > 0):
			{ 
  	?>
  		<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
	    	<input type="hidden" name="id" value="<?php echo $oferta ?>">
			<p>
	            <!-- Campo usuario -->
				<label for="ofertante">ID ofertante</label>
	            <input type="number" name="ofertante" placeholder="ID ofertante" value="<?php echo $ofertante ?>">
	            <?php
	            	if (isset($errores["ofertante"])):
	            ?>
	            	<p class="error"><?php echo $errores["ofertante"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>

	    	<p>
	            <!-- Campo nombre -->
				 <label for="nombre">Nombre</label>
	            <input type="text" name="nombre" placeholder="Nombre" value="<?php echo $nombre ?>">
	            <?php
					if (isset($errores["nombre"])):
						{	
	            ?>
	            	<p class="error"><?php echo $errores["nombre"] ?></p>
	            <?php
						}
	            	endif;
	            ?>
	        </p>
			<p>				
	            <!-- Campo descripción de la oferta -->
				<label for="descripcion">Descripción</label>
	            <textarea name="descripcion" rows="10" cols="50" placeholder="Descripcion" value="<?php echo $descripcion ?>"><?php echo $descripcion ?></textarea>
	        </p>
			<p>
				<!-- Campo fecha actividad -->
				<label for="fecha">Fecha Actividad</label>	            
	            <input type="datetime-local" name="fecha" value="<?php echo $fecha ?>">
	            <?php
	            	if (isset($errores["fecha"])):
	            ?>
	            	<p class="error"><?php echo $errores["fecha"]?></p>
	            <?php
	            	endif;
	            ?>
	        </p>

			<p>
	            <!-- Campo visada -->
				 <label for="visada">Visada</label>
	            <select id="visada" name="visada">
	            	<option value="">Seleccione Visada</option>
					<option value="0" <?php echo 0 == $visada ? "selected" : "" ?>>0</option>
					<option value="1" <?php echo 1 == $visada ? "selected" : "" ?>>1</option>
				</select>
	            <?php
	            	if (isset($errores["visada"])):
	            ?>
	            	<p class="error"><?php echo $errores["visada"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>

			<p>
	            <!-- Campo aforo -->
				 <label for="aforo">Aforo</label>
	            <input type="number" name="aforo" placeholder="Aforo" value="<?php echo $aforo ?>">
	            <?php
	            	if (isset($errores["aforo"])):
	            ?>
	            	<p class="error"><?php echo $errores["aforo"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>
			<p>
	            <!-- Campo categoría -->
				 <label for="categoria">Categoria</label>
	            <select id="categoria" name="categoria_id">
	            	<option value="">Seleccione Categoria</option>
	            <?php
				//Conectar a la base de datos para tomar los posibles valores de las categorias.
	            	$conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

				//Usamos un SELECT para traer los valores del id y la categoria 
				//Obtenemos el resultado de la consulta con la función "resultadoConsulta($conexion, $consulta)"

				$consulta = "SELECT * FROM categorias";	            	
	            $resultado = resultadoConsulta($conexion, $consulta);

					while ($row = $resultado->fetch(PDO::FETCH_ASSOC)):
  				?>
  					<option value="<?php echo $row["id"]; ?>" <?php echo $row["id"] == $categoria ? "selected" : ""?>><?php echo $row["categoria"]; ?></option>
  				<?php
  					endwhile;

  					$consulta = null;
        			$conexion = null;
  				?>
  				</select>
  				
	            <?php
	            	if (isset($errores["categoria"])):
	            ?>
	            	<p class="error"><?php echo $errores["categoria"] ?></p>
	            <?php
	            	endif;
	            ?>
	        </p>
	        <p>
	            <!-- Botón submit -->
	            <input type="submit" value="Guadar">
	        </p>
	    </form>
  	<?php
		// Si no hay errores
		}
  		else:
  			//Nos conectamos a la BBDD

			  $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
			
			// Creamos una variable con la consulta "UPDATE" a ejecutar

			$consulta = "UPDATE ofertas SET usuario_id= :ofertante, nombre= :nombre, descripcion= :descripcion, categoria_id= :categoria, 
							fecha_actividad= :fecha, aforo= :aforo, visada= :visada, updated_at= :fecha_modif
  							WHERE id = :id";
			
			// preparamos la consulta (bindParam)

			$resultado = $conexion->prepare($consulta);

			$resultado->bindParam(":ofertante", $ofertante);
			$resultado->bindParam(":nombre", $nombre);
			$resultado->bindParam(":descripcion", $descripcion);
			$resultado->bindParam(":categoria", $categoria);
			$resultado->bindParam(":fecha", $fecha);
			$resultado->bindParam(":aforo", $aforo);
			$resultado->bindParam(":visada", $visada);
			$resultado->bindParam(":fecha_modif", $fechaActual);
			$resultado->bindParam(":id", $oferta);

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

			$conexion = null;

        	// redireccionamos al listado 
  			header("Location: listados.php");
  			
    	endif;
    ?>
    <div class="contenedor">
        <div class="enlaces">
            <a href="listados.php">Volver al listado</a>			
			<p>
				<a href="../ControlAcceso/cerrar-sesion.php">Cerrar sesión</a>
			</p>
        </div>
   	</div>
    
</body>
</html>

<?php
    }   
        //cierre del if inicial
    ?>
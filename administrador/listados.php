<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
// Activa las sesiones
session_name("sesion-privada");
session_start();
// Comprueba si existe la sesión "email", en caso contrario vuelve a la página de inicio
if (!isset($_SESSION["email"])){
    header("Location: ../index.php");
} else{
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
    $nombre = $resultado["nombre"];

    // Comprueba que el rol sea "Admin", en caso contrario vuelve a la página inicial
	if ($perfil !== 1) {
		header("Location: ../index.php");
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
    <title>Vista Administrador</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../css/estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">    
</head>
<body>
    <header class="sticky-top bg-white shadow-sm">
		<nav class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
			<a href='../index.php'class="btn btn-dark">Inicio</a>
		<?php if ($email != ""): ?>
			<div class="text-end">
				<span class="me-3 fw-bold">Bienvenido/a <?php echo $nombre ?></span>
				<a href="../ControlAcceso/cerrar-sesion.php"  class="btn btn-danger">Salir</a>
			</div>
		<?php endif; ?>
		</nav>
	</header>
    <main class="container">
        <section class="my-5">
            <h2 class='text-primary-emphasis'>Fotos pendientes de validación</h2>
            <?php
			//fotos pendientes de validación
            $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);

			$select = "SELECT * FROM fotos f, usuarios u, rally r 
            WHERE f.usuario_id=u.id_usuario AND f.rally_id=r.id_rally
            AND estado = 'pendiente' ORDER BY f.fecha asc";

			$consulta = $conexion->query($select);

			$consulta->execute();

			// comprobamos si algún registro 
			if ($consulta->rowCount() == 0)
			{
				echo "<h5>Ninguna foto pendiente de validar</h5>" . PHP_EOL;
			}else{
                echo "<div class='row g-4'>" . PHP_EOL;
				while ($resultado = $consulta->fetch(PDO::FETCH_ASSOC)) {
                    echo "<div class='col-lg-4 col-md-6 col-12'>" . PHP_EOL;
					    echo "<article class='card h-100 border'>" . PHP_EOL;
                            echo "<img src='../$resultado[url]' class='card-img-top img-card' alt='Foto $resultado[id_foto]' onclick='mostrarImagen(\"../$resultado[url]\")'></img>" . PHP_EOL;
                            echo "<div class='card-body d-flex flex-column'>" . PHP_EOL;
                                echo "<p class='card-text'><strong>Usuario $resultado[id_usuario]</strong>: $resultado[nombre] $resultado[apellidos]</p>" . PHP_EOL;
                                echo "<p class='card-text'><strong>Rally</strong> $resultado[titulo]</p>" . PHP_EOL;
                                echo "<div class='mt-auto d-flex justify-content-evenly'>" . PHP_EOL;
                                    echo "<a href='revisar.php?id=$resultado[id_foto]&validar=1' class='btn btn-success'>Validar</a>" . PHP_EOL;
                                    echo "<a href='revisar.php?id=$resultado[id_foto]&validar=0' class='btn btn-danger'>Rechazar</a>" . PHP_EOL;
                                echo "</div>". PHP_EOL;
                            echo "</div>". PHP_EOL;
					    echo "</article>" . PHP_EOL;
                    echo "</div>". PHP_EOL;	
				}
                echo "</div>" . PHP_EOL;					
			}			
			?>
        </section>
        <section class="my-5">
            <div style="width: 100%; max-width: 900px;">
                <div class='d-flex align-items-center gap-3 mt-2'>
                    <h2 class="d-inline-block text-primary-emphasis">Rallies</h2>
                    <a href="../rally/nuevo.php" class='btn btn-outline-primary'>Nuevo rally</a> 
                </div> 
                <table  class="table table-borderless align-middle">
                    <tbody class="border-top">
                <?php

                    $conexion = conectarPDO($host, $user, $passwordBD, $bbdd);
            
                    $consulta = "SELECT r.*, count(i.usuario_id) as registrados FROM rally r
                    LEFT JOIN inscripciones i ON r.id_rally = i.rally_id GROUP BY r.id_rally";
            
                    $resultado = resultadoConsulta($conexion, $consulta);

                    while ($registro = $resultado->fetch()) {
                    echo "<tr class='border-top'>" . PHP_EOL;
                        echo "<td class='pb-1'>
                                <span class='fs-5'><strong>Rally</strong> $registro[titulo]</span> <br>
                                <span class='fst-italic'>" . formatoFecha($registro["fecha_ini"]) . " | " . formatoFecha($registro["fecha_fin"]) . " </span>
                            </td>" . PHP_EOL;

                        echo "<td class='text-center' rowspan='2'>
                                <div class='d-flex flex-column flex-lg-row gap-2 justify-content-center'>
                                    <a href='../rally/modificar.php?rally=$registro[id_rally]' class='btn btn-secondary'>Modificar</a>
                                    <button onclick='confirmarBorrado(\"../rally/eliminar.php?rally=$registro[id_rally]\")' class='btn btn-danger'>Eliminar</button>
                                    <a href='../rally/rally.php?rally=$registro[id_rally]' class='btn btn-dark'>Ir</a>
                                </div>
                            </td>" . PHP_EOL;
                    echo "</tr>" . PHP_EOL;

                    echo "<tr>" . PHP_EOL;
                        echo "<td class='pt-0 small'>      
                                <strong>Localidad:</strong> $registro[localidad]                     
                                <strong>Límite:</strong> $registro[participantes] |
                                <strong>Inscritos:</strong> $registro[registrados]
                            </td>" . PHP_EOL;
                    echo "</tr>" . PHP_EOL;
                    }

                ?>    
                    </tbody>
                </table>
            </div>
        </section>
        <section class="my-5">            
            <div style="width: 100%; max-width: 900px;">
                <div class='d-flex align-items-center gap-3 mt-2'>
                    <h2 class="d-inline-block text-primary-emphasis">Usuarios</h2>
                    <a href="nuevoUsuario.php" class='btn btn-outline-primary'>Nuevo usuario</a> 
                </div> 
                <table class="table table-borderless align-middle">
                    <tbody class="border-top">
                <?php
            
                    $consulta = "SELECT id_usuario, nombre, apellidos, email, tfno, fecha, img FROM usuarios WHERE rol_id = 2";
            
                    $resultado = resultadoConsulta($conexion, $consulta);

                    while ($registro = $resultado->fetch()) {
                        echo "<tr class='border-top'>" . PHP_EOL;
                        echo "<td class='pb-1 fs-5'><strong>Usuario$registro[id_usuario] $registro[nombre] $registro[apellidos]</strong></td>";
                        if ($registro["img"] != null) {
                            echo "<td rowspan='2'><img class='avatar' src='../$registro[img]' alt='Foto perfil'/></td>" ;
                        }else{
                            echo "<td rowspan='2'><img class='avatar' src='../img/avatar.svg' alt='Foto perfil'/></td>" ; 
                        }                         
                        echo "<td class='text-center' rowspan='2'> 
                                <div class='d-flex flex-column flex-lg-row gap-2 justify-content-center'>   
                                    <a class='btn btn-secondary' href='modificarUsuario.php?id=$registro[id_usuario]'>Modificar</a>
                                    <button class='btn btn-danger' onclick='confirmarBorrado(\"eliminarUsuario.php?id=$registro[id_usuario]\")'>Eliminar</button>
                                </div>
                            </td>" . PHP_EOL;
                        echo "</tr>". PHP_EOL;
                        echo "<tr>" . PHP_EOL;
                            echo "<td class='fst-italic'>$registro[email] - $registro[tfno]</td>" . PHP_EOL;
                        echo "</tr>". PHP_EOL;
                    }

                ?>    
                    </tbody>
                </table>
            </div>
        </section>
        <!-- Modal para imagen ampliada -->
		<div class="modal fade" id="modalImagen" tabindex="-1">
			<div class="modal-dialog modal-dialog-centered modal-lg">
				<div class="modal-content bg-transparent border-0">
				<div class="modal-body d-flex justify-content-center align-items-center p-0">
					<img id="imagenAmpliada" src=""  alt="Imagen ampliada">
				</div>
				</div>
			</div>
		</div>
    </main>    
    <?php
            // Libera el resultado y cierra la conexión
    
        $resultado = null;
        $conexion = null;

        include '../utiles/footer.php';
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
<script>
    //confirmar
	function confirmarBorrado(url) {
		if (confirm("¿Estás seguro de que deseas eliminar?")) {
			// Si el usuario hace clic en "Aceptar", redirige a la URL de eliminación
			window.location.href = url;
		}
	}

    //funcion abre modal imagen
	function mostrarImagen(src) {
		const modal = new bootstrap.Modal(document.getElementById('modalImagen'));
		document.getElementById('imagenAmpliada').src = src;
		modal.show();
	}   
</script>
</html>

<?php
    }   
        //cierre del if inicial
    ?>
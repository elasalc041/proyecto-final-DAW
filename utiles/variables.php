<?php

	// Definición de variables
	//$host = "172.26.104.205";
    //casa
    $host = "localhost";
	$user = "pepito01";
	$passwordBD = "pepito01";
	$bbdd = "rallies_fotos";


	$headerJSON = 'Content-Type: application/json';
    $codigosHTTP = [ 
        "200" => "HTTP/1.1 200 OK",
        "201" => "HTTP/1.1 201 Created",
        "202" => "HTTP/1.1 202 Accepted",
        "400" => "HTTP/1.1 400 Bad Request",
        "404" => "HTTP/1.1 404 Not Found",
        "500" => "HTTP/1.1 500 Internal Server Error"
    ];


    $fechaActual = date('Y-m-d H:i:s');


?>
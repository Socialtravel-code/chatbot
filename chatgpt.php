<?php
date_default_timezone_set('America/Argentina/Mendoza');
header('Content-Type: text/html; charset=UTF-8');
require 'vendor/autoload.php';
use Chatbot\Chatgpt\ChatgptClass;//declaro la clase Chatgpt que es la encargada de hacer las consultas a la API de openAI



if ($_SERVER['REQUEST_METHOD'] === 'POST') {//este método viene de la vesion anterior TODO: sería bueno implementar un método donde chatgpt se encatge de acuerdo a lo que llegue de la vista buscar las palabras para ejecutar las acciones

   $inputUsuario = $_POST['pregunta'];
   
   $pClaveReservar = ["hacer reserva", "iniciar reserva", "empezar reserva", "realizar reserva", "reservar", "cabañas", "crear cabañas", "habitacion", "caja", "categorias", "reservaciones"];
   
   $pClaveContinuarReservar = ["continuar reserva", "seguir reserva", "reanudar reserva", "completar reserva"];
   foreach ($pClaveReservar as $palabra) { //TODO: SOLO MODIFICO LA FUNCIÓN QUE ESTÁ DESTINADA A RESERVA POR EL MOMENTO, EN FUTURAS VERSIONES SERÁ INCLUIR EN UN SWITCH LA $palabra Y LLAMAR A LA BD
      
      if (strpos($inputUsuario, $palabra) !== false) {//busco la palabra que viene de la vista dentro de un array e inicio la ejeucion de la aplicación 
         
         //var_dump($busqueda->showDataBases());   
         //$intencionDetectada = true;
         $inputUsuario = mb_convert_encoding($inputUsuario, "UTF-8");
         //die($inputUsuario);
         $reserva = new ChatgptClass($inputUsuario,"reservaciones");//la clase chatgptclass es el punto de inicio de esta aplicacion 
         
      }
   }
}




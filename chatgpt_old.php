<?php

use Chatbot\ControladorBusqueda;
use Chatbot\ControladorPersistencia;

include('intencion_continuarReserva.php');
include('intencion_continuarDeposito.php');
include('intencion_continuarPago.php');
include('insertReserva.php');
include('insertDeposito.php');
include('insertPago.php');
session_start();

header('Content-Type: text/html; charset=utf-8');

//Conexion a masalojamiento
function getDBConnection() {
    $host = 'www.masalojamientos.com';
        $database = 'miembro_12';
        $username = 'santi';
        $password = 'websoft';

    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset("utf8");
    if ($conn->connect_error) {
        die ("No se pudo conectar a la base de datos");
        //die('Connection failed: ' . $conn->connect_error);
    }
    else{
        //echo "Conectado";
    }

    return $conn;
}

//Nos sirve en caso de testear y queremos limpiar sessions
function borrarSessions() {
    echo "limpiando";
    unset($_SESSION['variables']["reserva"]);
    unset($_SESSION['camposFaltantesReserva']);
}


function preguntaChatgpt($pregunta){
    //API KEY DE CHATGPT
    $apiKey='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';

    //INICIAMOS LA CONSULTA DE CURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer '.$apiKey,
    ]);

    //INICIAMOS EL JSON QUE SE ENVIARA A META
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{
        \"model\": \"text-davinci-003\",
        \"prompt\": \"".$pregunta."\",
        \"max_tokens\": 3000,
        \"temperature\": 0.1
    }");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //OBTENEMOS EL JSON COMPLETO CON LA RESPUESTA
    $response = curl_exec($ch);
    
    if ($response === false) {
        echo 'Error en la solicitud cURL: ' . curl_error($ch);
    } else {
        //var_dump($response); // Imprimir la respuesta para depuración
    }
    curl_close($ch);
    $decoded_json = json_decode($response, false);
    //RETORNAMOS LA RESPUESTA QUE EXTRAEMOS DEL JSON
    return  $decoded_json->choices[0]->text;    
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //$conn = getDBConnection();
    include_once "database/ControladorPersistencia.php";
    $conn = new ControladorPersistencia();

    var_dump($_POST['pregunta']);

    //detectarIntencion($_POST['pregunta']);
    
    
    $inputUsuario = $_POST['pregunta'];

    //Reiniciar Sessions
    $pBorrarS="borrar";
    //Reserva
    $pClaveReservar = ["hacer reserva", "iniciar reserva","empezar reserva","realizar reserva","reservar", "cabañas","crear cabañas","habitacion","caja","categorias","reservaciones"];
    $pClaveContinuarReservar = ["continuar reserva", "seguir reserva","reanudar reserva","completar reserva"];
    //Depositar
    $pClaveDepositar = ["hacer deposito","depositar"]; 
    $pClaveContinuarDepositar = ["continuar deposito", "seguir deposito","reanudar deposito","completar deposito"];
    //Pago
    $pClavePago = ["hacer pago","iniciar pago","empezar pago","realizar pago","pagar"]; 
    $pClaveContinuarPagar = ["continuar pago", "seguir pago","reanudar pago","completar pago"];
    //Disponibilidad
    $pClaveDisponibilidad = ["consultar disponibilidad", "disponibilidad"];


    $intencionDetectada = false;

    

    //Limpiar sessions
    if(strpos($inputUsuario,$pBorrarS)!== false){
        $intencionDetectada=true;
        borrarSessions();
    }


    //Continuar Pago -En Proceso
    foreach ($pClaveContinuarPagar as $palabra) {
        if (strpos($inputUsuario, $palabra) !== false) {
            $intencionDetectada = true;
            echo "Entro a continuar pago";
        }

    }

    //Realizar Pago
    foreach ($pClavePago as $palabra) {
        if (strpos($inputUsuario, $palabra) !== false) {
            $intencionDetectada = true;
            echo "Entro a pagar";
            $contexto = "Eres un creador de json. Solo usa los valores que te pase, en el caso de no que no te pase los valores vacios, por favor no inventes ningun valor. Haz un json con los siguientes detalles: numero_reserva,cantidad_pago,razon_pago, ingreso_egreso, enviar_voucher";

            $inputUsuario = $contexto . " " . $inputUsuario;

            
            $respuesta = preguntaChatgpt($inputUsuario);
            $respuesta = preg_replace('/^[^{\[]*|[^}\]]*$/', '', $respuesta);
            $pagarData =json_decode($respuesta);  
            print_r($pagarData);
            if ($pagarData === null) {
                echo "<br>Error decoding JSON response from OpenAI API.";
            } else {
                $_SESSION['variables']["pago"] = $pagarData;

            
                $camposFaltantes = array();

                if (empty($pagarData->numero_reserva)) {
                    $camposFaltantes[] = "numero_reserva";
                }
                
                
                if (empty($pagarData->cantidad_pago)) {
                    $camposFaltantes[] = "cantidad_pago";
                }

                if (empty($pagarData->razon_pago)) {
                    $camposFaltantes[] = "razon_pago";
                }

                if (empty($pagarData->ingreso_egreso)) {
                    $camposFaltantes[] = "ingreso_egreso";
                }
                
                if (!empty($camposFaltantes)) {
                    $_SESSION['camposFaltantesPago'] = $camposFaltantes;
                    echo "<br>Faltaron los siguientes campos: " . implode(", ", $camposFaltantes);
                   
                    
                }
                else{
                    if($pagarData->ingreso_egreso!="ingreso"&&$pagarData->ingreso_egreso!="egreso"){
                        echo "<br>Especifique si es ingreso o egreso por favor...";
                        $camposFaltantes[] = "ingreso_egreso";
                        $_SESSION['camposFaltantesPago'] = $camposFaltantes;
                    }
                    else{
                        insertPago($conn,$pagarData);
                    }
                }
            }
        }

    }

    //Continuar Deposito
    foreach ($pClaveContinuarDepositar as $palabra) {
        if (strpos($inputUsuario, $palabra) !== false) {
            $intencionDetectada = true;
            
            continuarDeposito($inputUsuario,$conn);
        }

    }

    //Realizar Deposito
    foreach ($pClaveDepositar as $palabra) {
        if (strpos($inputUsuario, $palabra) !== false) {
            $intencionDetectada = true;
            echo "Entro a depositar";
            $contexto = "Eres un creador de json. Solo usa los valores que te pase, en el caso de no que no te pase los valores vacios, por favor no inventes ningun valor. Haz un json con los siguientes detalles: numero_reserva,cantidad_deposito";

            $inputUsuario = $contexto . " " . $inputUsuario;

            
            $respuesta = preguntaChatgpt($inputUsuario);
            $respuesta = preg_replace('/^[^{\[]*|[^}\]]*$/', '', $respuesta);
            $reservaData =json_decode($respuesta);  
   
            if ($reservaData === null) {
                echo "<br>Error decoding JSON response from OpenAI API.";
            } else {
                $_SESSION['variables']["deposito"] = $reservaData;

            
                $camposFaltantesDeposito = array();

                if (empty($reservaData->numero_reserva)) {
                    $camposFaltantesDeposito[] = "numero_reserva";
                }
                
                
                if (empty($reservaData->cantidad_deposito)) {
                    $camposFaltantesDeposito[] = "cantidad_deposito";
                }
                
                
                if (!empty($camposFaltantesDeposito)) {
                    $_SESSION['camposFaltantesDeposito'] = $camposFaltantesDeposito;
                    echo "<br>Faltaron los siguientes campos: " . implode(", ", $camposFaltantesDeposito);
                   
                    
                }
                else{
                    insertDeposito($conn,$reservaData);
                }
            }
        }

    }

    //Continuar reserva
    foreach ($pClaveContinuarReservar as $palabra) {
        
        if (strpos($inputUsuario, $palabra) !== false) {
            $intencionDetectada = true;
            continuarReserva($inputUsuario,$conn);
        }

    }

    //Realizar Reserva
    foreach ($pClaveReservar as $palabra) { //TODO: SOLO MODIFICO LA FUNCIÓN QUE ESTÁ DESTINADA A RESERVA POR EL MOMENTO, EN FUTURAS VERSIONES SERÁ INCLUIR EN UN SWITCH LA $palabra Y LLAMAR A LA BD
        
        if (strpos($inputUsuario, $palabra) !== false) {
            include_once 'database/ControladorBusqueda.php';//incluyo el controlador encargado de realizar las funciones basicas como busqueda insersion y eliminacion 
            $busqueda= new ControladorBusqueda();//instancio objeto de la clase controlador busqueda
            $palabra = 'cabañas';// hardcode
            $objeto = $busqueda->metaCompleto($palabra); // cargo en mi objeto la metadata completa que obtengo al consultar la BD 
            $consulta = "";        // variable para incluir en la consulta a chatgpt
            foreach ($objeto as $key => $value) {// en este foreach recorro el array para incluir la cabecera de las tablas, en la siguiente line también incluyo metadata correspondiente a datos nulos o no
                $consulta .= $key.' = '; 
                $consulta .= ($value == 'NO')?$value.', ': 'puede ser Nulo,';
            }
            echo "Entro a reservar";
            $intencionDetectada = true;

            $contexto  = "Eres un creador de json. Solo usa los valores que te envio, "; 
            $contexto .=" en el caso de notar que alguno de los campos vacios, por favor no inventes ningun valor. tampoco inventes valores si te pido algo de manera coloquial ";
            $contexto .="Haz un json con los siguientes campos(ten presente que si dice que no Puede ser nulo no debes inventar ningun valor y preguntarme para que complete el campo especifico): "; 
            $contexto .= $consulta;
            $contexto .=" de ninguna manera debes inventar valores, Si te paso la fecha muestrala en formato YYYY-mm-dd teniendo en cuenta la fecha de hoy ".date('Y-m-d')." pero si en los detalles no especifico fecha no la utilices en ningun lugar";

            $inputUsuario = $contexto . "  " . $inputUsuario;

            echo $inputUsuario;
                $respuesta = preguntaChatgpt($inputUsuario);
                $respuesta = preg_replace('/^[^{\[]*|[^}\]]*$/', '', $respuesta);
                
                $reservaData =json_decode($respuesta);  

                $busqueda->guardar(get_object_vars($reservaData),$palabra);// Convierto en un array los datos para que se envien al controlador para guardarlos en la BD

                /*if ($reservaData === null) {
                    echo "<br>Error decoding JSON response from OpenAI API.";
                } else {
                    $_SESSION['variables']["reserva"] = $reservaData;
                }              

                $camposFaltantes = array();

                if (empty($reservaData->cantidad_personas_mayores)) {
                    $camposFaltantes[] = "mayores";
                }
                
                
                if (empty($reservaData->categoria_cabana)) {
                    $camposFaltantes[] = "categoria";
                }
                
                if (empty($reservaData->fecha_inicio)) {
                    $camposFaltantes[] = "fecha_inicio";
                }
                
                if (empty($reservaData->fecha_fin)) {
                    $camposFaltantes[] = "fecha_fin";
                }
                
                if (empty($reservaData->cantidad_personas)) {
                    $camposFaltantes[] = "cantidad_personas";
                }
                
                if (empty($reservaData->nombre)) {
                    $camposFaltantes[] = "nombre";
                }
                
                if (!empty($camposFaltantes)) {
                    $_SESSION['camposFaltantesReserva'] = $camposFaltantes;
                    echo "<br>Faltaron los siguientes campos: " . implode(", ", $camposFaltantes);
                    //echo "\nUn ejemplo: Hacer una reserva para Juan Lopez con un precio de cabaña de 1000, Dni 38456125, Email juanlopez@gmail.com, Celular 2618453698, desde el 4 de octubre de 2023 hasta el 5 de octubre para un grupo de 3 personas, compuesto por 2 adultos y 1 menor, con 1 matrimonio. La reserva es para la categoría Especiales";
                    
                }else{
                    insertReserva($reservaData,$conn);
                }*/

                break;
            
        }
    }
        

    //Metodo en -proceso
    foreach ($pClaveDisponibilidad as $palabra) {
        if (strpos($inputUsuario, $palabra) !== false) {
            $intencionDetectada = true;

            $contexto = "Eres el asistente de reservas de Cabañas. Ya te pasó los datos a generar un json con esas variables, json: con los siguientes detalles: categoria, cantidad_personas, fecha_inicio y fecha_fin. Pasar fecha formato dd-mm-YYYY con año actual " . date("Y");
            
            $inputUsuario = $contexto . " " . $inputUsuario;

            if ($intencionDetectada) {
                $respuesta=preguntaChatgpt($inputUsuario);  
                
                $disponibilidadData = json_decode($respuesta, true);

                if ($disponibilidadData === null) {
                    echo "Error decoding JSON response from OpenAI API.";
                } else {
                    $_SESSION['variables']["disponibilidad"] = $disponibilidadData;
                }

                print_r($_SESSION['variables']["disponibilidad"]);
                //empty($_SESSION['variables']["disponibilidad"]["categoria"]) || 
                if(empty($_SESSION['variables']["disponibilidad"]["mayores"])||empty($_SESSION['variables']["disponibilidad"]["menores"])||empty($_SESSION['variables']["disponibilidad"]["fecha_inicio"])|| empty($_SESSION['variables']["disponibilidad"]["fecha_fin"])|| empty($_SESSION['variables']["disponibilidad"]["cantidad_personas"])){
                    echo "\nFaltaron datos";
                    echo "\nUn ejemplo: consultar disponibilidad para el dia 4 de octubre hasta el 10 de octubre para 5 personas de la categoria especiales";
                    
                }else{
                    echo "Consultando disponibilidad......";
                    
                    $categoria = $_SESSION['variables']["disponibilidad"]["categoria"];
                    $fechaInicio = $_SESSION['variables']["disponibilidad"]["fecha_inicio"];
                    $fechaFin = $_SESSION['variables']["disponibilidad"]["fecha_fin"];// Aquí debes obtener la fecha de fin proporcionada por el cliente
                    $fechaInicio = $_SESSION['variables']["disponibilidad"]["fecha_inicio"];
                    $cabana_reserv = $_SESSION['variables']["disponibilidad"]["cantidad_personas"]; // Aquí debes obtener el número de personas proporcionado por el cliente 
                    $url = "https://www.masalojamientos.com/soft/disponibilidad_json_date.php?idm=12&fi=$fechaInicio&ff=$fechaFin";
                    echo $url;
                    // Inicializa la sesión cURL
                    $ch = curl_init($url);
                    
                    // Configura las opciones de cURL
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    
                    // Ejecuta la solicitud y obtén la respuesta
                    $response = curl_exec($ch);
                    
                    // Cierra la sesión cURL
                    curl_close($ch);
                    
                    // Verifica si se obtuvo una respuesta
                    if ($response === false) {
                        echo "<br>Error en la solicitud cURL: " . curl_error($ch);
                    } else {
                        // Decodifica el JSON
                        $data = json_decode($response, true);
                    
                        // Verifica si la respuesta está vacía
                        if (empty($data)) {
                            echo "<br>No hay lugares disponibles para las fechas indicadas.";
                        } else {
                            // Muestra los datos JSON con un echo
                            echo "<br>Datos disponibles:\n";
                            echo "<br>".json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }
                    }                    
                    //$sql = "SELECT COUNT(*) AS count FROM reservaciones WHERE desde_reserv >= '$fechaInicio' AND hasta_reserv <= '$fechaFin' AND cabana_reserv = $cabana_reserv";
                    /*$sql = "SELECT COUNT(*) AS count FROM reservaciones WHERE desde_reserv >= '$fechaInicio' AND hasta_reserv <= '$fechaFin'";
                    $result = $conn->query($sql);

                    if ($result) {
                        $row = $result->fetch_assoc();
                        $count = $row['count'];
                        if($count!=0){
                            echo "Tenemos " . $count . " cabañas ocupadas para las fechas indicadas y para " . $cabana_reserv . " personas.";
                        }
                        
                    } else {
                        echo "Error en la consulta: " . $conn->error;
                    }*/
                }
                break;
            }
        }

    }
    if($intencionDetectada==false){
        echo "No se ha detectado ninguna intencion. Por favor intente de nuevo con las palabras clave";
    }

}


function detectarIntencion($intencion)
{
    $pregunta = 'Qué entiendes si te digo lo siguiente: \"'. $intencion;
    $pregunta .= '\" con esta interpretacion podrías armar un array clave valor de la siguiente forma [\"intencion\"=>\"solo la accion que realizo\", \"sujeto\"=>\"sobre quien quiero realizar la accion pero solo el sujeto si hay mas datos desestimarlos\"]';
    $pregunta .= ' y devolverme ese array';
    echo $pregunta;
    $array = preguntaChatgpt($pregunta);
    var_dump($array);
    die($array);
}
?>
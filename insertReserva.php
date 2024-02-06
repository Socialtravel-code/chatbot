<?php
function insertReserva($reservaData, $conn) {
    try {

        //Convertimos en DateTime las fechas
        $desde_reserv = $reservaData->fecha_inicio;
        $hasta_reserv = $reservaData->fecha_fin;
        $fecha_desde = new DateTime($desde_reserv);
        $fecha_hasta = new DateTime($hasta_reserv);

        //En caso de que fecha desde sea mayor a fecha hasta indicaremos que estan mal las fechas y se debe realizar la intencion continuar reserva para poder terminar la reserva
        if ($fecha_desde > $fecha_hasta) {
            $camposFaltantes = array();
            $camposFaltantes[] = "fecha_inicio";
            $camposFaltantes[] = "fecha_fin";
            $_SESSION['camposFaltantesReserva'] = $camposFaltantes;
            echo "<br>La fecha de inicio es posterior a la fecha de fin. Por favor, selecciona fechas válidas.";
            echo "<br>Ingrese nuevamente las fechas por favor...";
        }
        else{

            $matrimonio = isset($reservaData->cantidad_matrimonios) ? $reservaData->cantidad_matrimonios : 0;
            $nombreCliente = $reservaData->nombre;
            $dniCliente = empty($reservaData->dni) ? 0 : $reservaData->dni;
            $celularCliente = $reservaData->celular;
            $emailCliente = $reservaData->email;
            $numClienteExistente = null;

            // Check if a client with the given dni_cli exists
            if($dniCliente!=0){
                $sqlClientDni = "SELECT num_cli FROM clientes WHERE dni_cli='$dniCliente' LIMIT 1";
                $resultClienteExistente = mysqli_query($conn, $sqlClientDni);
                if ($resultClienteExistente !== false && $resultClienteExistente !== null) {
                    $numClienteExistente = mysqli_fetch_assoc($resultClienteExistente);
                    $numClienteExistente =$numClienteExistente['num_cli'];
                    if ($numClienteExistente) {
                        echo "<br>Cliente existente con ese DNI *$numClienteExistente*";
                    } 
                }
                else {
                    $numClienteExistente = insertCliente($conn, $nombreCliente, $dniCliente, $celularCliente, $emailCliente);
                }

            }else {
                $numClienteExistente = insertCliente($conn, $nombreCliente, $dniCliente, $celularCliente, $emailCliente);
            }
            

            //Traemos los valores de la reserva en las siguientes variables
            $num_cli = $numClienteExistente ? $numClienteExistente : $conn->insert_id;

            $cantidadPersonas = $reservaData->cantidad_personas;
            $mayores = $reservaData->cantidad_personas_mayores;
            $menores = $reservaData->cantidad_personas_menores;

            //Pasamos a float el precio
            $precio_cabana = isset($reservaData->precio_cabana) ? $reservaData->precio_cabana : 0;
            $precio_cabana = floatval($precio_cabana);



            
            // Calcula la diferencia en días
            $intervalo = $fecha_desde->diff($fecha_hasta);
            $dias = $intervalo->days;
            $dias += 1;
            $total_pagar = $precio_cabana * $dias;

            //GrupoN representa un codigo que se genera en el momento a traves de la hora actual
            $grupoN = md5(date("Y-m-d H:i:s"));

            //Sql Reserva
            $sqlReserv = "INSERT INTO reservaciones (num_cli, matrimonios, mayores, menores, desde_reserv, hasta_reserv, dias_reserv, cabana_reserv, fecha_contacto, `precio_cabaña`, total_pagar, queda_pagar, estado, obs_reserv, grupo, personas_grupo) VALUES ('$num_cli','$matrimonio','$mayores','$menores', '$desde_reserv', '$hasta_reserv', '$dias', '27', NOW(), '$precio_cabana','$total_pagar','$total_pagar', 'prereserva', 'Reserva creada desde Chatbot','$grupoN','$cantidadPersonas')";

            if ($conn->query($sqlReserv) === TRUE) {
                echo "<br>Reservación creada con éxito.";
                //Limpiamos las sessions
                unset($_SESSION['variables']["reserva"]);
                unset($_SESSION['camposFaltantesReserva']);
            } else {
                echo "<br>Error en la inserción de la reservación: " . $conn->error;
            }
            
        }
    } catch (Exception $e) {
        echo "\nError: " . $e->getMessage();
    }
}

function insertCliente($conn, $nombreCliente, $dniCliente, $celularCliente, $emailCliente) {
    echo "<br>Creando cliente $nombreCliente si no existe";
    $sqlClientInsert = "INSERT INTO clientes (nom_cli, dni_cli, cel_cli, email_cli, observaciones_cli) VALUES ('$nombreCliente', '$dniCliente', '$celularCliente', '$emailCliente', 'Cliente creado desde Chatbot')";

    if ($conn->query($sqlClientInsert) === TRUE) {
        echo "<br>Cliente creado con éxito.";
        return $conn->insert_id; // Return the newly created client's ID
    } else {
        echo "<br>Error en la inserción del cliente: " . $conn->error;
        return false; // Return false to indicate failure
    }
}

?>
<?php
function insertDeposito($conn, $reservaData) {
    $numero_reserva = $reservaData->numero_reserva;
    $cantidad_deposito = $reservaData->cantidad_deposito;

    //Hacemos una consulta a reservaciones para traernos datos necesarios para realizar un deposito
    $sql = "SELECT queda_pagar, grupo, num_cli, cabana_reserv FROM reservaciones WHERE num_reserv = $numero_reserva";
    $resultado = mysqli_query($conn, $sql);

    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);

        if ($fila) {
            $queda_pagar = $fila['queda_pagar'];

            //Indica el monto que falta a pagar
            echo "<br>El monto que falta pagar para la reserva número $numero_reserva es: $queda_pagar";

          
                $grupo = $fila['grupo'];
                $cabana_reserv = $fila['cabana_reserv'];
                $num_cli = $fila['num_cli'];

                //consulta a cabañas para traer el nombre de la reserva a traves de su id
                $sqlNombreCabana = "SELECT nom_cabaña FROM cabañas WHERE id_cabania = $cabana_reserv LIMIT 1";
                $resultado2 = mysqli_query($conn, $sqlNombreCabana);
                $nombreCabana = mysqli_fetch_assoc($resultado2);
                $nombreCabana = $nombreCabana['nom_cabaña'];

                //Calculamos lo que queda a pagar de la reserva
                $queda_pagar_actualizado = $queda_pagar - $cantidad_deposito;

                //GrupoN representa un codigo que se genera en el momento a traves de la hora actual
                $grupoN = md5(date("Y-m-d H:i:s"));

                $sqlDeposito = "INSERT INTO depositos(fecha,num_reserv,concepto,nom_cli,deposito,grupo,cod_ver) VALUES (NOW(), '$numero_reserva', '$nombreCabana', '$num_cli', '$cantidad_deposito', '$grupo', '$grupoN')";

                if ($conn->query($sqlDeposito) === TRUE) {
                    echo "<br>Depósito realizado con éxito.";
                    unset($_SESSION['variables']["deposito"]);
                    unset($_SESSION['camposFaltantesDeposito']);
                } else {
                    echo "<br>Error en la inserción del depósito: " . $conn->error;
                }


                //Actualizamos el queda_pagar de reservaciones para que se refleje el deposito
                $sqlUpdateReserva = "UPDATE reservaciones SET `queda_pagar` = '$queda_pagar_actualizado', `estado` = 'reservado' WHERE num_reserv = $numero_reserva";

                if ($conn->query($sqlUpdateReserva) === TRUE) {
                    echo "<br>Reserva $numero_reserva queda por pagar: $queda_pagar_actualizado";
                } else {
                    echo "<br>Error en la actualización de la reserva: " . $conn->error;
                }
          
        } else {
            echo "<br>No existe la reserva $numero_reserva o esa reserva no tiene nada que pagar.";
        }

        mysqli_free_result($resultado);
    } else {
        echo "<br>Error en la consulta SQL: " . mysqli_error($conn);
    }
}?>
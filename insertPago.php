<?php
function insertPago($conn, $pagoData) {
    $numero_reserva = $pagoData->numero_reserva;
    $cantidad_pago = $pagoData->cantidad_pago;
    $es_ingreso = ($pagoData->ingreso_egreso == "ingreso") ? true : false;
    $razon_pago = $pagoData->razon_pago;
    //SELECT DE LA RESERVA 
    $sql = "SELECT queda_pagar, grupo, num_cli, cabana_reserv FROM reservaciones WHERE num_reserv = $numero_reserva";
    $resultado = mysqli_query($conn, $sql);

    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);

        if ($fila) {
            $queda_pagar = $fila['queda_pagar'];

            //Indica el monto que falta pagar
            echo "<br>El monto que falta pagar para la reserva número $numero_reserva es: $queda_pagar";

                $grupo = $fila['grupo'];
                $cabana_reserv = $fila['cabana_reserv'];
                $num_cli = $fila['num_cli'];

                $queda_pagar_actualizado = $queda_pagar - $cantidad_pago;
                $grupoN = md5(date("Y-m-d H:i:s"));

                //INSERT PAGOS
                $sqlPago = "INSERT INTO pagos(fecha,num_reserv,concepto,nom_cli,pago,grupo,cod_ver) VALUES (NOW(), '$numero_reserva', '$cabana_reserv', '$num_cli', '$cantidad_pago', '$grupo', '$grupoN')";
                echo "<br>".$sqlPago;
                if ($conn->query($sqlPago) === TRUE) {
                    echo "<br>Pago realizado con éxito.";
                    unset($_SESSION['variables']["pago"]);
                    unset($_SESSION['camposFaltantesPago']);
                } else {
                    echo "<br>Error en la inserción del pago: " . $conn->error;
                    exit;
                }
                
                //GrupoN representa un codigo que se genera en el momento a traves de la hora actual
                $grupoN = md5(date("Y-m-d H:i:s"));
                $ingreso=0;
                $egreso=0;    
                if($es_ingreso){
                    $ingreso=$cantidad_pago;
                }
                else{
                    $egreso=$cantidad_pago;
                }
                //INSERT LIBRO DIARIO
                $sqlLibroDiario = "INSERT INTO libro_diario(fecha,num_reserv,num_cli,id_cabania,concepto,ingreso,egreso,cod_ver) VALUES (NOW(), '$numero_reserva', '$num_cli','$cabana_reserv', '$razon_pago', '$ingreso','$egreso', '$grupoN')";
                echo $sqlLibroDiario;

                if ($conn->query($sqlLibroDiario) === TRUE) {
                    echo "<br>Libro diario insertado con exito.";
                } else {
                    echo "<br>Error en la inserción del libro diario: " . $conn->error;
                    exit;
                }
                //ACTUALIZACION QUEDA_PAGAR DE RESERVA
                if($es_ingreso){
                    $sqlUpdateReserva = "UPDATE reservaciones SET `queda_pagar` = '$queda_pagar_actualizado', `estado` = 'reservado' WHERE num_reserv = $numero_reserva";

                    if ($conn->query($sqlUpdateReserva) === TRUE) {
                        echo "<br>Reserva $numero_reserva queda por pagar: $queda_pagar_actualizado";
                    } else {
                        echo "<br>Error en la actualización de la reserva: " . $conn->error;
                    }
                }
          
        } else {
            echo "<br>No existe la reserva $numero_reserva o esa reserva no tiene nada que pagar.";
        }

        mysqli_free_result($resultado);
    } else {
        echo "<br>Error en la consulta SQL: " . mysqli_error($conn);
    }
}?>
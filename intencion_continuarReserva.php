<?php
function continuarReserva($inputUsuario,$conn){

            echo "Entro a continuar reservar";

            if (!empty($_SESSION['camposFaltantesReserva'])) {

                $contexto = "Eres un creador de json. Necesito que el usuario proporcione valores para completar los datos faltantes aunque quiero que solo uses los valores que el te pase, {{en el caso de no qu tengas los campos deja estos vacios}}. Haz un json con los siguientes detalles: " . implode(", ", $_SESSION['camposFaltantesReserva']).". Si te paso alguna fecha muestra esta en formato YYYY-mm-dd";
                $inputUsuario = $contexto . " " . $inputUsuario;


                $respuesta = preguntaChatgpt($inputUsuario);
                $respuesta = preg_replace('/^[^{\[]*|[^}\]]*$/', '', $respuesta);
                $reservaData =json_decode($respuesta);
                if ($reservaData === null) {
                    echo "<br>Error decoding JSON response from OpenAI API.";
                }
                else{
                    $camposFaltantes = array();
                    foreach ($_SESSION['camposFaltantesReserva'] as $campo) {
                        //echo "  *".$reservaData->$campo."*";
                        if ($reservaData->$campo === 0 || $reservaData->$campo === "") {
                            //echo $campo.", ";
                            $camposFaltantes[] = $campo;
                        }
                        else{
                            $_SESSION['variables']["reserva"]->$campo=$reservaData->$campo;
                        }
                    }
                    if (!empty($camposFaltantes)) {
                        unset($_SESSION['camposFaltantesReserva']);
                        $_SESSION['camposFaltantesReserva'] = $camposFaltantes;
                        echo "<br>Faltaron los siguientes campos: " . implode(", ", $camposFaltantes);
                        return $camposFaltantes;
                        //echo "\nUn ejemplo: Hacer una reserva para Juan Lopez con un precio de cabaña de 1000, Dni 38456125, Email juanlopez@gmail.com, Celular 2618453698, desde el 4 de octubre de 2023 hasta el 5 de octubre para un grupo de 3 personas, compuesto por 2 adultos y 1 menor, con 1 matrimonio. La reserva es para la categoría Especiales";
                        
                    }else{
                        print("<br>No hay campos faltantes");
                        //Limpiamos la session de campos faltantes 
                        unset($_SESSION['camposFaltantesReserva']);
                        insertReserva($_SESSION['variables']["reserva"],$conn);
                }
            }
            /*else{
                echo "<br>No hay campos faltantes para continuar una reserva....";
                
            }*/
        }
    }
    
?>
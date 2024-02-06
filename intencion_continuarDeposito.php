<?php
function continuarDeposito($inputUsuario,$conn){

        
            echo "Entro a continuar deposito";

            if (!empty($_SESSION['camposFaltantesDeposito'])) {
               
                $contexto = "Eres un creador de json. En el caso de no que tengas los campos deja estos vacios. Haz un json con los siguientes detalles: " . implode(", ", $_SESSION['camposFaltantesDeposito']);
                $inputUsuario = $contexto . " " . $inputUsuario;
                

                $respuesta = preguntaChatgpt($inputUsuario);
                
                $respuesta = preg_replace('/^[^{\[]*|[^}\]]*$/', '', $respuesta);
                $reservaData =json_decode($respuesta);
                if ($reservaData === null) {
                    echo "<br>Error decoding JSON response from OpenAI API.";
                }
                else{
                    //print_r($reservaData);
                    $camposFaltantes = array();
                    foreach ($_SESSION['camposFaltantesDeposito'] as $campo) {
                        //echo "  *".$reservaData->$campo."*";
                        if ($reservaData->$campo === 0 || $reservaData->$campo === "") {
                            //echo "      entra if  ";
                            //echo $campo.", ";
                            $camposFaltantes[] = $campo;
                        }
                        else{
                            //echo "      entra else  ".$campo;
                            $_SESSION['variables']["deposito"]->$campo=$reservaData->$campo;
                        }
                    }
                    if (!empty($camposFaltantes)) {
                        unset($_SESSION['camposFaltantesDeposito']);
                        $_SESSION['camposFaltantesDeposito'] = $camposFaltantes;
                        echo "<br>Faltaron los siguientes campos: " . implode(", ", $camposFaltantes);
                        return $camposFaltantes;
                        //echo "\nUn ejemplo: Hacer una reserva para Juan Lopez con un precio de cabaña de 1000, Dni 38456125, Email juanlopez@gmail.com, Celular 2618453698, desde el 4 de octubre de 2023 hasta el 5 de octubre para un grupo de 3 personas, compuesto por 2 adultos y 1 menor, con 1 matrimonio. La reserva es para la categoría Especiales";
                        
                    }else{
                     
                        $_SESSION['camposFaltantesDeposito']=null;
                        insertDeposito($conn, $_SESSION['variables']["deposito"]);
                        
                       
                        //return $_SESSION['camposFaltantesDeposito'];
                    }
                }
            }
            else{
                    echo "<br>No hay campos faltantes para continuar una deposito....";
                    
            }
        }
        
?>
<?php
namespace Chatbot\Database;

use Chatbot\Database\ControladorPersistencia;
use Chatbot\Database\ControladorEscritura;
use Chatbot\Helper\Helper;
use PDO;
use PDOException;


/**
 * Esta clase es generada para manejar los datos y sentencias que vienen de los controladores
 * @author Diego
 */




class SqlQuery {

    protected $helper;
    protected $refControladorPersistencia; //controlador persistencia utilizado para crear la conexion a la BD

    function __construct() {
       
        $this->helper = new Helper();
        $this->refControladorPersistencia = new ControladorPersistencia();
    }

    public function crearBase($datosCampos) {//crea una base de datos
        $sentencia = "CREATE DATABASE IF NOT EXISTS " . $datosCampos["nombreDB"] . "; USE " . $datosCampos["nombreDB"] . ";"; //senetcia sql que crea y usa la base de datos
        $sentencia .= $this->crearTabla($datosCampos); //llama a la funcion de crear tablas
        return $sentencia; //regresa la sentencia al controlador     
    }

    public function crearTabla($datosCampos) {//funcion que se va a encargar de crear las tablas en la base de datos seleccionada
        for ($index = 0; $index < count($datosCampos); $index++) {//for encargado de recorrer el datoscampo para crear la tabla en la base de datos 
            $sentencia = " CREATE TABLE IF NOT EXISTS `" . $datosCampos["nombre"] . "`(`" .
                    "id_" . $datosCampos["nombre"] . "` INT NOT NULL AUTO_INCREMENT, `" .
                    $datosCampos["nombre"] . "` " . $datosCampos["tipo"] . "(" . $datosCampos["caracteres"] . ") NOT NULL,";
            $sentencia .= "`fch_creacion` DATETIME NOT NULL, `fch_modificacion` "
                    . "DATETIME NOT NULL,"
                    . " `fch_baja` DATETIME NOT NULL," .
                    "PRIMARY KEY" . "(id_" . $datosCampos["nombre"] . ")" .
                    ") ENGINE=INNODB AUTO_INCREMENT=55 "
                    . "DEFAULT CHARSET=latin1;";
        }
        return $sentencia; //regresa la sentencia a la funcion crear BD
    }

    public function listarTablas($base) {
        $sentencia = "SHOW FULL TABLES FROM " . $base;
        $this->respaldo($sentencia, $base);
        return $sentencia;
    }

    private function respaldo($sentencia, $base) {
        $escribir = new ControladorEscritura();
        try {
            $this->refControladorPersistencia->get_conexion()->beginTransaction(); //abro la conexion para leer la BD
            $variable = $this->refControladorPersistencia->ejecutarSentencia($sentencia); //realizo la consulta en la BD
            $var = $variable->fetchAll(PDO::FETCH_ASSOC); //obtengo los valores
            $this->refControladorPersistencia->get_conexion()->commit();
        } catch (PDOException $excepcionPDO) {
            echo "<br>Error PDO: " . $excepcionPDO->getTraceAsString() . '<br>';
            $this->refControladorPersistencia->get_conexion()->rollBack(); //si salio mal hace un rollback
        }
        for ($index = 0; $index < count($var); $index++) {//for para listar las tablas en la base de datos
            foreach ($var[$index] as $key => $value) {//foreach para leer data de las cabeceras de las bases
                if ($key == "Tables_in_" . $base) {//limpio el resto de la informacion en la BD
                    //echo $value . "<br>";*/
                    $escribo = $this->showCreate($value);//llamo a la funcion de crear el encabezado de los datos para realizar la insercion
                    $escribir->escribirPHP($escribo);
                    //$this->buscarTablaRespaldo($value); //obtengo todos los datos para realizar el respaldo
                }
            }
        }
        /* SELECT * rea INTO OUTFILE C:\\pepe.txt;
          The above MySQL statement will take a BACKUP of the publisher TABLE INTO a FILE called publisher_backup.txt located IN the C drive of your windows system.
          USING LOAD DATA INFILE statement, you can RESTORE DATA FROM the delimited TEXT files. */
        return $var;
    }

    public function buscarTablaRespaldo($tabla) {//funcion utilizada para obtener todos los datos de la tabla y realizar el respaldo correspondiente
        try {
            $this->refControladorPersistencia->get_conexion()->beginTransaction(); //abro la conexion para leer la BD
            $consulta = "SELECT * FROM " . $tabla; //con la consulta DESCRIBE $tabla´(éste es el nombre del controlador que obvio coincide con el de la tabla) obtengo la metadata de la BD
            $variable = $this->refControladorPersistencia->ejecutarSentencia($consulta); //realizo la consulta en la BD
            $var = $variable->fetchAll(PDO::FETCH_ASSOC); //obtengo los valores
            $this->refControladorPersistencia->get_conexion()->commit();
            $resultado = $this->recorrerConsulta($var, $tabla); //recorro los datos obtenidos en la consulta para para armar el archivo
        } catch (PDOException $excepcionPDO) {
            echo "<br>Error PDO: " . $excepcionPDO->getTraceAsString() . '<br>';
            $this->refControladorPersistencia->get_conexion()->rollBack(); //si salio mal hace un rollback
        }
        return $resultado; //regreso el array
    }

    public function recorrerConsulta($consulta, $tabla) {//funcion utilizada para recorrer y ordenar los datos de la BD
        $crear = "insert into `" . $tabla . "`(`"; //variable encargada de armar el encabezado 
        $escribir = new ControladorEscritura();//instancio clase escritura para transportar los datos a un txt
        foreach ($consulta as $key => $value) {//primer array para recorrer los datos consultados
            if ($key == 0) {//ingresa solo cuando es el primer indice para obtener el encabezado de la consulta
                for ($index = 0; $index < 1; $index++) {//for para realizar indexar la primer vez ingresado al foreach
                    $total = count($value); //variable encargada de de almacenar el total del array
                    $i = 0; //contador
                    foreach ($value as $llave => $dato) {//segundo foreach para obtener los datos que vienen en el array
                        if ($total - 1 == $i) {//cuando llego al final del array inserto los parentesis
                            $crear .= $llave . "`) values ( ";
                        } else {//sino sigo mientras existan campos los llene
                            $crear .= $llave . "`,`";
                        }
                        $i++; //sumo uno al contador
                    }
                }
            }
            foreach ($value as $key1 => $valor) {//este foreach es para llenar los valores que vienen de la tabla despues de haber armando el encabezado
                $crear .= "`" . $valor . "`,";
                $escribir->escribir($crear);//llamo a la funcion escribir para armar el archivo de datos
            }
        }
    }

    public function showCreate($tabla) {//utilizo esta funcion para mostrar los datos con los que se crearon las tablas
        try {
            $this->refControladorPersistencia->get_conexion()->beginTransaction(); //abro la conexion para leer la BD
            $consulta = "SHOW CREATE TABLE " . $tabla; //con la consulta SHOW CREATE TABLE  $tabla´obtengo los datos de la BC
            $variable = $this->refControladorPersistencia->ejecutarSentencia($consulta); //realizo la consulta en la BD
            $var = $variable->fetchAll(PDO::FETCH_ASSOC); //obtengo los valores
            $this->refControladorPersistencia->get_conexion()->commit();
        } catch (PDOException $excepcionPDO) {
            echo "<br>Error PDO: " . $excepcionPDO->getTraceAsString() . '<br>';
            $this->refControladorPersistencia->get_conexion()->rollBack(); //si salio mal hace un rollback
        }
        return $var; //regreso el array
    }

    public function meta($tabla) {//funcion meta(), se utiliza para obtener los datos de la tabla en cuestion que luego serán mis variables en las sentencias... y también mis claves primarias
        echo "-----hellegadohastaca----".__FILE__.__LINE__;
        $array = array(); //declaro array donde voy a armar los key a ser utilizados por el resto de los metodos 
        $this->refControladorPersistencia->get_conexion()->beginTransaction(); //abro la conexion para leer la BD
        $consulta = "DESCRIBE"; //con la consulta DESCRIBE $tabla´(éste es el nombre del controlador que obvio coincide con el de la tabla) obtengo la metadata de la BD
        $consulta .= ' ' . $tabla; //concateno la consulta con el nombre de la tabla
        $variable = $this->refControladorPersistencia->ejecutarSentencia($consulta); //realizo la consulta en la BD
        $var = $variable->fetchAll(PDO::FETCH_ASSOC); //obtengo los valores
        $this->refControladorPersistencia->get_conexion()->commit();
        foreach ($var as $valores) {//hago un for para recorrer los valores que me devuelve la tabla
            foreach ($valores as $clave => $valor) {//este for es el que se encarga de llenar el arreglo con los Keys correpondiente que obtuve de la BD
                if ($clave == "Field") {//Field = Campo :) no lo voy a aclarar
                    $array[$valor] = "campo"; //lleno el array y completo los valores con un string cualquiera para saber q estoy trabajando
                }
            }
        }
        return $array; //regreso el array*/           
    }

    static function showDatabases(){
        return 'SHOW tables';
    }    

    public function metaCompleto($tabla) {//funcion meta(), se utiliza para obtener los datos de la tabla en cuestion que luego serán mis variables en las sentencias... y también mis claves primarias
        $array = array(); //declaro array donde voy a armar los key a ser utilizados por el resto de los metodos 
        $this->refControladorPersistencia->get_conexion()->beginTransaction(); //abro la conexion para leer la BD
        $consulta = "DESCRIBE"; //con la consulta DESCRIBE $tabla´(éste es el nombre del controlador que obvio coincide con el de la tabla) obtengo la metadata de la BD
        $consulta .= ' ' . $tabla; //concateno la consulta con el nombre de la tabla
        $variable = $this->refControladorPersistencia->ejecutarSentencia($consulta); //realizo la consulta en la BD
        $var = $variable->fetchAll(PDO::FETCH_ASSOC); //obtengo los valores
        $this->refControladorPersistencia->get_conexion()->commit();
        foreach ($var as $valores) {//hago un for para recorrer los valores que me devuelve la tabla
            foreach ($valores as $clave => $valor) {//este for es el que se encarga de llenar el arreglo con los Keys correpondiente que obtuve de la BD
                if ($clave == "Field") {//Field = Campo :) no lo voy a aclarar
                    $array[$valor] = "campo"; //lleno el array y completo los valores con un string cualquiera para saber q estoy trabajando
                }               
            }
        }
        foreach ($array as $key => $value) {
            foreach ($var as $valores) {//hago un for para recorrer los valores que me devuelve la tabla
                foreach ($valores as $clave => $valor) {//este for es el que se encarga de llenar el arreglo con los Keys correpondiente que obtuve de la BD
                    if ($clave == "Null") 
                    {                        
                        $array[$key] = $valor; //lleno el array y completo los valores con un string cualquiera para saber q estoy trabajando
                    }               
                }
            }
        }
        return $array; //regreso el array*/        
    }

    public function armarSentencia($arrayCabecera, $tabla) {//ésta es la funcion encargada de generar la sentencia agregar en la base de datos
        $i = 1; //contador inicializado en 1 
        $strTabla = strtolower(substr($tabla, 11));
        $sentencia = "INSERT INTO " . $tabla . " ("; //sentencia insert paso como dato el nombre de la tabla
        $llaveStr = ""; //inicializada en vacio, para poder cargarlo los campos de las base de dato a la sentencia
        array_shift($arrayCabecera); //elimino el primer componente del array... ya que si es insertar el id tiene q ser automatico
        $long = sizeof($arrayCabecera); //determino el tamaño del array para usarlo en el if de los foreach
        foreach ($arrayCabecera as $llave => $value) {// recorro el array ;)
            $llaveStr = " " . $llave; //ingreso los nombres de los campos de la BD en la sentencia
            if ($long > $i) {//si todavia hay metadata con nombres de campo sigo el recorrido del array
                $sentencia .= $llaveStr . ","; //agrego una coma despues de cada nuevo campo ingresado en la sentencia
            } else {//sino =)
                $sentencia .= $llaveStr . ')'; //finalizo los campos en cerrando con un parentesis
            }
            $i++; //autoincremento de la variable
        }
        if ($i != 1) {
            $i = 1;
        } //vuelvo a inicializar la variable en 1 para su posterior uso en el próximo foreach
        $sentencia .= ' VALUES ('; //concateno la sentencia con la cantidad de campos que se requieran para las incognitas
        foreach ($arrayCabecera as $llave => $value) {//vuelvo a recorrer el array... ahora poque no lo hago en el mismo array de arriba con dos variables distintas y después las concateno
            if ($long > $i) {//si no es el último campo...
                $sentencia .= '?, '; //cargo con una incognita mientras no se el último... se repite
            } else {//sino ;,
                $sentencia .= '? )'; //finalizo la sentencia
            }
            $i++; //incremento
        }
        return $sentencia; //regreso la sentencia
    }

    public function armarArray($arrayCabecera, $arrayDatos) {//ésta es para armar el array al momento de realizar la inserción en la BD
        $paramArray = array(); //array q se encarga de devolver los datos al controlador solicitante
        $limpArray = array(); //en este solo quito los datos que no representan nada en esta acción 
        $i = 0; //contador para los foreach de datos
        $j = 0; //contador para las fechas
        $id = 0;//array_values($arrayDatos)[0];
        array_shift($arrayCabecera);
        $lenght= count($arrayDatos)-1;
        
        
        foreach ($arrayDatos as $llave => $value) {//recorro los datos del array de datos
            if ($i <= $lenght ) {//mientras no lleguemos a la llave acción sequimos incorporando al array los datos
                $limpArray[$i] = $value; //todos los valores .... con su respectivo indice
            } else {//sino $)
                $i = 0; //reinicio el contador ... llegué al final
                break; //termino el método
            }
            $i++; //autoincremento la variable $i
        } 
        $i=0;
        foreach ($arrayCabecera as $llave => $value) {//utilizo este foreach para cargar los datos en un array con sus respectivas llaves.... tal cual esta en la BD
            $paramArray[$llave] = $limpArray[$i];                
            $i++; //auto incremento
        }
        //var_dump($paramArray);
        return $paramArray; //regreso el array que se encuentra armado y listo para ser insertado en la BD
    }

    public function buscarUltimo($tabla) {//Esta función busca el máximo ID cargado el la tabla correspondiente para reguresar el dato
        $sentencia = "SELECT MAX(id) FROM " . $tabla; //es solo para no repetir la sentencia un montón de veces en el BDSentencias
        return $sentencia; //obvio.... regreso la sentencia
    }

    public function buscar($tabla, $id = NULL, $campo = NULL) {//tuve que generar esta función para no cambiar la de la lógica que usamos en el controlador..
        
        $sentencia = ""; //string para guardar la sentencia q voy a devolver al controlador
        $contador = 0;//verifico si es join o no        
        $sentencia = "SELECT * FROM " .$tabla ; //inserto el nombre del formulario para relizar la consulta desde el controlador        
        
        return $sentencia; //regreso la sentencia para ser usada..
    }

    public function eliminar($tabla, $id) {//está demás decir para que sirve esta función ... todavía basica... pero funcional
        $consulta = "DELETE FROM " . $tabla . " WHERE id =" . $id; // se genera la sentencia..
        return $consulta; //regreso la consula... '(
    }        

    private function buscarInnerJoin($tabla, $id, $campo) {//Ésta función esta todavía en fase de prueba... en realidad lo que me gustaria hacer es ver si en lugar de pasar una tabla secundaria pudiera generar un array para poder pasar los datos desde la consulta sea vista o gpt... de esa manera podría leer las todos lo elementos que vienen en el array para poder armar la consulta °¬)
        $array = $this->meta($tabla); //armo el array para buscar las relaciones de la tabla
        $arrLlaveNum = $this->llaveNumerica($array); //cambio el array que obtengo desde la meta y lo convierto en numero para pasarlo por el for        
        $consulta = "SELECT * FROM " . $arrLlaveNum[0]; //inicia la sentencia    
        for ($i = 0; $i < count($arrLlaveNum) - 1; $i++) {//uso un for para armar el array
            $consulta .= " INNER JOIN " . $arrLlaveNum[$i + 1] . " ON " . $arrLlaveNum[0] . ".id_" .
                    $arrLlaveNum[$i + 1] . " = " . $arrLlaveNum[$i + 1] . ".id_" .
                    $arrLlaveNum[$i + 1]; //agrego cuantos campor sea necesario y genero la consulta con todos datos q vienen del array numerico
        }
        if ($id != NULL) {
            $consulta .= " WHERE " . $arrLlaveNum[0] . "." . $campo . " = '" . $id . "'";
        }
        return $consulta; // tal cual lo pienso regreso la función y... magicamente busca con un INNER JOIN  dentro de la BD... @)
    } 

    

    public function armarSentenciaModificar($arrayCabecera, $tabla) {//ésta es la funcion encargada de generar la sentencia agregar en la base de datos
        $i = 1; //contador inicializado en 1 
        $sentencia = "UPDATE " . $tabla . " SET"; //sentencia insert paso como dato el nombre de la tabla
        $llaveStr = ""; //inicializada en vacio, para poder cargarlo los campos de las base de dato a la sentencia
        array_shift($arrayCabecera);
        $long = sizeof($arrayCabecera); //determino el tamaño del array para usarlo en el if de los foreach
        foreach ($arrayCabecera as $llave => $value) {// recorro el array ;)
            $llaveStr = " " . $llave; //ingreso los nombres de los campos de la BD en la sentencia
            if ($long > $i) {//si todavia hay metadata con nombres de campo sigo el recorrido del array
                $sentencia .= $llaveStr . "=?,"; //agrego una coma despues de cada nuevo campo ingresado en la sentencia
            } else {//sino =)
                $sentencia .= $llaveStr . "=? "; //finalizo los campos en cerrando con un parentesis
            }
            $i++; //autoincremento de la variable
        }
        $sentencia .= ' WHERE id_' . $tabla . " =?"; //concateno la sentencia con la cantidad de campos que se requieran para las incognitas
        return $sentencia;
    }

    public function arrayString($array) {//Ésta función la utilizo para  comparar la 1° clave de la base de datos (fuera del ID) con los datos que vienen desde la vista
        $i = 0; //inicio mi contador en 0
        $string = ""; //ésta es el string que voy a regresar a la consulta desde el controlador
        foreach ($array as $llave => $val) {//ingresa al foreach para recorrer el array
            if ($i == 1 && $val == "campo") {//verifica si es el 2 campo y si el contenido del mismo es campo ... ingreso y asigno los valores correspondientes al array
                $string = $llave; // el string que quiero devolver ;)
                
                break; //termino el ciclo ya tengo el dato que necesito
            } else if ($i == 1) {//sino :]
                $string = $val; // en este caso asigno el string lo cargo con el valor del campo porque es el que viene de la vista
            }
            $i++; //incremento
        }
        return $string; //regreso al lugar de donde se hizo la llamada... °¬[
    }

    public function cambiarArray($array) {//esta funcion es utilizada para cambiar el primer elemento por el último dentro de la funcion modficar
        $llave = array_keys($array)[0]; //creo una variable llamada llave donde guardo el primer elemento del array
        $valor = array_values($array)[0]; //una variable llamada valor donde guardo el primer valor del array
        array_shift($array); //elimino el primer elemento para no repetir $}
        $array[$llave] = $valor; //agrego al final del array el elemento q saque para que se utilice en la funcion modificar
        return $array; //regreso el array
    }
   

    private function llaveNumerica($array) {//cambio los datos del array del meta por claves con numeros para manejar la sentencia del join
        $arrayNum = array(); //array generado para regresara al llamado de la funcion
        foreach ($array as $llave => $valor) {//for para recorrer el array
            if (substr($llave, 0, 2) == "id") {//solo ingreso si es id... en la base de datos todos los campos comunes deben comenzar con id y correspoderse con los id´s de las otras tablas
                array_push($arrayNum, substr($llave, 3)); //sumo a mi array nuevo los datos de las llavas sacando los "id_" que vienen de la BD
            }
        }
        return $arrayNum; //regreso el array
    }
   
}
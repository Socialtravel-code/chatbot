<?php
namespace Chatbot\Database;

use Chatbot\Database\ControladorPersistencia;
use Chatbot\Database\SqlQuery;
use Chatbot\LogClass\LogClass;
use Chatbot\Helper\Helper;
use Exception;
use PDO;
use PDOException;


/*
 * Clase generada para servir de controlador maestro 
 */

/**
 * Description of ControladorMaster
 *
 * @author DIEGO
 */
class ControladorMaster
{

    protected $refControladorPersistencia;
    protected $sqlQuery;

    function __construct()
    {     
        $this->refControladorPersistencia = new ControladorPersistencia();               
        $this->sqlQuery = new SqlQuery();       
        
    }

    public function meta($tabla)
    {
         
        try 
        {   
            //echo "-----hellegadohastaca----".__FILE__.__LINE__."</br>"; 
            $this->refControladorPersistencia->get_conexion()->beginTransaction();
            $this->refControladorPersistencia->get_conexion()->beginTransaction();     
            //echo "-----hellegadohastaca----".__FILE__.__LINE__."</br>";   
            $objeto = $this->refControladorPersistencia->ejecutarSentencia(
                $this->sqlQuery->meta($tabla)
            );                      
            return $objeto;
        } catch (PDOException $e) { 
            //var_dump($e);
            //echo "-----hellegadohastaca----".__FILE__.__LINE__."</br>";               
            $this->refControladorPersistencia->get_conexion()->rollBack(); //si salio mal hace un rollback
            new LogClass($e,__LINE__,__CLASS__);
            //file_put_contents(__DIR__ . "//log/dberror.log", "Date: " . date('M j Y - G:i:s') . " ---- Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            echo $e->getCode()." Consulte con el administrador";
        }
    }

    public function metaCompleto($tabla)
    {
        try 
        {
            $objeto = $this->sqlQuery->metaCompleto($tabla);
            return $objeto;
        } catch (\Throwable $e) {
            echo $e->getTraceAsString();
            $this->refControladorPersistencia->get_conexion()->rollBack(); //si salio mal hace un rollback
        }
    }

    public function buscar($tabla)
    {
        
        try 
        {
            $this->refControladorPersistencia->get_conexion()->beginTransaction(); //comienza la transacción
            $statement = $this->refControladorPersistencia->ejecutarSentencia(
                $this->sqlQuery->buscar($tabla)
            ); //senencia armada desde la clase SqlQuery sirve para comenzar la busqueda
            $array = $statement->fetchAll(PDO::FETCH_ASSOC); //retorna un array asociativo para no duplicar datos
            $this->refControladorPersistencia->get_conexion()->commit(); //si todo salió bien hace el commit            
            return $array; //regreso el array para poder mostrar los datos en la vista... con Ajax... y dataTable de JavaScript
        } catch (PDOException $excepcionPDO) {
            echo "<br>Error PDO: " . $excepcionPDO->getTraceAsString() . '<br>';
            $this->refControladorPersistencia->get_conexion()->rollBack(); //si salio mal hace un rollback
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
            $this->refControladorPersistencia->get_conexion()->rollBack(); //si salio mal hace un rollback
        }
    }

    public function showDatabases()
    {
        try {
            $this->refControladorPersistencia->get_conexion()->beginTransaction(); //comienzo la transacción
            $statement = $this->refControladorPersistencia->ejecutarSentencia(
                $this->sqlQuery::showDatabases() //$this->sqlQuery::showDatabases()
            ); //Uso la funcion correspondiente de controlador pesistencia    
            $this->refControladorPersistencia->get_conexion()->commit(); //ejecuto la acción para eliminar de forma lógica a los ususario
            return $statement->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $excepcionPDO) { //excepcion para controlar los errores
            echo "<br>Error PDO: " . $excepcionPDO->getTraceAsString() . '<br>';
            $this->refControladorPersistencia->get_conexion()->rollBack(); //si salio mal hace un rollback
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
            $this->refControladorPersistencia->get_conexion()->rollBack();  //si hay algún error hace rollback
        }
        return $statement;
    }

    public function eliminar($tabla, $id)
    {
        try {
            $this->refControladorPersistencia->get_conexion()->beginTransaction(); //comienzo la transacción
            $this->refControladorPersistencia->ejecutarSentencia(
                $this->sqlQuery->eliminar($tabla, $id)
            ); //Uso la funcion correspondiente de controlador pesistencia         
            $this->refControladorPersistencia->get_conexion()->commit(); //ejecuto la acción para eliminar de forma lógica a los ususario
        } catch (PDOException $excepcionPDO) { //excepcion para controlar los errores
            echo "<br>Error PDO: " . $excepcionPDO->getTraceAsString() . '<br>';
            $this->refControladorPersistencia->get_conexion()->rollBack(); //si salio mal hace un rollback
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
            $this->refControladorPersistencia->get_conexion()->rollBack();  //si hay algún error hace rollback
        }
        return ["eliminado" => "eliminado"];
    }

    public function guardar($tabla, $datosCampos)
    {

        try {
            $this->refControladorPersistencia->get_conexion()->beginTransaction();
            //comienza la transacción
            $arrayCabecera = $this->sqlQuery->meta($tabla); //armo la cabecera del array con los datos de la tabla de BD
            $sentencia =  $this->sqlQuery->armarSentencia($arrayCabecera, $tabla); //armo la sentencia
            $array =  $this->sqlQuery->armarArray($arrayCabecera, $datosCampos); //armo el array con los datos de la vista y los datos que obtuve de la BD
            $this->refControladorPersistencia->ejecutarSentencia($sentencia, $array); //genero la consulta
            $this->refControladorPersistencia->get_conexion()->commit();
        } catch (PDOException $excepcionPDO) {
            echo "<br>Error PDO: " . $excepcionPDO->getTraceAsString() . '<br>';
            $this->refControladorPersistencia->get_conexion()->rollBack(); //si salio mal hace un rollback
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
            $this->refControladorPersistencia->get_conexion()->rollBack();  //si hay algún error hace rollback
        }
        $respuesta = ["status" => "ok"]; //si la transaccion termino correctamente
        return $respuesta; //regreso       

    }

    public function modificar($tabla, $datosCampos)
    {
        $id = $datosCampos["id"];
        try {
            $this->refControladorPersistencia->get_conexion()->beginTransaction();  //comienza la transacción 
            $arrayCabecera = $this->sqlQuery->meta($tabla); //armo el array con la cabecera de los datos
            $sentencia = $this->sqlQuery->armarSentenciaModificar($arrayCabecera, $tabla); //genero sentencia
            $array = $this->sqlQuery->armarArray($arrayCabecera, $datosCampos); //Armo el array con los datos que vienen de la vista y la cabecera de la BD
            array_shift($array); //elimino primer elemento del array que es el id
            array_push($array, $id); //agrego el id al final del array para realizar la consulta
            $this->refControladorPersistencia->ejecutarSentencia($sentencia, $array); //genero la consulta a la BD            
            $this->refControladorPersistencia->get_conexion()->commit();  //si todo salió bien hace el commit            
        } catch (PDOException $excepcionPDO) {
            echo "<br>Error PDO: " . $excepcionPDO->getTraceAsString() . '<br>';
            $this->refControladorPersistencia->get_conexion()->rollBack(); //si salio mal hace un rollback
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
            $this->refControladorPersistencia->get_conexion()->rollBack();  //si hay algún error hace rollback
        }
        $respuesta = ["status" => "ok"]; //si la transaccion termino correctamente
        return $respuesta;
    }

    public function getCampo($datosCampos)
    {
        $i = 0;
        foreach ($datosCampos as $key => $value) {
            if ($i == 1) {
                return $key;
            }
            $i++;
        }
    }
}

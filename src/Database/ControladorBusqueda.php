<?php
namespace Chatbot\Database;

use Chatbot\Database\ControladorPersistencia;
use Chatbot\Helper\Hepler;//incluyo helper que se encarga de llamar a funciones de ayuda
use Chatbot\Database\SqlQuery;
use Chatbot\Database\ControladorMaster;
use Chatbot\Helper\Helper;
use Chatbot\LogClass\LogClass;

class ControladorBusqueda
{

    protected $refControladorPersistencia;

    function __construct()
    {
        $this->refControladorPersistencia = new ControladorPersistencia();
        
    }

    public function meta($tabla) //incluyo nombre de tabla para realizar consulta
    {
        
        $master = new ControladorMaster();    
        //echo "-----hellegadohastaca----".__FILE__.__LINE__."</br>";     
        return $master->meta($tabla); //envio datos
    }

    public function metaCompleto($tabla)
    {
        $master = new ControladorMaster();
        return $master->metaCompleto($tabla);
    }

    public function buscar($tabla)
    { //busca usando la clase SqlQuery
        $master = new ControladorMaster();
        return $master->buscar($tabla);
    }


    public function eliminar($id, $tabla)
    { //elimina usando SqlQuery clase
        $master = new ControladorMaster();
        $master->eliminar($tabla, $id);
        return ["eliminado" => "eliminado"];
    }


    public function guardar($datosCampos, $tabla)
    { //funcion guardar con SqlQuery implementado
        try 
        {
            $master = new ControladorMaster(); //instancio clase array maestro
            $sql = new SqlQuery(); // instancion clase sql
            $arrayMaestro = $sql->meta($tabla); // busco metadata
            array_shift($arrayMaestro); //tablas 
            $helper = new Helper();
            $datosCampos = $helper->compararVista($arrayMaestro, $datosCampos);
            return $master->guardar($tabla, $datosCampos); //llamo a la funcion que realiza el gurdado de los datos

        } catch (\Throwable $e) {
            new LogClass($e,__LINE__,__CLASS__);
            //file_put_contents(__DIR__ . "//log/dberror.log", "Date: " . date('M j Y - G:i:s') . " ---- Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
        //comparo los datos que vienen de la vista con los que genero la meta de la BD

        

    }



    public function modificar($datosCampos, $tabla)
    { //utiliza clase SqlQuery para automatizar consulta
        $guardar = new SqlQuery(); //instancio objeto de la clase sqlQuery
        $master = new ControladorMaster();
        return $master->modificar($tabla, $datosCampos);
    }


    public function showDataBases()
    { //utiliza clase SqlQuery para automatizar consulta
        $master = new ControladorMaster();
        return $master->showDatabases();
    }

}

<?php
namespace Chatbot\Database;

use Chatbot\Database\Conexion as Conn;

class ControladorPersistencia
{
    private $_conexion = null;
    function get_conexion()
    {
        return $this->_conexion;
    }

    public function __construct()
    {
        //echo "-----hellegadohastaca----".__FILE__.__LINE__."</br>";
        $db = new Conn();
        $this->_conexion = $db->getConexion();
    }

    /**
     *
     * @param string $query
     * @param array $parametros
     */
    public function ejecutarSentencia($query, $parametros = null)
    {        
        $statement = $this->_conexion->prepare($query);// llamo a prepare
        if ($parametros) {
            $index = 1;
            foreach ($parametros as $key => $parametro) {//recorro el array 
                $statement->bindValue($index, $parametro);//hago bind de los datos con la sentencia
                $index++;
            }
            
        }
        $statement->execute();//ejecto jua
        return $statement;
    }
    public function getUltimoId()
    {
        return $this->_conexion->lastInsertId();
    }
}



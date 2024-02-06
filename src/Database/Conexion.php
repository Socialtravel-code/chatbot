<?php

namespace Chatbot\Database;

use PDO;
use PDOException;
use Chatbot\Dotenv\Dotenvclass;
use Chatbot\LogClass\LogClass;

class Conexion
{
    private $_conexion = null;
    private $cadenaConexion = "";

    public function __construct()
    {
        $this->getDotenv();

        $this->cadenaConexion = $_ENV['DB_DRIVER'].":host=" . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] ;

        try {
            $this->_conexion = new PDO($this->cadenaConexion, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES  \'UTF8\''));
        } catch (PDOException $e) {
            new LogClass($e,__LINE__,__CLASS__);
            //file_put_contents(__DIR__ . "//log/dberror.log", "Date: " . date('M j Y - G:i:s') . " ---- Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }

    public function getConexion() //expongo la conexion
    {

        return $this->_conexion;
    }

    public function getDotenv()
    {
        return new Dotenvclass();
    }

    public function __destruct() // finalizo la conexion
    {
        try {
            $this->_conexion = null; //cierra conexion
        } catch (PDOException $e) {
            //file_put_contents("log/dberror.log", "Fecha: " . date('M j Y - G:i:s') . " ---- Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            new LogClass($e,__LINE__,__CLASS__);
            die($e->getMessage());
        }
    }
}

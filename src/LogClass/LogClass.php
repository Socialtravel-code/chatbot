<?php
namespace Chatbot\LogClass;

class LogClass
{
    private $dir;

    public function __construct($error,$linea,$clase)
    {
        $this->dir = dirname(__DIR__);
        file_put_contents($this->dir. "/LogClass/log/dberror.log", "Date: " . 
        date('M j Y - G:i:s') . " <--> Error: " . $error->getMessage(). "\n\t---En la Linea: '".$linea."' \n\t---Clase ".$clase . PHP_EOL, FILE_APPEND);

    }


}
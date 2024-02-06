<?php

namespace Chatbot\Dotenv;
use Dotenv\Dotenv;

class Dotenvclass{
    

    private $dir;//varible que va a almacernar la raiz del directorio donde se encuenta el .env
    private $dotenv;//varible para cargar el archivo .env

    public function __construct()
    {
        $this->dir = dirname(__DIR__);//obtengo la raíz del directorio        
        $this->dotenv = Dotenv::createImmutable($this->dir); //Creo el inmutable para acceder a la data
        $this->dotenv->load(); //cargo los datos desde el .env
    }

}
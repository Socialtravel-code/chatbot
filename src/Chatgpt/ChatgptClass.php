<?php

namespace Chatbot\Chatgpt;

use Chatbot\Database\ControladorBusqueda; //incluyo el controladro busqueda que centraliza el CRUD
use Chatbot\Dotenv\Dotenvclass; //cargo Dotenv
use Chatbot\Helper\Helper;
use Chatbot\LogClass\LogClass;
use OpenAI; //cargo libretia OpenAI

class ChatgptClass
{
    private $apiKey; //apikey para la conexion a la API de openAI
    private $busqueda; //objeto de la clase busqueda
    private $respuestaArmada; //la respuesta que recibo desde el helper para armar la pregunta a chatgpt
    private $helper;
    private $meta;

    function __construct($inputusuario, $palabra)
    {
        $this->getDotenv();
        $this->apiKey = $_ENV['APIKEY'];
        $this->helper = new Helper();
        $this->busqueda = new ControladorBusqueda();
        $this->meta = $this->busqueda->meta($palabra);
        $this->respuestaArmada = $this->helper->consultaReplace($this->meta);
        $this->retornaJson($inputusuario, $this->respuestaArmada);
        //$this->crearJsonChat($this->respuestaArmada." ".$inputusuario);
        //$this->crearJsonChat($this->apiKey, $pregunta);
    }

    private function crearJsonChat($pregunta)
    {
        try {

            $client = OpenAI::client($this->apiKey); //genero el cliente que se va a encargar de hacer la consulta a chatgpt
            $result = $client->completions()->create([ //creo la consulta al chatgpt con los datos necesarios
                'model' => 'gpt-3.5-turbo-instruct',
                'prompt' => $pregunta,
                'max_tokens' => 3000,
                'temperature' => 0.1
            ]);
            //die($result);
            return $result->choices[0]->text; // regresa el JSON que he creado


        } catch (\Exception $e) {
            //file_put_contents(dirname(__DIR__) . "/Database/log/dberror.log", "Date: " . date('M j Y - G:i:s') . " ---- Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND); //guardo la excepcion en el log de errores
            new LogClass($e, __LINE__, __CLASS__);
            echo  $e->getCode() . " Consulte con el administrador";
        }
    }

    public function retornaJson($inputusuario, $pregunta)
    {
        try {
            $response = $this->crearJsonChat($pregunta . " " . $inputusuario);
            //var_dump($response);
            $decoded_json = json_decode($response, false);
            //echo $decoded_json;
            return $this->busqueda->guardar(preg_replace('/^[^{\[]*|[^}\]]*$/', '', $decoded_json), "reservaciones");
        } catch (\Throwable $e) {
            //file_put_contents(dirname(__DIR__) . "/Database/log/dberror.log", "Date: " . date('M j Y - G:i:s') . " ---- Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND); //guardo la excepcion en el log de errores
            new LogClass($e, __LINE__, __CLASS__);
            echo  $e->getCode() . " Consulte con el administrador";
        }
    }

    public function guardar()
    {
    }

    public function getDotenv()
    {
        return new Dotenvclass();
    }
}

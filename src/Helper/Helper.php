<?php

namespace Chatbot\Helper;

use Chatbot\Consulta\InterfaceConsulta;
use DateTime;

class Helper
{

    function compararVista($array, $datos)
    {
        $nuevo = array_keys($array); //busco los key en el array
        $nuevo = array_fill_keys($nuevo, NULL); // relleno con NULL
        foreach ($datos as $key => $value) {
            if (array_key_exists($key, $array)) {
                $nuevo[$key] = $datos[$key]; //relleno con los datos
            }
        }
        return $nuevo; //regreso el array
    }

    function consultaReplace($meta)
    {
        return $this->remplazarString("[CAMPOS]", $meta, InterfaceConsulta::CONSULTA_JSON);
    }

    function remplazarString($search, $replace, $subject)
    {
        $nuevoString = "";
        $replace = json_encode($replace, JSON_UNESCAPED_UNICODE);
        $nuevoString = str_replace($search, $replace, $subject);
        $fecha = new DateTime();
        $fecha = $fecha->format('Y-m-d');
        $nuevoString = str_replace("[DATE]", $fecha, $nuevoString);
        return $nuevoString;
    }
}

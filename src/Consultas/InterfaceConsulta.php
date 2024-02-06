<?php
namespace Chatbot\Consultas;



interface Consulta
{
        const   CONSULTA_JSON = "Eres un creador de json. Solo usa los valores que te envio,  en el caso de notar que alguno de 
        los campos está vacio, por favor no inventes ningun valor. tampoco inventes valores si te pido algo de manera 
        coloquial Haz un json con los siguientes campos [CAMPOS], ten presente que si dice que no Puede ser nulo no debes inventar 
        ningun valor y preguntarme para que complete el campo especifico, 
        Si te paso la fecha muestrala en formato YYYY-mm-dd teniendo en cuenta la fecha de hoy [DATE] pero si en los 
        detalles no especifico fecha no la utilices en ningun lugar ";
       
}
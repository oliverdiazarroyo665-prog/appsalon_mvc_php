<?php

namespace Controllers;

use Model\Cita;
use Model\CitaServicio;
use Model\Servicio;

class APIController
{
    public static function index()
    {
        $servicios = Servicio::all();

        //arreglo asociativo es lo mismo que un objeto en javascript

        echo json_encode($servicios);
    }

    public static function guardar()
    {

        //ALMACENA la cita y devuelve el id

        $cita = new Cita($_POST);

        $resultado = $cita->guardar();

        $id = $resultado['id'];

        //ALMACENA las citas y el servicio

        //ALMACENA LOS SERVICIOS CON EL ID DE LA CITA
        $idServicios = explode(",", $_POST['servicios']);
        foreach ($idServicios as $idServicio) {
            $args  = [
                'citaId' => $id,
                'servicioId' => $idServicio
            ];
            $citaServicio = new CitaServicio($args);
            $citaServicio->guardar();
        }

        echo json_encode(['resultado' => $resultado]);
    }

    public static function eliminar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $cita = Cita::find($id);
            $cita->eliminar();
            header('Location:' . $_SERVER['HTTP_REFERER']);
        }
    }
}

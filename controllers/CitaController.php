<?php

namespace Controllers;

use MVC\Router;


class CitaController
{
    public static function index(Router $router)
    {
        // Verificar si la sesión no está activa antes de iniciarla
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        isAuth();

        $router->render('cita/index', [
            'nombre' => $_SESSION['nombre'],
            'id' => $_SESSION['id']
        ]);
    }
}

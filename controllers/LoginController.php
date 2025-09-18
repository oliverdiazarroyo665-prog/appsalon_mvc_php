<?php

namespace Controllers;

use MVC\Router;
use Classes\Email;
use Model\Usuario;

class LoginController
{
    public static function login(Router $router)
    {
        $alertas = [];



        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);

            $alertas =  $auth->validarLogin();

            if (empty($alertas)) {
                //comprobar que exista usuario
                $usuario = Usuario::where('email', $auth->email);

                if ($usuario) {
                    //verificar el password
                    if ($usuario->comprobarPasswordAndVerificado($auth->password)) {
                        //autenticar el usuario
                        session_start();

                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre . " " . $usuario->apellido;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        if ($usuario->admin === "1") {
                            $_SESSION['admin'] = $usuario->admin ?? null;

                            header('Location: /admin');
                        } else {
                            header('Location: /cita');
                        }
                    }
                } else {
                    Usuario::setAlerta('error', 'usuario no encontrado');
                }
            }
        }
        $alertas = Usuario::getAlertas();
        $router->render('auth/login', [
            'alertas' => $alertas,

        ]);
    }
    public static function logout()
    {
        // Verificar si la sesión no está activa antes de iniciarla
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];

        header('Location: /');
    }
    public static function olvide(Router $router)
    {
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            $alertas = $auth->validarEmail();

            if (empty($alertas)) {
                $usuario = Usuario::where('email', $auth->email);

                if ($usuario && $usuario->confirmado === "1") {
                    //generar token 
                    $usuario->crearToken();
                    $usuario->guardar();

                    //ENVIAR EL EMAIL
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();

                    //alerta de exito
                    Usuario::setAlerta('exito', 'revisa tu email');
                } else {
                    Usuario::setAlerta('error', 'el usuario no existe o no esta confirmado');
                }
            }
        }
        $alertas = Usuario::getAlertas();
        $router->render('auth/olvide-password', [
            'alertas' => $alertas
        ]);
    }
    public static function recuperar(Router $router)
    {
        $alertas = [];
        $error = false;

        $token = s($_GET['token']);

        //buscar usuario por su token
        $usuario = Usuario::where('token', $token);

        if (empty($usuario)) {
            Usuario::setAlerta('error', 'token no valido');
            $error = true;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //leer el nuevo password y guardarlo
            $password = new Usuario($_POST);
            $alertas = $password->validarPassword();
            //debuguear($password);
            if (empty($alertas)) {
                $usuario->password = null;
                $usuario->password = $password->password;
                $usuario->hashPassword();
                $usuario->token = null;
                $resultado = $usuario->guardar();
                if ($resultado) {
                    header('Location: /');
                }
            }
        }
        //debuguear($usuario);
        $alertas = Usuario::getAlertas();

        $router->render('auth/recuperar-password', [
            'alertas' => $alertas,
            'error' => $error
        ]);
    }
    public static function crear(Router $router)
    {

        $usuario = new Usuario;

        //alertas vacias
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();

            //revisar que alertas este vacio
            if (empty($alertas)) {
                //Que el usuario no este registrado
                $resultado = $usuario->existeUsuario();

                if ($resultado->num_rows) {
                    $alertas = Usuario::getAlertas();
                } else {
                    //Hashear el password
                    $usuario->hashPassword();

                    //generar un token unico
                    $usuario->crearToken();

                    //enviar el email
                    $email = new Email(
                        $usuario->nombre,
                        $usuario->email,
                        $usuario->token
                    );

                    $email->enviarConfirmacion();
                    //crear el usuario
                    $resultado = $usuario->guardar();

                    if ($resultado) {
                        header('Location: /mensaje');
                    }
                }
            }
        }
        $router->render('auth/crear-cuenta', [
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }
    public static function mensaje(Router $router)
    {
        $router->render('auth/mensaje');
    }

    public static function confirmar(Router $router)
    {

        $alertas = [];

        $token = s($_GET['token']);

        $usuario = Usuario::where('token', $token);

        if (empty($usuario)) {
            //mostrar mensaje de error
            Usuario::setAlerta('error', 'token no valido');
        } else {
            //modificar a usuario confirmado
            $usuario->confirmado = "1";
            $usuario->token = '';
            $usuario->guardar();
            Usuario::setAlerta('exito', 'cuenta comprobada correctamente');
        }
        //obtener alertas
        $alertas = Usuario::getAlertas();

        //renderizar la vista
        $router->render('auth/confirmar-cuenta', [
            'alertas' => $alertas
        ]);
    }
}

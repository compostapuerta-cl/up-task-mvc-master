<?php

namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

class LoginController
{

    public static function login(Router $router)
    {
        $alertas = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarLogin();

            if (empty($alertas)) {
                //VERIFICAR QUE EL USUARIO EXISTA
                $usuario = Usuario::where('email', $usuario->email);
                if (!$usuario || !$usuario->confirmado) {
                    Usuario::setAlerta('error', "El Usuario no Existe o No esta confirmado");
                } else {
                    //EL USUARIO EXISTE
                    if (password_verify($_POST['password'], $usuario->password)) {
                        //INICIAR SESION DEL USUARIO SI EL PASSWORD ES CORRECTO
                        session_start();
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;
                        
                        //REDIRECCIONAR
                        header('Location: /dashboard');

                    } else {
                        Usuario::setAlerta('error', "Password Incorrecto");  
                    }
                }
            }
        }
        $alertas = Usuario::getAlertas();

        //RENDER A LA VISTA
        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesion',
            'alertas' => $alertas
        ]);
    }

    public static function logout()
    {
        session_start();
        $_SESSION = []; /* AL INICIAR SESION ES UN ARREGLO CON ESTO LIMPIAMOS LOS VALORES */
        header('Location: /');
    }

    public static function crear(Router $router)
    {
        $alertas = [];
        $usuario = new Usuario;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();

            if (empty($alertas)) {
                $existeUsuario = Usuario::where('email', $usuario->email);

                if ($existeUsuario) {
                    Usuario::setAlerta('error', 'El Usuario ya esta registrado');
                    $alertas = Usuario::getAlertas();
                } else {

                    //HASEAR EL PASSWORD
                    $usuario->hashPassword();

                    //ELIMINAR PASSWORD2 2222
                    unset($usuario->password2);

                    //GENERAR EL TOKEN
                    $usuario->crearToken();

                    //CREAR UN NUEVO USUARIO
                    $resultado = $usuario->guardar();

                    //ENVIAR EMAIL
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarConfirmacion();

                    if ($resultado) {
                        header('Location: /mensaje');
                    }
                }
            }
        }


        //RENDER A LA VISTA
        $router->render('auth/crear', [
            'titulo' => 'Crear cuenta',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function olvide(Router $router)
    {
        $alertas = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();

            if (empty($alertas)) {
                //BUSCAR EL USUARIO
                $usuario = Usuario::where('email', $usuario->email);

                if ($usuario && $usuario->confirmado) {
                    // GENERAR UN TOKEN NUEVO
                    $usuario->crearToken();
                    unset($usuario->password2);
                    // ACTUALIZAR EL USUARIO
                    $usuario->guardar();

                    // ENVIAR EL EMAIL
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();

                    // IMPRIMIR LA ALERTA
                    Usuario::setAlerta('exito', 'Hemos enviado las instrucciones a tu email');
                } else {
                    Usuario::setAlerta('error', 'El usuario no existe o no esta confirmado');
                }
            }
        }
        $alertas = Usuario::getAlertas();

        //MUESTRA LA VISTA
        $router->render('auth/olvide', [
            'titulo' => 'Olvide mi Password',
            'alertas' => $alertas
        ]);
    }

    public static function reestablecer(Router $router)
    {
        $token = s($_GET['token']);
        $mostrar = true;

        if (!$token) header('Location: /');
        //IDENTIFICAR EL USUARIO CON ESTE TOKEN
        $usuario = Usuario::where('token', $token);
        if (empty($usuario)) {
            Usuario::setAlerta('error', 'Token No Valido');
            $mostrar = false;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //AGREGANDO EL NUEVO PASSWORD
            $usuario->sincronizar($_POST);
            //VALIDAR EL PASSWORD
            $alertas = $usuario->validarPassword();

            if (empty($alertas)) {
                //HASEAR EL NUEVO PASSWORD
                $usuario->hashPassword();
                //ELIMINAR EL TOKEN
                $usuario->token = null;
                //GUARDAR EL USUARIO EN LA BD
                $resultado = $usuario->guardar();
                //REDIRECCIONAR
                if ($resultado) {
                    header('Location: /');
                }
            }
        }
        $alertas = Usuario::getAlertas();
        //MUESTRA LA VISTA
        $router->render('auth/reestablecer', [
            'titulo' => 'Reestablecer Password',
            'alertas' => $alertas,
            'mostrar' => $mostrar
        ]);
    }

    public static function mensaje(Router $router)
    {
        $router->render('auth/mensaje', [
            'titulo' => 'Cuenta Creada Exitosamente'
        ]);
    }

    public static function confirmar(Router $router)
    {
        $token = s($_GET['token']);
        if (!$token) header('Location: /'); //SI ALGUIEN INTENTA ENTRAR A CONFIRMAR LO MANDA AL INICIO

        //ENCONTRAR AL USUARIO CON ESTE TOKEN
        $usuario = Usuario::where('token', $token);
        if (empty($usuario)) {
            //NO SE ENCONTRO UN USUARIO CON ESE TOKEN
            Usuario::setAlerta('error', 'Token no Valido');
        } else {
            //CONFIRMAR LA CUENTA
            $usuario->confirmado = 1;
            $usuario->token = null;
            unset($usuario->password2);

            //GUARDAR EN LA BASE DE DATOS
            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta Comprobada Correctamente');
        }




        $alertas = Usuario::getAlertas();
        $router->render('auth/confirmar', [
            'titulo' => 'Confirma tu cuenta UpTask',
            'alertas' => $alertas
        ]);
    }
}

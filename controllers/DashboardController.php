<?php

namespace Controllers;

use Model\Proyecto;
use Model\Usuario;
use MVC\Router;


class DashboardController
{
    public static function index(Router $router)
    {
        session_start();
        isAuth();
        $id = $_SESSION['id'];
        $proyectos = Proyecto::belongsTo('propietarioId', $id);

        $router->render('dashboard/index', [
            'titulo' => 'Proyectos',
            'proyectos' => $proyectos
        ]);
    }

    public static function crear_proyecto(Router $router)
    {
        session_start();
        isAuth();
        $alertas = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proyecto = new Proyecto($_POST);

            //VALIDACION
            $alertas = $proyecto->validarProyecto();

            if (empty($alertas)) {
                //GENERAR UNA URL UNICA
                $hash = md5(uniqid());
                $proyecto->url = $hash;

                //ALMACENAR EL CREADO DEL PROYECTO
                $proyecto->propietarioId = $_SESSION['id'];

                //GUARDAR EL PROYECTO
                $proyecto->guardar();

                //REDIRECCIONAR
                header('Location:/proyecto?url=' . $proyecto->url);
            }
        }

        $router->render('dashboard/crear-proyecto', [
            'alertas' => $alertas,
            'titulo' => 'Crear Proyecto'
        ]);
    }

    public static function proyecto(Router $router)
    {
        session_start();
        isAuth();

        //REVISAR QUE LA PERSONA QUE VISITA EL PROYECTO, ES QUIEN LO CREO
        $token = $_GET['id'];
        if (!$token) header('Location: /dashboard');
        $proyecto = Proyecto::where('url', $token);
        if ($proyecto->propietarioId !== $_SESSION['id']) {
            header('Location: /dashboard');
        }



        $router->render('dashboard/proyecto', [
            'titulo' => $proyecto->proyecto
        ]);
    }

    public static function perfil(Router $router)
    {
        session_start();
        isAuth();
        $alertas = [];

        $usuario = Usuario::find($_SESSION['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validar_perfil();

            if (empty($alertas)) {

                $existeUsuario = Usuario::where('email', $usuario->email);

                if ($existeUsuario && $existeUsuario->id !== $usuario->id) { //SI EXISTE USUARIO ID ES DIFERENTE AL USUARIO QUE ESTA AUTENTICADO
                    //MENSAJE DE ERROR
                    Usuario::setAlerta('error', 'Email no valido, ya pertenece a otra cuenta');
                    $alertas = $usuario->getAlertas();
                } else {
                    //GUARDAR EL REGISTRO

                    $usuario->guardar();

                    Usuario::setAlerta('exito', 'Guardado Correctamente');
                    $alertas = $usuario->getAlertas();

                    //ASIGNAR EL NOMBRE NUEVO A LA BARRA
                    $_SESSION['nombre'] = $usuario->nombre;
                }
            }
        }

        $router->render('dashboard/perfil', [
            'titulo' => 'Perfil',
            'usuario' => $usuario,
            'alertas' => $alertas

        ]);
    }

    public static function cambiar_password(Router $router)
    {
        session_start();
        isAuth();

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = Usuario::find($_SESSION['id']);
            //SINCRONIZAR CON LOS DATOS DEL USUARIO
            $usuario->sincronizar($_POST);

            $alertas = $usuario->nuevo_password();

            if (empty($alertas)) {
                $resultado = $usuario->comprobar_password();

                if ($resultado) {
                    
                    $usuario->password = $usuario->password_nuevo;
                    //ELIMINAR PROPIEDADES NO NECESARIAS
                    unset($usuario->password_actual);
                    unset($usuario->password_nuevo);

                    //HASEAR EL NUEVO PASSWORD

                 $usuario->hashPassword();
                    //ACTUALIZAR
                  $resultado = $usuario->guardar();

                  if($resultado){
                    Usuario::setAlerta('exito', 'Password Guardado Correctamente');
                    $alertas = $usuario->getAlertas();
                  }
                } else {
                    Usuario::setAlerta('error', 'Password Incorrecto');
                    $alertas = $usuario->getAlertas();
                }
            }
        }


        $router->render('dashboard/cambiar-password', [
            'titulo' => 'Cambiar Password',
            'alertas' => $alertas
        ]);
    }
}

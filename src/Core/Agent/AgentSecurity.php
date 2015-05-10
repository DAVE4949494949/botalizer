<?php

namespace Core\Agent;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Core\Repository;
use Core\Template;
use Core\Agent\Users;

class AgentSecurity
{

    static function Registration()
    {
        if (Repository::$data['roleuser'] == 0)
        {
            $request = new Request($_POST);
            Repository::$data['error'] = array();
            if ($request->query->get('send') == 1)
            {
                # проверям логин 
                if (!filter_var($request->query->get('email'), FILTER_VALIDATE_EMAIL))
                {
                    Repository::$data['error'][] = "Не правильный формат Email";
                }
                if (Users::CheckUser($request->query->get('email')))
                {
                    Repository::$data['error'][] = "Email был зарегистрирован ранее";
                }
                if (strlen($request->query->get('password')) < 6 or strlen($request->query->get('password')) > 30)
                {
                    Repository::$data['error'][] = "Пароль должен быть от 6-х до 30 символов";
                }
                if (strlen($request->query->get('name')) < 3 or strlen($request->query->get('name')) > 30)
                {
                    Repository::$data['error'][] = "Имя должено быть от 3-х до 30 символов";
                }
                # Если нет ошибок, то добавляем в БД нового пользователя 
                if (count(Repository::$data['error']) == 0)
                {
                    $email = $request->query->get('email');
                    $password = md5(md5(trim($request->query->get('password'))));
                    $name = $request->query->get('name');
                    //$id = Users::AddNewUser($email, $password, $name);
                    if ($id = Users::AddNewUser($email, $password, $name))
                    {
                        $_SESSION["id"] = $id;
                        $_SESSION["email"] = $email;
                        $_SESSION["password"] = $password;
                        $_SESSION["roleuser"] = Repository::RolesUsers(1);
                        $_SESSION["loggedIn"] = true;
                        return new RedirectResponse('/login');
                    }
                }
            }
        }else{
            return new RedirectResponse('/');
        }

        $html = Template::RenderTemplate('pages/registration.twig', Repository::$data);
        return new Response($html);
    }

    public static function Login()
    {
        $request = new Request($_POST);
        if (Repository::$data['roleuser'] == 0)
        {
            Repository::$data['error'] = array();
            $email = $request->query->get('email');
            $password = $request->query->get('password');
            if ($request->query->get('send') == 1)
            {
                if (empty($email))
                {
                    Repository::$data['error'][] = "Поле Email обязательно для заполнения";
                } else
                {
                    # проверям логин 
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                    {
                        Repository::$data['error'][] = "Не правильный формат Email";
                    }
                }

                if (empty($password))
                {
                    Repository::$data['error'][] = "Поле Пароль обязательно для заполнения";
                } else
                {
                    if (strlen($password) < 6 or strlen($password) > 30)
                    {
                        Repository::$data['error'][] = "Пароль должен быть от 6-х до 30 символов";
                    }
                }
                # Если нет ошибок, то обрабатываем пользователя 
                if (count(Repository::$data['error']) == 0)
                {
                    $user = Users::getUserID($email, $password);
                    if (count(Repository::$data['error']) == 0)
                    {
                        $_SESSION["id"] = $user->id;
                        $_SESSION["email"] = $user->email;
                        $_SESSION["password"] = $user->password;
                        $_SESSION["roleuser"] = $user->roleuser;
                        $_SESSION["loggedIn"] = true;
                        Repository::$data["roleuser"] = $_SESSION["roleuser"];
                        return new RedirectResponse('/');
                    }
                }
            }

            $html = Template::RenderTemplate('pages/login.twig', Repository::$data);
            return new Response($html);
        } else
        {
            return new RedirectResponse('/');
        }
    }

    public static function Auth()
    {
        # проверка авторизации
        if (isset($_COOKIE['id']) and isset($_COOKIE['hash']))
        {
            $userdata = Users::CheckAuth($_COOKIE['id']);

            if (($userdata['hash'] !== $_COOKIE['hash']) or ($userdata['id'] !== $_COOKIE['id']))
            {
                setcookie('id', '', time() - 60 * 24 * 30 * 12, '/');
                setcookie('hash', '', time() - 60 * 24 * 30 * 12, '/');
                setcookie('errors', '1', time() + 60 * 24 * 30 * 12, '/');
                header('Location: /login');
                exit();
            } else
            {
                setcookie('errors', '2', time() + 60 * 24 * 30 * 12, '/');
                header('Location: /login');
                exit();
            }
        }
    }

    public static function CheckLogIn()
    {
        # Запуск сессии
        session_start();
        # Служит для отладки, показывает все ошибки, предупреждения и т.д.
        error_reporting(E_ALL);
        // Если не авторизирован
        if (!isset($_SESSION["loggedIn"]))
        {
            return 0;
        }
        if (isset($_SESSION["loggedIn"]) && $_SESSION["loggedIn"] === true)
        {
            return $_SESSION["roleuser"];
        }
    }

    public static function Logout()
    {
        if (Repository::$data['roleuser'] > 0)
        {
            unset($_SESSION["id"]);
            unset($_SESSION["email"]);
            unset($_SESSION["password"]);
            unset($_SESSION["loggedIn"]);
            unset($_SESSION["roleuser"]);
            session_destroy();
        }
        return new RedirectResponse('/login');
    }

}


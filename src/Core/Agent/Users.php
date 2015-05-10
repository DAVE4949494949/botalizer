<?php

namespace Core\Agent;

use RedBean_Facade as R;
use Core\Repository;

class Users
{

    static public function Connect()
    {
        $data = Repository::GetGonfig();
        R::setup('mysql:host=' . $data['host'] . ';dbname=' . $data['dbname'], $data['user'], $data['password']);
    }

    static public function CheckUser($email)
    {
        self::Connect();
        $user = R::find('users', ' email = ?', array($email));
        return (!empty($user));
    }

    static public function AddNewUser($email, $password, $name)
    {
        self::Connect();
        $user = R::findOne('users', ' email = ?', array($email));
        if(!$user){
            $user = R::dispense('users');
            $user->email = $email;
            $user->password = $password;
            $user->name = $name;
            $user->roleuser = 1;
            $id = R::store($user);
            return $id;
        }else{
            return FALSE;
        }
    }

    static function getUserID($email, $password)
    {
        self::Connect();
        $user = R::findOne('users', ' email = ?', array($email));
        if ($user === FALSE or empty($user))
        {
            Repository::$data['error'][] = "Пользователь с таким Email не зарегистрирован";
        } else
        {
            if ($user->password !== md5(md5($password)))
            {
                echo $user->password .' - НЕРАВНО - '.md5(md5($password));
                //print_r($user);
                //exit('ошибка');
                Repository::$data['error'][] = "Неверный пароль";
            } else
            {
                return $user;
            }
        }
    }

    # Функция для генерации случайной строки 

    static function generateCode($length = 6)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
        $code = "";
        $clen = strlen($chars) - 1;
        while (strlen($code) < $length)
        {
            $code .= $chars[mt_rand(0, $clen)];
        }
        return $code;
    }
    public static function CheckAuth($id)
    {
        self::Connect();
        $user = R::findOne('users', ' id = ?', array($id));
        return $user;
    }

}
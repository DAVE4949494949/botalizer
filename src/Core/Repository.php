<?php

namespace Core;

class Repository
{

    static $config;
    public static $data = array();
    
    public static function GetGonfig()
    {
        if (empty(self::$config))
        {
            self::$config = include_once __DIR__ . '/../config.php';
        }
        return self::$config;
    }

    public static function RolesUsers($role = 0)
    {
        self::$data['roleuser']= $role;
        return self::$data['roleuser'];
    }

}

<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Core\Agent\AgentSecurity;
use Core\Repository;
$request = Request::createFromGlobals();
$sc = include __DIR__.'/src/container.php';
Twig_Autoloader::register();
Repository::$data['roleuser'] = AgentSecurity::CheckLogIn();
if(Repository::$data['roleuser'] > 0){
    Repository::$data['user_id'] =  $_SESSION["id"];
  }else{
      Repository::$data['user_id'] = 0;
  }
    

Repository::GetGonfig();
$response = $sc->get('framework')->handle($request);
$response->send();

function pre($data , $die = false)
{
    echo '<pre>' . print_r($data, 1) . '</pre>' . "\n\r";
    if($die)
        exit();
}
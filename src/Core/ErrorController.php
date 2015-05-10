<?php

namespace Core;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\FlattenException;

class ErrorController
{
    public function exceptionAction(FlattenException $exception)
    {
        $msg = 'Ошибка! (' . $exception->getMessage() . ')';
        return new Response($msg, $exception->getStatusCode());
    }
}



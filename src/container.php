<?php

use Symfony\Component\DependencyInjection;
use Symfony\Component\DependencyInjection\Reference;

$routes = include __DIR__.'/../routers.php';

$sc = new DependencyInjection\ContainerBuilder();
$sc->register('context', 'Symfony\Component\Routing\RequestContext');
$sc->register('matcher', 'Symfony\Component\Routing\Matcher\UrlMatcher')
        ->setArguments(array($routes, new Reference('context')))
;
$sc->register('resolver', 'Symfony\Component\HttpKernel\Controller\ControllerResolver');

$sc->register('listener.router', 'Symfony\Component\HttpKernel\EventListener\RouterListener')
        ->setArguments(array(new Reference('matcher')))
;
$sc->register('listener.response', 'Symfony\Component\HttpKernel\EventListener\ResponseListener')
        ->setArguments(array('UTF-8'))
;
$sc->register('listener.exception', 'Symfony\Component\HttpKernel\EventListener\ExceptionListener')
        ->setArguments(array('Core\\ErrorController::exceptionAction'))
;
$sc->register('dispatcher', 'Symfony\Component\EventDispatcher\EventDispatcher')
        ->addMethodCall('addSubscriber', array(new Reference('listener.router')))
        ->addMethodCall('addSubscriber', array(new Reference('listener.response')))
        ->addMethodCall('addSubscriber', array(new Reference('listener.exception')))
;
$sc->register('framework', 'Core\Framework')
        ->setArguments(array(new Reference('dispatcher'), new Reference('resolver')))
;
$sc->register('listener.string_response', 'Core\StringResponseListener');
$sc->getDefinition('dispatcher')
        ->addMethodCall('addSubscriber', array(new Reference('listener.string_response')))
;
return $sc;
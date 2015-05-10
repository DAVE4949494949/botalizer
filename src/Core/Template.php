<?php

namespace Core;
use Core\Repository;
class Template{
   
    public static function RenderTemplate($template_file,$data)
    {
       $dir_cache = FALSE;
       if(Repository::$config['cache_template']){
           $dir_cache = __DIR__.'/../../temp/cache/compilation_cache';
       }
        $loader = new \Twig_Loader_Filesystem(__DIR__.'/../../templates');
        $twig = new \Twig_Environment($loader, array(
            'cache' => $dir_cache,
              ));
        $html = $twig->render($template_file, $data);
        return $html;
    }
}
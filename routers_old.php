<?php

use Symfony\Component\Routing;

$routes = new Routing\RouteCollection();

//$routes->add('sitemap', new Routing\Route('/sitemap', array(
//    '_controller' => 'Userset\\Analiz\\Models\\Analiz::Sitemap')
//        )
//);

$routes->add('update', new Routing\Route('/update/{url}', array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::UpdateAction')
    )
);

$routes->add('update_categories', new Routing\Route('/update-cat/{url}', array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::UpdateAction')
    )
);

$routes->add('create', new Routing\Route('/create/{short_urlshort_url}', array(
            '_controller' => 'Userset\\ShortUrl\\Controllers\\ShortUrlController::CreateAction')
    )
);

$routes->add('result_analizEN', new Routing\Route('/website/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionEN')
    )
);

$routes->add('checksite', new Routing\Route('/checksite/{id}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::showProgressBar')
    )
);

$routes->add('fastchecksite', new Routing\Route('/fastchecksite/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::fastCheckSite')
    )
);


$routes->add('analizEN', new Routing\Route('/', array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::RunActionEN')
    )
);

$routes->add('analizParseEN', new Routing\Route('/run/', array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::RunParseActionEN')
    )
);

/**
 * Маршрут к главной странице
 */
$routes->add('home', new Routing\Route('/homeaction222222222222222', array(
            '_controller' => 'Userset\\ShortUrl\\Controllers\\ShortUrlController::HomeAction')
    )
);

/**
 * Маршрут к sitemap
 */
$routes->add('sitemap', new Routing\Route('/sitemap.xml', array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::SitemapAction')
    )
);

$routes->add('analiz_category', new Routing\Route('/category/{category}', array(
           '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ShowCategory')
    )
);
return $routes;
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
            '_controller' =>
            'Userset\\Analiz\\Controllers\\SeoAnalizController::UpdateCategoriesAction')
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

$routes->add('result_analizFR', new Routing\Route('/website/fr/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionFR')
    )
);

$routes->add('result_analizDE', new Routing\Route('/website/de/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionDE')
    )
);

$routes->add('result_analizES', new Routing\Route('/website/es/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionES')
    )
);

$routes->add('result_analizIT', new Routing\Route('/website/it/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionIT')
    )
);

$routes->add('result_analizJA', new Routing\Route('/website/ja/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionJA')
    )
);

$routes->add('result_analizNL', new Routing\Route('/website/nl/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionNL')
    )
);

$routes->add('result_analizJA', new Routing\Route('/website/ja/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionJA')
    )
);

$routes->add('result_analizTR', new Routing\Route('/website/tr/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionTR')
    )
);

$routes->add('result_analizPL', new Routing\Route('/website/pl/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionPL')
    )
);

$routes->add('result_analizRO', new Routing\Route('/website/ro/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionRO')
    )
);

$routes->add('result_analizKO', new Routing\Route('/website/ko/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionKO')
    )
);

$routes->add('result_analizZH', new Routing\Route('/website/zh/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionZH')
    )
);

$routes->add('result_analizPT', new Routing\Route('/website/pt/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionPT')
    )
);

$routes->add('result_analizID', new Routing\Route('/website/id/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionID')
    )
);

$routes->add('result_analizBR', new Routing\Route('/website/br/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionBR')
    )
);

$routes->add('result_analizRU', new Routing\Route('/website/ru/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionRU')
    )
);

$routes->add('result_analizHI', new Routing\Route('/website/hi/{url}',
        array(
            '_controller' => 'Userset\\Analiz\\Controllers\\SeoAnalizController::ResultActionHI')
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
<?php

namespace Userset\Analiz\Controllers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Userset\Analiz\Module\Url;
use Userset\Analiz\Module\CatCheck;
use Userset\Analiz\Module\IPSitesList;
use Userset\Analiz\Module\geoPlugin;
use Core\Repository;
use Core\Template;
use Userset\ShortUrl\Models\ShortUrlModel;
use Userset\Analiz\Models\Analiz;

class SeoAnalizController
{

    /**
     * Ключевой метод , выполнение при создании анализа сайта.
     * Маршрут route:/analiz/seo
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */


    static function createTask()
    {
        $request = new Request($_REQUEST);
        Url::$url = $request->query->get('siteurl');
        $url = strtolower(Url::getUrl());
        if (\Userset\Analiz\Models\Analiz::CheckSite($url)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo '/website/' . $url;
                exit();
            } else {
                return new RedirectResponse(Repository::$config['base_url'] . '/website/' . $url);
            }
        }
        $taskId = \Userset\Analiz\Models\Analiz::getTaskId($url);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo '/checksite/' . $taskId;
            exit();
        }
        return new RedirectResponse(Repository::$config['base_url'] . '/checksite/' . $taskId);
    }

    static function fastCheckSite($url)
    {
//        error_reporting(E_ALL);
//        ini_set('display_errors', 1);
        Url::$url = $url;
        $url = strtolower(Url::getUrl());
        if (\Userset\Analiz\Models\Analiz::CheckSite($url)) {
            return new RedirectResponse(Repository::$config['base_url'] . '/website/' . $url);
        }
        $taskId = \Userset\Analiz\Models\Analiz::getTaskId($url);

        $json = json_decode(file_get_contents('http://127.0.0.1:8081/api/?action=check&id=' . $taskId));
        pre($json, 1);
        if (!isset($json->success) || isset($json->error)) {
            echo 'ERROR';
        } else {
            echo 'OK!';
        }
//        pre($json, 1);
        die();
        /*$fp = fsockopen("127.0.0.1", 5000, $errno, $errstr, 30);
        if (!$fp) {
            echo "$errstr ($errno)<br />\n";
        } else {
            $out = "paramAdd id $taskId\r\n";
            $out .= "check\r\n";
            fwrite($fp, $out);
            $response = '';
            
            $i = 0;
            fclose($fp);
            echo 'OK!';
            while (!feof($fp)) {
                $i++;
                $response .= fgets($fp, 4096);
                if(strpos($response, '{"status":"Bye!","context":"api"}') !== false){
                    break;
                }
            }
            if(($response = json_encode($response)) && !isset($response->error)){
                return new RedirectResponse(Repository::$config['base_url'] . '/website/' . $url);
            }else{
                echo 'Error!';
            }
            fclose($fp);
        }*/
    }


    static function showProgressBar($id)
    {
        global $sc;
        $html = Template::RenderTemplate('progress.twig', array('taskId' => $id));
        return new Response($html);
    }

    static public function ShowCategory($category)
    {
        Repository::$data['last_result_analiz_link'] = \Userset\Analiz\Models\Analiz::LastLinkAnalizByCategory($category);
        Repository::$data['categoryName'] = $category;
        $html = Template::RenderTemplate('pages/category.twig', (array)Repository::$data);
        return new Response($html);
    }

    static public function RunParseActionEN()
    {
        // Получение массива POST данных формы
        $request = new Request($_GET);
        // Если кнопка нажата выполнять обработку
        if ($request->query->get('act') === 'parse') {
            // Уровень показа ошибок!
            //     error_reporting(0);
            // Получение URL из формы
            $url = $request->query->get('siteurl');
            Repository::$data['status_analiz'] = Url::Init($url);

            $parse_url = parse_url($url);

            // проверка на adult
            $adult_words = array();
            if (file_exists(dirname(dirname(dirname(__DIR__))) . '/adult.txt')) {
                $adult_words = file_get_contents(dirname(dirname(dirname(__DIR__))) . '/adult.txt');
                $adult_words = explode("\n", $adult_words);
                foreach ($adult_words as &$word) {
                    $word = trim($word, " \r");
                }
            }
            if (Url::IsAdult($adult_words, 3) || array_intersect(array('Sexual Materials', 'Pornography'), self::getCategory($url))) {
                Repository::$data['error'][] = 'URL: ' . $url . ' contains adult content';
                Repository::$data['status_analiz'] = FALSE;
                // проверка на содержание www в начале
            } elseif ((isset($parse_url['host']) && strpos(trim($parse_url['host']), 'www.') !== false) || (isset($parse_url['path']) && strpos(trim($parse_url['path']), 'www.') !== false)) {
                Repository::$data['error'][] = 'Please enter the URL address ' . $url . ' without ".www"';
                Repository::$data['status_analiz'] = FALSE;
            } // Запуск обработки URL
            elseif (Repository::$data['status_analiz'] === TRUE) {
                // Получить URL
                Repository::$data['url'] = strtolower(Url::getUrl());
                // Если сайт есть базе данных перенаправить
                if (\Userset\Analiz\Models\Analiz::CheckSite(Repository::$data['url'])) {
                    return new RedirectResponse(Repository::$config['base_url'] . '/website/' . Repository::$data['url']);
                }
                // Получить IP
                Repository::$data['ip'] = Url::$ip_adres;

                // Постер сайта
                Repository::$data['screen_url'] = 'http://api.s-shot.ru/1024x1024/PNG/1024/KEYNJ7EWBQ57EJV5QE7/Z100/T0/D0/JS1/FS1/?' . Repository::$data['url'];
                $image_name_file = filter_var(Repository::$data['url'], FILTER_SANITIZE_STRING);
                $image_name_file = 'datashots/img/' . $image_name_file . '.png';
                if (!is_file($image_name_file)) {
                    if (!@copy(Repository::$data['screen_url'], $image_name_file)) {
                        Repository::$data['screen_url'] = 'http://api.s-shot.ru/1024x1024/PNG/1024/KEYNJ7EWBQ57EJV5QE7/Z100/T0/D0/JS1/FS1/?' . Repository::$data['url'];
                        Repository::$data['error'][] = " Ошибка загрузки постера";
                    }
                }

                Repository::$data['time'] = Url::$load_time;
                Repository::$data['speed'] = Url::$speed;
                Repository::$data['h1'] = Url::$h1;

                $cat = new CatCheck();
                Repository::$data['cat_google'] = $cat->CheckInDMOZ(Repository::$data['url']);
                Repository::$data['cat_yahoo'] = $cat->CheckInYahoo(Repository::$data['url']);
                Repository::$data['cat_safebrowsing'] = $cat->CheckInSafeBrowsing(Repository::$data['url']);
                Repository::$data['cat_norton_safe_web'] = $cat->CheckInNortonSafeWeb(Repository::$data['url']);

                Repository::$data['alexa_visitors_by_country'] = self::GetAlexaVisitorsByCountry(Repository::$data['url']);

                //список ip
                $ipsitesinfo = new IPSitesList();
                $ipsitesinfo->GetIP_info(Repository::$data['url']);

                //расположение датацентра
                $geopluginq = new geoPlugin();
                $geopluginq->locate(Repository::$data['ip']);
                Repository::$data['server_countryName'] = $geopluginq->countryName;
                Repository::$data['server_countryCode'] = $geopluginq->countryCode;
                Repository::$data['server_city'] = $geopluginq->city;
                Repository::$data['server_region'] = $geopluginq->region;
                Repository::$data['ip_count'] = $ipsitesinfo->sitescount;
                Repository::$data['ip_count_url'] = $ipsitesinfo->lookmore;
                Repository::$data['ip_hosting'] = gethostbyaddr(gethostbyname(Repository::$data['url']));

                Repository::$data['keywords'] = Url::$keywords_url;
                Repository::$data['description'] = Url::$description_url;
                Repository::$data['dns_ns'] = Url::getNS();
                Repository::$data['google_images'] = Url::$GoogleImages;

                Repository::$data['pagerank'] = !empty(Url::$PageRank) ? Url::$PageRank : 0;

                Repository::$data['alexa_rank'] = self::GetAlexaRankXML(Repository::$data['url']);
                Repository::$data['alexa_delta_rank'] = self::GetAlexaDeltaRankXML(Repository::$data['url']);

                $index_links = self::getPageLinks($url);
                Repository::$data['in_indexing_links'] = $index_links[0];
                Repository::$data['in_noindex_links'] = $index_links[1];
                Repository::$data['out_indexing_links'] = $index_links[2];
                Repository::$data['out_noindex_links'] = $index_links[3];

                $content_words = self::getContentWords($url);
                Repository::$data['symbols'] = $content_words[1];
                Repository::$data['words'] = $content_words[2];
                Repository::$data['unique_words'] = $content_words[4];
                Repository::$data['stopwords'] = $content_words[5];
                Repository::$data['content_percent'] = $content_words[6];

                $w3c_html_validator = self::getW3CHTMLValidator($url);
                Repository::$data['html_errors'] = $w3c_html_validator[0];
                Repository::$data['html_warnings'] = $w3c_html_validator[1];
                Repository::$data['doctype'] = $w3c_html_validator[2];
                Repository::$data['charset'] = $w3c_html_validator[3];

                $w3c_css_validator = self::getW3CCSSValidator($url);
                Repository::$data['css_errors'] = $w3c_css_validator[0];
                Repository::$data['css_warnings'] = $w3c_css_validator[1];

                $category = self::getCategory($url);
                Repository::$data['category'] = $category[0];

                $soc_facebook = self::getSocFacebook($url);
                Repository::$data['facebook_shares'] = $soc_facebook[2];
                Repository::$data['facebook_likes'] = $soc_facebook[3];
                Repository::$data['facebook_comments'] = $soc_facebook[4];

                $soc_tweets = self::getSocTweets($url);
                Repository::$data['tweets'] = print_r($soc_tweets->count, 1);

                $soc_gplus = self::getSocGPlus($url);
                Repository::$data['gplus'] = $soc_gplus[0];

                $resources = self::getResources($url);
                Repository::$data['address'] = print_r($resources->id, 1);
                Repository::$data['title'] = print_r($resources->title, 1);
                Repository::$data['score'] = print_r($resources->score, 1);
                Repository::$data['size'] = print_r($resources->pageStats->htmlResponseBytes, 1);
                Repository::$data['css'] = print_r($resources->pageStats->cssResponseBytes, 1);
                Repository::$data['images'] = print_r($resources->pageStats->imageResponseBytes, 1);
                Repository::$data['javascript'] = print_r($resources->pageStats->javascriptResponseBytes, 1);
                Repository::$data['other'] = print_r($resources->pageStats->otherResponseBytes, 1);

                $traffic = self::getTraffic($url);
                Repository::$data['visits'] = $traffic[0];

                $alexa_pages = self::getAlexaPages($url);
                Repository::$data['alexa_pages'] = $alexa_pages[0];

                $headers = self::getHeaders($url);
                Repository::$data['status'] = $headers[0];
                Repository::$data['server'] = $headers[1];;

                $index_backlinks = self::getIndexBacklinks($url);
                Repository::$data['google_pages'] = $index_backlinks[0];
                Repository::$data['google_main_pages'] = $index_backlinks[1];
                Repository::$data['yahoo_pages'] = $index_backlinks[2];
                Repository::$data['yahoo_images'] = $index_backlinks[3];
                Repository::$data['domain_authority'] = $index_backlinks[4];
                Repository::$data['backlinks_domains'] = $index_backlinks[6];
                Repository::$data['backlinks_gov_domain'] = $index_backlinks[7];
                Repository::$data['backlinks_edu_domain'] = $index_backlinks[8];
                Repository::$data['backlinks_ips'] = $index_backlinks[9];
                Repository::$data['backlinks_subnets'] = $index_backlinks[10];
                Repository::$data['backlinks_all'] = $index_backlinks[11];
                Repository::$data['backlinks_text'] = $index_backlinks[12];
                Repository::$data['backlinks_nofollow'] = $index_backlinks[13];
                Repository::$data['backlinks_redirect'] = $index_backlinks[14];
                Repository::$data['backlinks_images'] = $index_backlinks[15];
                Repository::$data['backlinks_gov_links'] = $index_backlinks[18];
                Repository::$data['backlinks_edu_links'] = $index_backlinks[19];
                Repository::$data['registrar'] = $index_backlinks[20];
                Repository::$data['whois'] = $index_backlinks[21];
                Repository::$data['domain_created'] = $index_backlinks[22];
                Repository::$data['domain_expires'] = $index_backlinks[23];

                Repository::$data['favicon'] = self::DownloadFavicon($url);

                if (!isset(Repository::$data['error'])) {
                    if (Repository::$config['write_result_in_db']) {
                        \Userset\Analiz\Models\Analiz::SaveAnaliz(Repository::$data);
                        return new RedirectResponse(Repository::$config['base_url'] . '/website/' . Repository::$data['url']);
                    }
                }
            } else {
                Repository::$data['error'][] = "Sorry, but URL: " . $url . " cant't be analyzed.";
                Repository::$data['status_analiz'] = FALSE;
            }
        } else {
            Repository::$data['last_result_analiz_link'] = \Userset\Analiz\Models\Analiz::LastLinkAnaliz();
        }

        $html = Template::RenderTemplate('pages/analiz.twig', (array)Repository::$data);
        return new Response($html);
    }

    static public function RunActionEN()
    {
        // Получение массива POST данных формы
        $request = new Request($_POST);
        // Если кнопка нажата выполнять обработку
//        pre($request->query, 1);
        if ($request->query->get('act') === 'do') {

            return self::createTask();
            // Уровень показа ошибок!
            //     error_reporting(0);
            // Получение URL из формы
            $url = $request->query->get('siteurl');
            Repository::$data['status_analiz'] = Url::Init($url);

            $parse_url = parse_url($url);

            // проверка на adult
            $adult_words = array();
            if (file_exists(dirname(dirname(dirname(__DIR__))) . '/adult.txt')) {
                $adult_words = file_get_contents(dirname(dirname(dirname(__DIR__))) . '/adult.txt');
                $adult_words = explode("\n", $adult_words);
                foreach ($adult_words as &$word) {
                    $word = trim($word, " \r");
                }
            }
            if (Url::IsAdult($adult_words, 3)) {
                Repository::$data['error'][] = 'URL: ' . $url . ' contains adult content';
                Repository::$data['status_analiz'] = FALSE;
                // проверка на содержание www в начале
            } elseif ((isset($parse_url['host']) && strpos(trim($parse_url['host']), 'www.') !== false) || (isset($parse_url['path']) && strpos(trim($parse_url['path']), 'www.') !== false)) {
                Repository::$data['error'][] = 'Please enter the URL address ' . $url . ' without ".www"';
                Repository::$data['status_analiz'] = FALSE;
            } // Запуск обработки URL
            elseif (Repository::$data['status_analiz'] === TRUE) {
                // Получить URL
                Repository::$data['url'] = strtolower(Url::getUrl());
                // Если сайт есть базе данных перенаправить
                if (\Userset\Analiz\Models\Analiz::CheckSite(Repository::$data['url'])) {
                    return new RedirectResponse(Repository::$config['base_url'] . '/website/' . Repository::$data['url']);
                }
                // Получить IP
                Repository::$data['ip'] = Url::$ip_adres;

                // Постер сайта
                Repository::$data['screen_url'] = 'http://api.s-shot.ru/1024x1024/PNG/1024/KEYNJ7EWBQ57EJV5QE7/Z100/T0/D0/JS1/FS1/?' . Repository::$data['url'];
                $image_name_file = filter_var(Repository::$data['url'], FILTER_SANITIZE_STRING);
                $image_name_file = 'datashots/img/' . $image_name_file . '.png';
                if (!is_file($image_name_file)) {
                    if (!@copy(Repository::$data['screen_url'], $image_name_file)) {
                        Repository::$data['screen_url'] = 'http://api.s-shot.ru/1024x1024/PNG/1024/KEYNJ7EWBQ57EJV5QE7/Z100/T0/D0/JS1/FS1/?' . Repository::$data['url'];
                        Repository::$data['error'][] = " Ошибка загрузки постера";
                    }
                }

                Repository::$data['time'] = Url::$load_time;
                Repository::$data['speed'] = Url::$speed;
                Repository::$data['h1'] = Url::$h1;

                $cat = new CatCheck();
                Repository::$data['cat_google'] = $cat->CheckInDMOZ(Repository::$data['url']);
                Repository::$data['cat_yahoo'] = $cat->CheckInYahoo(Repository::$data['url']);
                Repository::$data['cat_safebrowsing'] = $cat->CheckInSafeBrowsing(Repository::$data['url']);
                Repository::$data['cat_norton_safe_web'] = $cat->CheckInNortonSafeWeb(Repository::$data['url']);

                Repository::$data['alexa_visitors_by_country'] = self::GetAlexaVisitorsByCountry(Repository::$data['url']);

                //список ip
                $ipsitesinfo = new IPSitesList();
                $ipsitesinfo->GetIP_info(Repository::$data['url']);

                //расположение датацентра
                $geopluginq = new geoPlugin();
                $geopluginq->locate(Repository::$data['ip']);
                Repository::$data['server_countryName'] = $geopluginq->countryName;
                Repository::$data['server_countryCode'] = $geopluginq->countryCode;
                Repository::$data['server_city'] = $geopluginq->city;
                Repository::$data['server_region'] = $geopluginq->region;
                Repository::$data['ip_count'] = $ipsitesinfo->sitescount;
                Repository::$data['ip_count_url'] = $ipsitesinfo->lookmore;
                Repository::$data['ip_hosting'] = gethostbyaddr(gethostbyname(Repository::$data['url']));

                Repository::$data['keywords'] = Url::$keywords_url;
                Repository::$data['description'] = Url::$description_url;
                Repository::$data['dns_ns'] = Url::getNS();
                Repository::$data['google_images'] = Url::$GoogleImages;

                Repository::$data['pagerank'] = !empty(Url::$PageRank) ? Url::$PageRank : 0;

                Repository::$data['alexa_rank'] = self::GetAlexaRankXML(Repository::$data['url']);
                Repository::$data['alexa_delta_rank'] = self::GetAlexaDeltaRankXML(Repository::$data['url']);

                $index_links = self::getPageLinks($url);
                Repository::$data['in_indexing_links'] = $index_links[0];
                Repository::$data['in_noindex_links'] = $index_links[1];
                Repository::$data['out_indexing_links'] = $index_links[2];
                Repository::$data['out_noindex_links'] = $index_links[3];

                $content_words = self::getContentWords($url);
                Repository::$data['symbols'] = $content_words[1];
                Repository::$data['words'] = $content_words[2];
                Repository::$data['unique_words'] = $content_words[4];
                Repository::$data['stopwords'] = $content_words[5];
                Repository::$data['content_percent'] = $content_words[6];

                $w3c_html_validator = self::getW3CHTMLValidator($url);
                Repository::$data['html_errors'] = $w3c_html_validator[0];
                Repository::$data['html_warnings'] = $w3c_html_validator[1];
                Repository::$data['doctype'] = $w3c_html_validator[2];
                Repository::$data['charset'] = $w3c_html_validator[3];

                $w3c_css_validator = self::getW3CCSSValidator($url);
                Repository::$data['css_errors'] = $w3c_css_validator[0];
                Repository::$data['css_warnings'] = $w3c_css_validator[1];

                $category = self::getCategory($url);
                Repository::$data['category'] = $category[0];

                $soc_facebook = self::getSocFacebook($url);
                Repository::$data['facebook_shares'] = $soc_facebook[2];
                Repository::$data['facebook_likes'] = $soc_facebook[3];
                Repository::$data['facebook_comments'] = $soc_facebook[4];

                $soc_tweets = self::getSocTweets($url);
                Repository::$data['tweets'] = print_r($soc_tweets->count, 1);

                $soc_gplus = self::getSocGPlus($url);
                Repository::$data['gplus'] = $soc_gplus[0];

                $resources = self::getResources($url);
                Repository::$data['address'] = print_r($resources->id, 1);
                Repository::$data['title'] = print_r($resources->title, 1);
                Repository::$data['score'] = print_r($resources->score, 1);
                Repository::$data['size'] = print_r($resources->pageStats->htmlResponseBytes, 1);
                Repository::$data['css'] = print_r($resources->pageStats->cssResponseBytes, 1);
                Repository::$data['images'] = print_r($resources->pageStats->imageResponseBytes, 1);
                Repository::$data['javascript'] = print_r($resources->pageStats->javascriptResponseBytes, 1);
                Repository::$data['other'] = print_r($resources->pageStats->otherResponseBytes, 1);

                $traffic = self::getTraffic($url);
                Repository::$data['visits'] = $traffic[0];

                $alexa_pages = self::getAlexaPages($url);
                Repository::$data['alexa_pages'] = $alexa_pages[0];

                $headers = self::getHeaders($url);
                Repository::$data['status'] = $headers[0];
                Repository::$data['server'] = $headers[1];

                $index_backlinks_without_proxy = self::getIndexBacklinksWithoutProxy($url);
                Repository::$data['google_pages'] = $index_backlinks_without_proxy[0];
                Repository::$data['google_main_pages'] = $index_backlinks_without_proxy[1];
                Repository::$data['yahoo_pages'] = $index_backlinks_without_proxy[2];
                Repository::$data['yahoo_images'] = $index_backlinks_without_proxy[3];
                Repository::$data['domain_authority'] = $index_backlinks_without_proxy[4];
                Repository::$data['backlinks_domains'] = $index_backlinks_without_proxy[6];
                Repository::$data['backlinks_gov_domain'] = $index_backlinks_without_proxy[7];
                Repository::$data['backlinks_edu_domain'] = $index_backlinks_without_proxy[8];
                Repository::$data['backlinks_ips'] = $index_backlinks_without_proxy[9];
                Repository::$data['backlinks_subnets'] = $index_backlinks_without_proxy[10];
                Repository::$data['backlinks_all'] = $index_backlinks_without_proxy[11];
                Repository::$data['backlinks_text'] = $index_backlinks_without_proxy[12];
                Repository::$data['backlinks_nofollow'] = $index_backlinks_without_proxy[13];
                Repository::$data['backlinks_redirect'] = $index_backlinks_without_proxy[14];
                Repository::$data['backlinks_images'] = $index_backlinks_without_proxy[15];
                Repository::$data['backlinks_gov_links'] = $index_backlinks_without_proxy[18];
                Repository::$data['backlinks_edu_links'] = $index_backlinks_without_proxy[19];
                Repository::$data['registrar'] = $index_backlinks[20];
                Repository::$data['whois'] = $index_backlinks[21];
                Repository::$data['domain_created'] = $index_backlinks[22];
                Repository::$data['domain_expires'] = $index_backlinks[23];

                Repository::$data['favicon'] = self::DownloadFavicon($url);

                if (!isset(Repository::$data['error'])) {
                    if (Repository::$config['write_result_in_db']) {
                        \Userset\Analiz\Models\Analiz::SaveAnaliz(Repository::$data);
                        return new RedirectResponse(Repository::$config['base_url'] . '/website/' . Repository::$data['url']);
                    }
                }
            } else {
                Repository::$data['error'][] = "Sorry, but URL: " . $url . " cant't be analyzed.";
                Repository::$data['status_analiz'] = FALSE;
            }
        } else {
            Repository::$data['last_result_analiz_link'] = \Userset\Analiz\Models\Analiz::LastLinkAnaliz();
        }

        $html = Template::RenderTemplate('pages/analiz.twig', (array)Repository::$data);
        return new Response($html);
    }

    static public function UpdateAction($url)
    {
//        error_reporting(E_ALL);
//        ini_set('display_errors', 0);
        Url::$url = $url;
        $url = strtolower(Url::getUrl());
        if (Analiz::CheckSite($url)) {
            if (true || Analiz::ChekDateUpdate($url)) {

                $taskId = Analiz::getTaskId($url);
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo '/checksite/' . $taskId;
                    exit();
                }

                $html = Template::RenderTemplate('progress.twig', array('taskId' => $taskId));
                return new Response($html);
//                return new RedirectResponse(Repository::$config['base_url'] . '/checksite/' . $taskId);
            }
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo '/website/' . $url;
                exit();
            } else {
                return new RedirectResponse(Repository::$config['base_url'] . '/website/' . $url);
            }
        }
        return new RedirectResponse(Repository::$config['base_url']);
//        pre(1, 1);

        // Уровень показа ошибок!
//        if (isset($_GET['testim'])) {
//            error_reporting(E_ALL);
//            ini_set('display_errors', 0);
//        }
        // Запуск обработки URL
        Repository::$data['status_analiz'] = Url::Init($url);

        if (Repository::$data['status_analiz'] === TRUE) {
            // Получить URL
//            Repository::$data['url'] = Url::$url;
            Repository::$data['url'] = strtolower(Url::getUrl());
            // Если сайт есть базе данных перенаправить
            if (!\Userset\Analiz\Models\Analiz::ChekDateUpdate(Repository::$data['url'])) {
                Repository::$data['error'][] = "Analyze can be done only 1 time in a day.";
                Repository::$data['status_analiz'] = FALSE;
            } else {

                // Получить IP
                Repository::$data['ip'] = Url::$ip_adres;

                // Постер сайта
                Repository::$data['screen_url'] = 'http://api.s-shot.ru/1024x1024/PNG/1024/KEYNJ7EWBQ57EJV5QE7/Z100/T0/D0/JS1/FS1/?' . Repository::$data['url'];
                $image_name_file = filter_var(Repository::$data['url'], FILTER_SANITIZE_STRING);
                $image_name_file = 'datashots/img/' . $image_name_file . '.png';
                if (!is_file($image_name_file)) {
                    if (!@copy(Repository::$data['screen_url'], $image_name_file)) {
                        Repository::$data['screen_url'] = 'http://api.s-shot.ru/1024x1024/PNG/1024/KEYNJ7EWBQ57EJV5QE7/Z100/T0/D0/JS1/FS1/?' . Repository::$data['url'];
                        Repository::$data['error'][] = " Ошибка загрузки постера";
                    }
                }

                Repository::$data['time'] = Url::$load_time;
                Repository::$data['speed'] = Url::$speed;
                Repository::$data['h1'] = Url::$h1;

                $cat = new CatCheck();
                Repository::$data['cat_google'] = $cat->CheckInDMOZ(Repository::$data['url']);
                Repository::$data['cat_yahoo'] = $cat->CheckInYahoo(Repository::$data['url']);
                Repository::$data['cat_safebrowsing'] = $cat->CheckInSafeBrowsing(Repository::$data['url']);
                Repository::$data['cat_norton_safe_web'] = $cat->CheckInNortonSafeWeb(Repository::$data['url']);

                Repository::$data['alexa_visitors_by_country'] = self::GetAlexaVisitorsByCountry(Repository::$data['url']);

                //список ip
                $ipsitesinfo = new IPSitesList();
                $ipsitesinfo->GetIP_info(Repository::$data['url']);

                //расположение датацентра
                $geopluginq = new geoPlugin();
                $geopluginq->locate(Repository::$data['ip']);
                Repository::$data['server_countryName'] = $geopluginq->countryName;
                Repository::$data['server_countryCode'] = $geopluginq->countryCode;
                Repository::$data['server_city'] = $geopluginq->city;
                Repository::$data['server_region'] = $geopluginq->region;
                Repository::$data['ip_count'] = $ipsitesinfo->sitescount;
                Repository::$data['ip_count_url'] = $ipsitesinfo->lookmore;
                Repository::$data['ip_hosting'] = gethostbyaddr(gethostbyname(Repository::$data['url']));

                Repository::$data['keywords'] = Url::$keywords_url;
                Repository::$data['description'] = Url::$description_url;
                Repository::$data['dns_ns'] = Url::getNS();
                Repository::$data['google_images'] = Url::$GoogleImages;

                Repository::$data['pagerank'] = !empty(Url::$PageRank) ? Url::$PageRank : 0;

                Repository::$data['alexa_rank'] = self::GetAlexaRankXML(Repository::$data['url']);
                Repository::$data['alexa_delta_rank'] = self::GetAlexaDeltaRankXML(Repository::$data['url']);

                $index_links = self::getPageLinks($url);
                Repository::$data['in_indexing_links'] = $index_links[0];
                Repository::$data['in_noindex_links'] = $index_links[1];
                Repository::$data['out_indexing_links'] = $index_links[2];
                Repository::$data['out_noindex_links'] = $index_links[3];

                $content_words = self::getContentWords($url);
                Repository::$data['symbols'] = $content_words[1];
                Repository::$data['words'] = $content_words[2];
                Repository::$data['unique_words'] = $content_words[4];
                Repository::$data['stopwords'] = $content_words[5];
                Repository::$data['content_percent'] = $content_words[6];

                $w3c_html_validator = self::getW3CHTMLValidator($url);
                Repository::$data['html_errors'] = $w3c_html_validator[0];
                Repository::$data['html_warnings'] = $w3c_html_validator[1];
                Repository::$data['doctype'] = $w3c_html_validator[2];
                Repository::$data['charset'] = $w3c_html_validator[3];

                $w3c_css_validator = self::getW3CCSSValidator($url);
                Repository::$data['css_errors'] = $w3c_css_validator[0];
                Repository::$data['css_warnings'] = $w3c_css_validator[1];

                $category = self::getCategory($url);
                Repository::$data['category'] = $category[0];

                $soc_facebook = self::getSocFacebook($url);
                Repository::$data['facebook_shares'] = $soc_facebook[2];
                Repository::$data['facebook_likes'] = $soc_facebook[3];
                Repository::$data['facebook_comments'] = $soc_facebook[4];

                $soc_tweets = self::getSocTweets($url);
                Repository::$data['tweets'] = print_r($soc_tweets->count, 1);

                $soc_gplus = self::getSocGPlus($url);
                Repository::$data['gplus'] = $soc_gplus[0];

                $resources = self::getResources($url);
                Repository::$data['address'] = print_r($resources->id, 1);
                Repository::$data['title'] = print_r($resources->title, 1);
                Repository::$data['score'] = print_r($resources->score, 1);
                Repository::$data['size'] = print_r($resources->pageStats->htmlResponseBytes, 1);
                Repository::$data['css'] = print_r($resources->pageStats->cssResponseBytes, 1);
                Repository::$data['images'] = print_r($resources->pageStats->imageResponseBytes, 1);
                Repository::$data['javascript'] = print_r($resources->pageStats->javascriptResponseBytes, 1);
                Repository::$data['other'] = print_r($resources->pageStats->otherResponseBytes, 1);

                $traffic = self::getTraffic($url);
                Repository::$data['visits'] = $traffic[0];

                $alexa_pages = self::getAlexaPages($url);
                Repository::$data['alexa_pages'] = $alexa_pages[0];

                $headers = self::getHeaders($url);
                Repository::$data['status'] = $headers[0];
                Repository::$data['server'] = $headers[1];

                $index_backlinks_without_proxy = self::getIndexBacklinksWithoutProxy($url);
                Repository::$data['google_pages'] = $index_backlinks_without_proxy[0];
                Repository::$data['google_main_pages'] = $index_backlinks_without_proxy[1];
                Repository::$data['yahoo_pages'] = $index_backlinks_without_proxy[2];
                Repository::$data['yahoo_images'] = $index_backlinks_without_proxy[3];
                Repository::$data['domain_authority'] = $index_backlinks_without_proxy[4];
                Repository::$data['backlinks_domains'] = $index_backlinks_without_proxy[6];
                Repository::$data['backlinks_gov_domain'] = $index_backlinks_without_proxy[7];
                Repository::$data['backlinks_edu_domain'] = $index_backlinks_without_proxy[8];
                Repository::$data['backlinks_ips'] = $index_backlinks_without_proxy[9];
                Repository::$data['backlinks_subnets'] = $index_backlinks_without_proxy[10];
                Repository::$data['backlinks_all'] = $index_backlinks_without_proxy[11];
                Repository::$data['backlinks_text'] = $index_backlinks_without_proxy[12];
                Repository::$data['backlinks_nofollow'] = $index_backlinks_without_proxy[13];
                Repository::$data['backlinks_redirect'] = $index_backlinks_without_proxy[14];
                Repository::$data['backlinks_images'] = $index_backlinks_without_proxy[15];
                Repository::$data['backlinks_gov_links'] = $index_backlinks_without_proxy[18];
                Repository::$data['backlinks_edu_links'] = $index_backlinks_without_proxy[19];
                Repository::$data['registrar'] = $index_backlinks[20];
                Repository::$data['whois'] = $index_backlinks[21];
                Repository::$data['domain_created'] = $index_backlinks[22];
                Repository::$data['domain_expires'] = $index_backlinks[23];

                Repository::$data['favicon'] = self::DownloadFavicon($url);

                if (!isset(Repository::$data['error'])) {
                    \Userset\Analiz\Models\Analiz::UpdateAnaliz(Repository::$data);
                    return new RedirectResponse(Repository::$config['base_url'] . '/website/' . Repository::$data['url']);
                }
            }
        } else {
            Repository::$data['error'][] = "Sorry, but URL: " . $url . " cant't be analyzed.";
            Repository::$data['status_analiz'] = FALSE;
        }


        $html = Template::RenderTemplate('pages/analiz.twig', (array)Repository::$data);
        return new Response($html);
    }

    public static function GetAlexaVisitorsByCountry($url)
    {
        $return = array();
        $url = 'http://www.alexa.com/siteinfo/' . $url;
        //Initialize the Curl
        $ch = curl_init();
        //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_COOKIE, 'rpt=%21; optimizelyEndUserId=oeu1405797533627r0.14135225908830762; session_www_alexa_com=0cf78ecd-9e44-49da-89cd-ee8e056f0332; rpt=%21; jwtScribr=eJyrVsrNUbIyNNBRKikG0iYGphYGhkCqFgBUnQY6.VFyNgDiiuN_JjGBe5qvhpM8OQjmoP-GxOZE7BL8-Tms; session_www_alexa_com_daily=1405797540; lv=1405797540; migrated=true; optimizelySegments=%7B%22176053510%22%3A%22gc%22%2C%22176317345%22%3A%22direct%22%2C%22176317346%22%3A%22false%22%7D; optimizelyBuckets=%7B%7D; _ga=GA1.2.1339216243.1405797537; __asc=c1f43b2c14750101542d5c04e3f; __auc=c1f43b2c14750101542d5c04e3f; optimizelyPendingLogEvents=%5B%22n%3Dhttp%253A%252F%252Fwww.alexa.com%252Fsiteinfo%252Fgoogle.com%26u%3Doeu1405797533627r0.14135225908830762%26wxhr%3Dtrue%26t%3D1405797540698%26f%3D1471040775%22%5D');
        //Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);
        //Execute the fetch
        $data = curl_exec($ch);
        //Close the connection
        \curl_close($ch);
        preg_match_all('/<td.*?\/> &nbsp;([^<]+).*?(\d{1,2}\.?\d{1,2}%)/i', $data, $matches);
        if (isset($matches[1], $matches[2]) && !empty($matches[1]) && !empty($matches[2])) {
            foreach ($matches[1] as $k => $country) {
                if (isset($matches[2][$k]) && !empty($matches[2][$k]))
                    $return[] = array(
                        'country' => $country,
                        'percent' => $matches[2][$k],
                    );
            }
        }
        return $return;
    }

    public static function GetAlexaRankXML($url)
    {
        $url = "http://data.alexa.com/data?cli=10&dat=snbamz&url=" . $url;
        //Initialize the Curl
        $ch = curl_init();
        //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        //Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);
        //Execute the fetch
        $data = curl_exec($ch);
        //Close the connection
        \curl_close($ch);
        $xml = new \SimpleXMLElement($data);
        //Get popularity node
        $popularity = $xml->xpath("//POPULARITY");
        //Get the Rank attribute
        @$rank = (string)$popularity[0]['TEXT'];
        return $rank;
    }

    public static function GetAlexaDeltaRankXML($url)
    {
        $url = "http://data.alexa.com/data?cli=10&dat=snbamz&url=" . $url;
        //Initialize the Curl
        $ch = curl_init();
        //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        //Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);
        //Execute the fetch
        $data = curl_exec($ch);
        //Close the connection
        \curl_close($ch);
        $xml = new \SimpleXMLElement($data);
        //Get popularity node
        $rank = $xml->xpath("//RANK");
        //Get the Rank attribute
        @$rank = (string)$rank[0]['DELTA'];
        return $rank;
    }

    protected static function get_domain_name($url)
    {
        $pieces = parse_url('http://' . $url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            return substr($regs['domain'], 0, strpos($regs['domain'], '.'));
        }
        return $url;
    }

    protected static function get_domain_tld($url)
    {
        $pieces = parse_url('http://' . $url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            return substr($regs['domain'], strpos($regs['domain'], '.'));
        }
        return '';
    }

    public static function ResultActionEN($url)
    {
//        ini_set('display_errors', 1);
//        error_reporting(E_ALL);
        $data = \Userset\Analiz\Models\Analiz::Get($url);
        if ($data == FALSE) {
            return new RedirectResponse(Repository::$config['base_url'] . '/');
        }
        $data->similar = array();
//        if(isset($_GET['testim']))
//            pre(self::get_domain_name($data->url), 1);
//        $data->siteName = substr($data->url, 0, strpos($data->url, '.'));
        $data->siteName = self::get_domain_name($data->url);
        $data->siteTld = self::get_domain_tld($data->url);
        $data->status_analiz = true;
        if ($similar = \Userset\Analiz\Models\Analiz::GetSimilar($data))
            $data->similar = $similar;
        $html = Template::RenderTemplate('pages/analiz.twig', (array)$data);
        return new Response($html);
    }

    /**
     * Определяет размер файла
     * @param type $page Путь к файлу
     * @return type Размер страницы сайта
     */

    static function GetIPSite($url)
    {

        $ip = @gethostbyname($url);
        return $ip;
    }


    public static function GetSizePage($page)
    {
        $res = filesize($page);
        return $res;
    }

    private static function getPageLinks($url)
    {
        $content = @Url::file_get_contents_new('http://getinfo.botalizer.com/tools/tools/checkurllinks/' . $url);
        preg_match_all('/>(.*)</', $content, $index_links);
        return $index_links[1];
    }

    private static function getContentWords($url)
    {
        $content = @Url::file_get_contents_new('http://getinfo.botalizer.com/tools/tools/contentcheck/' . $url);
        preg_match_all('/>(.*)</', $content, $content_words);
        return $content_words[1];
    }

    private static function getW3CHTMLValidator($url)
    {
        $content = @Url::file_get_contents_new('http://getinfo.botalizer.com/parsers/htmlvalidator.php?s=' . $url);
        preg_match_all('/>(.*)</', $content, $w3c_html_validator);
        return $w3c_html_validator[1];
    }

    private static function getW3CCSSValidator($url)
    {
        $content = @Url::file_get_contents_new('http://getinfo.botalizer.com/parsers/cssvalidator.php?s=' . $url);
        preg_match_all('/>(.*)</', $content, $w3c_css_validator);
        return $w3c_css_validator[1];
    }

    private static function getHeaders($url)
    {
        $content = @Url::file_get_contents_new('http://getinfo.botalizer.com/parsers/headers.php?s=' . $url);
        preg_match_all('/>(.*)</', $content, $headers);
        return $headers[1];
    }

    private static function getCategory($url)
    {
        $content = @Url::file_get_contents_new('http://getinfo.botalizer.com/parsers/category.php?s=' . $url);
        preg_match_all('/>(.*)</', $content, $category);
        return $category[1];
    }

    private static function getSocFacebook($url)
    {
        $content = @Url::file_get_contents_new('http://api.facebook.com/method/links.getStats?format=xml&urls=' . $url);
        preg_match_all('/>(.*)</', $content, $soc_facebook);
        return $soc_facebook[1];
    }

    private static function getSocTweets($url)
    {
        $soc_tweets = Json_decode(@Url::file_get_contents_new('http://urls.api.twitter.com/1/urls/count.json?url=www.' . $url));
        return $soc_tweets;
    }

    private static function getSocGPlus($url)
    {
        $content = @Url::file_get_contents_new('http://getinfo.botalizer.com/parsers/google_plus.php?s=' . $url);
        preg_match_all('/>(.*)</', $content, $soc_gplus);
        return $soc_gplus[1];
    }

    private static function getTraffic($url)
    {
        $content = @Url::file_get_contents_new('http://getinfo.botalizer.com/parsers/traffic.php?s=' . $url);
        preg_match_all('/>(.*)</', $content, $traffic);
        return $traffic[1];
    }

    private static function getAlexaPages($url)
    {
        $content = @Url::file_get_contents_new('http://getinfo.botalizer.com/parsers/alexa.php?s=' . $url);
        preg_match_all('/>(.*)</', $content, $alexa_pages);
        return $alexa_pages[1];
    }

    private static function getResources($url)
    {
        $resources = Json_decode(@Url::file_get_contents_new('https://www.googleapis.com/pagespeedonline/v1/runPagespeed?url=http://' . $url . '&key=AIzaSyDBXcaMK3CHYbC8mAylWJPQeXBNFEbVwgw'));
        return $resources;
    }

    private static function getIndexBacklinks($url)
    {
        $content = @Url::file_get_contents_new('http://proxy.botalizer.com/check/' . $url);
        preg_match_all('/>(.*)</', $content, $index_backlinks);
        return $index_backlinks[1];
    }

    private static function getIndexBacklinksWithoutProxy($url)
    {
        $content = @Url::file_get_contents_new('http://withoutproxy.botalizer.com/check/' . $url);
        preg_match_all('/>(.*)</', $content, $index_backlinks_without_proxy);
        return $index_backlinks_without_proxy[1];
    }


    private static function DownloadFavicon($url)
    {
        $httpurl = $url;
        if (strpos($url, 'http') === false)
            $httpurl = 'http://' . $url;
        $ch = curl_init();
        //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
//        curl_setopt($ch, CURLOPT_HEADER, 1);
        //Set the URL
        curl_setopt($ch, CURLOPT_URL, 'http://g.etfv.co/' . $httpurl);
        //Execute the fetch
        $image = curl_exec($ch);
        $info = curl_getinfo($ch);
        //Close the connection
        \curl_close($ch);
//        pre($info, 1);
        $ext = '.ico';
        if (isset($info['content_type']) && (strlen($info['content_type']) > 1)) {
            switch ($info['content_type']) {
                case "image/png":
                    $ext = '.png';
                    break;
                case "image/gif":
                    $ext = '.gif';
                    break;
                case "image/x-icon":
                default:
                    $ext = '.ico';
            }
        }
        if (1 < file_put_contents(dirname(dirname(dirname(dirname(__DIR__)))) . '/datafavicon/img/' . $url . $ext, $image)) {
            return '/datafavicon/img/' . $url . $ext;
        }
        return false;
    }

    static public function SitemapAction()
    {
        $itemsPerPage = 50 * 1000;
        if (isset($_GET['page']) && ($page = $_GET['page'])) {
            $html = Template::RenderTemplate('sitemap.twig', array('sites' => ShortUrlModel::GetSiteMapData($page, $itemsPerPage)));
        } else {
            $html = Template::RenderTemplate('sitemapCategory.twig', array('pages' => ShortUrlModel::GetSiteMapDataCount($itemsPerPage)));
        }
        $response = new Response($html);
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

}
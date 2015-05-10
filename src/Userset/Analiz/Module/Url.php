<?php

namespace Userset\Analiz\Module;

use Core\Repository; 
use Core\Curl;

class Url
{

    /**
     * Исходный код страницы
     * @var type Сущность анализируемой страницы
     */
    static $sourse_html = "";

    /**
     * После инициализации содержит хост сайта
     * @var type Хост сайта
     */
    static $url = "";

    /**
     * После инициализации присваивается размер страницы
     * @var type Размер страницы
     */
    static $page_size = "";
    static $load_time = "";
    static $speed = "";

    /**
     * Ответ сервера
     * @var type Статус ответа сервера <int>
     */
    static $http_code = "";

    /**
     * URL перенаправления
     * @var type Адрес перенаправления
     */
    static $redirect_url = "";

    /**
     * IP адрес
     */
    static $ip_adres = "";

    /**
     * Кодировка , если 1 значит Win-1251
     * @var type
     */
    static $charset = 0;

    /**
     * Тег Title
     * @var type Заголовок сайта
     */
    static $url_title = "";

    /**
     * Заголовок Документа
     * @var type
     */
    static $h1 = "";

    /**
     * Хранение показателя PR от Google
     * @var type
     */
    static $PageRank = "";
    static $GoogleImages = "";
    static $GoogleLinks = "";

    static function GETSOURSE($url)
    {
        self::$url = $url;
        self::Meta($url);

        $curl = new curl();
        self::$sourse_html = self::GetSrcSite(@$curl->get($url));
        self::$url_title = self::GetTitle(self::$sourse_html);
    }

    /**
     * Инициализация и разбор URL адреса
     * Комплект <seo анализ сайта>
     * @param type $url
     * @return boolean
     */
    static function Init($url, $n = 0)
    {
        if ($n === 0) {
            // Инициальзация URL -> получение хоста сайта;
            self::$url = self::AddRemHttpURL($url);
            // Инициализация Класса обёртки cURL;
            // Если URL не получен
            if (!self::$url) {
                // Регистртрование ошибки
//                Repository::$data['error'][] = 'Адрес сайта:' . $url . ' не!';
//                pre('Адрес сайта:' . $url . ' не!', 1);
                return false;
            }
        } else {
            self::$url = $url;
        }
        $curl = new curl(array('proxy' => true, 'cookie' => true, 'cache' => true));
        // Получение исходного кода по URL;
        self::$sourse_html = self::GetSrcSite(@$curl->get(self::$url));

        // Получение ответа от сервера;
        self::$http_code = $curl->info['http_code'];
        // Получение размера загрузки;
        self::$page_size = $curl->info['size_download'];
        // Получение время отклика;
        self::$load_time = $curl->info['total_time'];
        // Получение скорости загрузки;
        self::$speed = $curl->info['speed_download'];
        // Если есть редирект / получить URL перенаправления;
        self::$redirect_url = $curl->info['redirect_url'];
        if (!empty(self::$redirect_url)) {
            self::$sourse_html = self::GetSrcSite(@$curl->get(self::$redirect_url));
        }
        // Получить IP адрес
        self::$ip_adres = gethostbyname(self::getUrl());
        // Получить заголовак
        self::$url_title = self::GetTitle(self::$sourse_html);
        // Получить хаголовок h1
        self::$h1 = self::GetH1(self::$sourse_html);
        // To do

        if (!preg_match('/^(https?:\/\/)(.*)/i', self::$url)) {
            if (!self::Meta('http://' . self::getUrl())) {
                //Ошибка! Описание!
            }
        } else {
            if (!self::Meta(self::$url)) {
                //Ошибка! Описание!
            }
        }
        // Сохранение показателя PageRankGoogle
        self::$PageRank = self::GetPageRank(self::getUrl());
        // Количество images на сайт из Google
        self::$GoogleImages = self::getGoogleImages(self::$url);
        // Количество ссылок на сайт из Google
        self::$GoogleLinks = self::getGoogleLinks(self::getUrl());


//        echo '<pre>';
//        print_r($curl->info);
//        echo '</pre>';

        return true;
    }

    static $description_url = "";
    static $keywords_url = "";

    /**
     * Получение meta tegs
     * Комплект <seo анализ сайта>
     * @return boolean
     */
    static function Meta($url)
    {
        $meta_teg_url = @get_meta_tags($url);
        if (!$meta_teg_url) {
            return False;
        }
        if (self::$charset === 1) {
            if (isset($meta_teg_url['keywords'])) {
                self::$keywords_url = iconv("windows-1251", "UTF-8", $meta_teg_url['keywords']);
            }
            if (isset($meta_teg_url['description'])) {
                self::$description_url = iconv("windows-1251", "UTF-8", $meta_teg_url['description']);
            }
        } else {
            if (isset($meta_teg_url['keywords'])) {
                self::$keywords_url = $meta_teg_url['keywords'];
            }
            if (isset($meta_teg_url['description'])) {
                self::$description_url = $meta_teg_url['description'];
            }
        }
        return true;
    }

    /**
     * Получение заголовка первого уровня;
     * Комплект <seo анализ сайта>
     * @param type $source
     * @return type
     */
    static function GetH1($source)
    {
        $h1 = '';
        if (preg_match("/<h1>(.*)<\/h1>/isU", $source, $ar)) {
            $h1 = $ar[1];
        } else {
            $h1 = self::ExtractString($source, '<h1>', '</h1>');
        }
        //print_r($source);exit();

        if (empty($h1)) {
            $html = new curl();

            if (!preg_match('/^(https?:\/\/)(.*)/i', self::$url)) {
                $source = $html->get('http://' . self::$url);
            } else {
                $source = $html->get(self::$url);
            }
            //$source =  htmlspecialchars(trim($source),ENT_QUOTES);
            if (self::$charset == 1) {
                $source = @iconv("windows-1251", "UTF-8", $source);
            }
            //$source = preg_replace("/<h1(.+?)>/si", "<h1>", $source);
            //$source = preg_replace('/<h1>(.*?)<\/h1>/', '<teg>$1</teg>', $source);
            //preg_match("/<teg>(.*)<\/teg>/isU", $source, $ar);
            //unset($source);
            //echo htmlspecialchars(trim($source),ENT_QUOTES);
            preg_match("/<h1>(.*)<\/h1>/isU", $source, $ar);
            if (!empty($ar[1])) {
                $h1 = $ar[1];


            }
        }

        $h1 = filter_var($h1, FILTER_SANITIZE_STRING);
        return $h1;
    }

    /**
     * Получение заголовка страницы;
     * Комплект <seo анализ сайта>
     * @param type $sourse
     * @return int
     */
    static function GetTitle($sourse)
    {
        preg_match('|<title>(.*)</title>|sUSi', $sourse, $str);
        if (count($str) < 1) {
            return self::getUrl();
        }
        return $str[1];
    }

    /**
     * Метод принимает $url и возвращает Хост сайта
     * Комплект <seo анализ сайта>
     * @param type $url Адрес сайта
     * @return type Возвращает Хост сайта без протокола
     * @example http://www.u-set.ru => www.u-set.ru
     */
    static function AddRemHttpURL($url)
    {
        $url_info = \parse_url($url);
        if (empty($url_info['host'])) {
            $url = trim($url_info['path'], '/');
        } else {
            $url = $url_info['host'];
        }
        $url = explode("/", $url);
        if (filter_var('http://' . $url[0], FILTER_VALIDATE_URL)) {
//            if ($url = self::file_get_contents_new('http://' . $url[0])) {
            if ($url = self::get_true_url('http://' . $url[0])) {
                return $url;
            }
        }
        return FALSE;
    }

    static function IsAdult($words, $count = 1)
    {
        $matches = 0;
        foreach ($words as $word) {
            if ((strlen($word) < 2) || preg_match('~' . $word . '~i', self::$sourse_html, $what) === 0)
                continue;
            $matches++;
            if ($matches == $count)
                return true;
        }
        return false;
    }

    /**
     * Копирование исходного кода сайта в Файл
     * Комплект <seo анализ сайта>
     * @param type $url Адрес сайта (host)
     * @return type TRUE если ошибок нет, FALSE в ином случаи
     */
    public static function GetSrcSite($sourse)
    {
        $rez = preg_replace("'<!--(.+?)-->'si", "", $sourse);
        //$rez = preg_replace("'<script(.+?)</script>'si", "", $rez);
        //$rez = preg_replace("'<style(.+?)</style>'si", "", $rez);
        //$rez = preg_replace("'<img(.+?)>'si", "<img src='/web/images/links.gif'>", $rez);
        //$rez = preg_replace("'<h1(.+?)>'si", "<h1>", $rez);
        //$rez = preg_replace('/"/', '', $rez);
        //$rez = preg_replace("/'/", "", $rez);
        //$rez = preg_replace("'=//'si", "=http://", $rez);
        //$rez = preg_replace("'<a(.+?)href'si", "<a href", $rez);
        //$rez = preg_replace("'<a href( +?)='si", "<a href=", $rez);
        //$rez = preg_replace("'<a href=( +?)'si", "<a href=", $rez);
        //$rez = preg_replace("'title(.+?)>'si", ">", self::$src_url);
        //$rez = preg_replace("'target(.+?)>'si", ">", $rez);
        /*         * ******************************************************************* */
        if (preg_match("/charset=windows-1251/i", $rez)) {
            $rez = @\iconv("windows-1251", "UTF-8", $rez);
            self::$charset = 1;
        }
        //*********************************************************//
        if (!empty($rez)) {
            return $rez;
        } else {
            return FALSE;
        }
    }

    static function ExtractString($str, $start, $end)
    {
        $str_low = strtolower($str);
        $pos_start = @strpos($str_low, $start);
        $pos_end = @strpos($str_low, $end, ($pos_start + strlen($start)));
        if (($pos_start !== false) && ($pos_end !== false)) {
            $pos1 = $pos_start + strlen($start);
            $pos2 = $pos_end - $pos1;
            return substr($str, $pos1, $pos2);
        }
        return false;
    }

    /**
     * number format - 1000000,1 000 000,1.000.000  as 1,000,000
     * @param type $number
     * @param type $divchar
     * @param type $divat
     * @return string
     */
    static function format_numberQ($number = '', $divchar = ',', $divat = 3)
    {
        $decimals = '';
        $formatted = '';
        $number = str_replace(",", "", $number);
        $number = str_replace(".", "", $number);
        $number = str_replace(" ", "", $number);
        if (strstr($number, '.')) {
            $pieces = explode('.', $number);
            $number = $pieces[0];
            $decimals = '.' . $pieces[1];
        } else {
            $number = (string)$number;
        }
        if (strlen($number) <= $divat)
            return $number;
        $j = 0;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            if ($j == $divat) {
                $formatted = $divchar . $formatted;
                $j = 0;
            }
            $formatted = $number[$i] . $formatted;
            $j++;
        }
        if ($formatted . $decimals == "") {
            return "0";
        }
        return $formatted . $decimals;
    }

    /**
     * Получение значения Page Rang;
     * Комплект <seo анализ сайта>
     * @param type $q
     * @param type $host
     * @param type $context
     * @return type
     */
    static function GetPageRank($q, $host = 'toolbarqueries.google.com', $context = NULL)
    {
        if (!preg_match('/^(https?:\/\/)(.*)/i', $q)) {
            $url = 'http://' . $q;
        }
        $seed = "Mining PageRank is AGAINST GOOGLE'S TERMS OF SERVICE. Yes, I'm talking to you, scammer.";
        $result = 0x01020345;
        $len = strlen($q);
        for ($i = 0; $i < $len; $i++) {
            $result ^= ord($seed{$i % strlen($seed)}) ^ ord($q{$i});
            $result = (($result >> 23) & 0x1ff) | $result << 9;
        }
        if (PHP_INT_MAX != 2147483647) {
            $result = -(~($result & 0xFFFFFFFF) + 1);
        }
        $ch = sprintf('8%x', $result);
        $url = 'http://%s/tbr?client=navclient-auto&ch=%s&features=Rank&q=info:%s';
        $url = sprintf($url, $host, $ch, $q);
        @$pr = self::file_get_contents_new($url);
        return $pr ? substr(strrchr($pr, ':'), 1) : false;
    }

    /**
     * Получение количества индексируемых ссылок в Google;
     * Комплект <seo анализ сайта>
     * @param type $host
     * @return string
     */
	    static function getGoogleImages($host)
    {
        $content = self::file_get_contents_new('http://ajax.googleapis.com/ajax/services/' . 'search/images?v=1.0&filter=0&q=site:'
                . \urlencode($host));
        $data = json_decode($content);
        if (isset($data->responseData->cursor->estimatedResultCount))
        {
return number_format(intval($data->responseData->cursor->estimatedResultCount), 0, '.', ''); 
        }
        return "";
    }

    /**
     * Количество ссылок на страницы из Google
     * Комплект <seo анализ сайта>
     * @param type $host
     * @return int
     */
    static function getGoogleLinks($host)
    {
        $content = self::file_get_contents_new('http://ajax.googleapis.com/ajax/services/' . 'search/web?v=1.0&filter=0&q='
            . urlencode($host));
        $data = json_decode($content);
        if (isset($data->responseData->cursor->estimatedResultCount)) {
            return \intval($data->responseData->cursor->estimatedResultCount);
        }
        return 0;
    }

    static function file_get_contents_new($url, $count = 0)
    {
        if ($count > 5)
            return false;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux i686 (x86_64)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);

        $data = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);
        if (isset($status['http_code']) && ($status['http_code'] == '301' || $status['http_code'] == '302') && isset($status['redirect_url']) && $status['redirect_url']) {
            return self::file_get_contents_new($status['redirect_url'], $count + 1);
        }
        if (isset($status['http_code']) && ($status['http_code'] == '200')) {
            return $data;
        }
        return false;
    }

    static function getYandexIndex($host, $par)
    {
        error_reporting(1);
        $query = $host;
        if ($par == 0)
            $search_tail = htmlspecialchars('host:' . $query . ' | host:www.' . $query);
        if ($par == 1)
            $search_tail = htmlspecialchars('"' . $query . '"');
        $found = 0;
        if ($query) {
            // XML Р·Р°РїСЂРѕСЃ
            $doc = <<<DOC
<?xml version='1.0' encoding='utf-8'?>
<request>
    <query>$search_tail</query>
</request>
DOC;
            $context = stream_context_create(array(
                'http' => array(
                    'method' => "POST",
                    'header' => "Content-type: application/xml\r\n" .
                        "Content-length: " . strlen($doc),
                    'content' => $doc
                )
            ));
            $response = file_get_contents(
                Repository::$config['ya_xml'], true, $context);
            if ($response) {
                $xmldoc = new \SimpleXMLElement($response);
                $error = $xmldoc->response->error;
                $found_all = $xmldoc->response->found;
                $found = $xmldoc->xpath("response/results/grouping/group/doc");
                if ($error) {
                    $found_all = 0;
                    $rezultat = $found_all;
                } else {
                    $rezultat = $found_all;
                }
            } else {
                $found_all = 0;
                $rezultat = $found_all;
            }
        }
        return $rezultat;
    }

    public static function getUrl()
    {
        $parse_url = parse_url('http://' . preg_replace('/https?:\/\//', '', self::$url));

        if (!isset($parse_url['host']))
            $url = $parse_url['path'];
        else
            $url = $parse_url['host'];
        return str_replace('www.', '', $url);
    }

    public static function getNS()
    {
        $return = array();
        $dns = dns_get_record(self::getUrl(), DNS_NS);

        foreach ($dns as $d) {
            $ip = gethostbyname($d['target']);
            $geopluginq = new geoPlugin();
            $geopluginq->locate($ip);
            $return[] = array(
                'server' => $d['target'],
                'ip' => $ip,
                'country' => $geopluginq->countryName,
            );
        }
        return $return;
    }

    static function get_true_url($url, $count = 0)
    {
        if ($count > 5)
            return false;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux i686 (x86_64)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);
        if (isset($status['http_code']) && ($status['http_code'] == '301' || $status['http_code'] == '302') && isset($status['redirect_url']) && $status['redirect_url']) {
            return self::get_true_url($status['redirect_url'], $count + 1);
        }
        if (isset($status['http_code']) && ($status['http_code'] == '200')) {
            return $url;
        }
        return false;
    }

}
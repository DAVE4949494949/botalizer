<?php

namespace Userset\ShortUrl\Models;

use Userset\ShortUrl\ShortUrlClass;
use RedBean_Facade as R;
use Core\Repository;

class ShortUrlModel
{

    public static function GetFullURL($short_code)
    {
        self::Connect();
        $id = self::link2dec($short_code);
        $data = R::findOne('short', ' id = ?', array($id));
        $short = R::dispense('short');
        $short->id = $id;
        if (!empty($data->counter)) {
            $short->counter = $data->counter + 1;
        } else {
            $short->counter = 1;
        }
        R::store($short);
        if (empty($data)) {
            return false;
        }
        return $data;
    }

    static function LastLinkShort()
    {
        self::Connect();
        //$analiz = R::findAll('analiz', 'ORDER BY create_date LIMIT 10');
        $analiz = R::getAll('select * from short LIMIT 100');
        return $analiz;
    }

    // Функция получения индекса из кода ссылки 
    static function link2dec($link)
    {
        $digits = Array('0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6,
            '7' => 7, '8' => 8, '9' => 9, 'a' => 10, 'b' => 11, 'c' => 12, 'd' => 13,
            'e' => 14, 'f' => 15, 'g' => 16, 'h' => 17, 'i' => 18, 'j' => 19, 'k' => 20,
            'l' => 21, 'm' => 22, 'n' => 23, 'o' => 24, 'p' => 25, 'q' => 26, 'r' => 27,
            's' => 28, 't' => 29, 'u' => 30, 'v' => 31, 'w' => 32, 'x' => 33, 'y' => 34,
            'z' => 35, 'A' => 36, 'B' => 37, 'C' => 38, 'D' => 39, 'E' => 40, 'F' => 41,
            'G' => 42, 'H' => 43, 'I' => 44, 'J' => 45, 'K' => 46, 'L' => 47, 'M' => 48,
            'N' => 49, 'O' => 50, 'P' => 51, 'Q' => 52, 'R' => 53, 'S' => 54, 'T' => 55,
            'U' => 56, 'V' => 57, 'W' => 58, 'X' => 59, 'Y' => 60, 'Z' => 61);
        $id = 0;
        for ($i = 0; $i < strlen($link); $i++) {
            @$id += $digits[$link[(strlen($link) - $i - 1)]] * pow(62, $i);
        }
        return $id;
    }

    public static function SaveFullUrl($full_url)
    {
        self::Connect();
        $short_find = R::findOne('short', ' full_url = ?', array($full_url));
        if (empty($short_find)) {
            if (empty($full_url)) {
                exit('Возможно данную ссылку сократить нет возможности , проверьте работоспособность URL адреса');
            }
            $source = \Userset\Analiz\Module\Url::GETSOURSE($full_url);
            Repository::$data['title'] = \Userset\Analiz\Module\Url::$url_title;
            Repository::$data['description'] = \Userset\Analiz\Module\Url::$description_url;
            Repository::$data['keywords'] = \Userset\Analiz\Module\Url::$keywords_url;
            $date = new \DateTime(date('d.m.Y H:i:s'));
            $short = R::dispense('short');
            $short->host = parse_url($full_url, PHP_URL_HOST);
            $short->full_url = $full_url;
            $short->user_id = Repository::$data['user_id'];
            $short->date_created = strtotime($date->format('d.m.Y H:i:s'));
            $short->counter = 0;
            //
            $short->title = \Userset\Analiz\Module\Url::$url_title;
            Repository::$data['title'] = \Userset\Analiz\Module\Url::$url_title;
            //$login = Repository::$config['tw_login']; 
            //$password = Repository::$config['tw_password'];
            //  \Userset\Analiz\Module\PostInTwitter::post(\Userset\Analiz\Module\Url::$url_title, $login, $password);
            $short->description = \Userset\Analiz\Module\Url::$description_url;
            $short->keywords = \Userset\Analiz\Module\Url::$keywords_url;
            //
            $id = R::store($short);
            $short_url = self::dec2link($id);
            $short = R::dispense('short');
            $short->id = $id;
            $short->short_url = $short_url;
            R::store($short);
            return $short_url;
        } else {

            return $short_find->short_url;
        }
    }

    static function Connect()
    {
        $config = Repository::GetGonfig();
        R::setup('mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'], $config['user'], $config['password']);
    }

    // Функция получения кода ссылки из индекса 
    static function dec2link($id)
    {
        $digits = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $link = '';
        do {
            $dig = $id % 62;
            $link = $digits[$dig] . $link;
            $id = floor($id / 62);
        } while ($id != 0);
        return $link;
    }

    /**
     * @param int $page
     * @param $itemsPerPage
     * @return array
     */
    public static function GetSiteMapData($page = 1, $itemsPerPage = 50000)
    {
        self::Connect();
        $start = ($page - 1) * $itemsPerPage;
        return R::getAll('SELECT `site`, `update_date` FROM `analiz` LIMIT ' . $start . ',' . $itemsPerPage);
    }

    public static function GetSiteMapDataCount($itemsPerPage = 50000)
    {
        self::Connect();
        $count = R::getRow('SELECT COUNT(*) as `count` FROM `analiz` ');
        $count = $count['count'];
        return ceil($count / $itemsPerPage);
    }

}

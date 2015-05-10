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

class SeoKeyPosition
{

    static public function StartAction()
    {
        // Получение массива POST данных формы
        $request = new Request($_POST);
        // Если кнопка нажата выполнять обработку
        if ($request->query->get('act') === 'do')
        {
            // Получение URL из формы
            $url = $request->query->get('siteurl');
            $key = $request->query->get('key');
        }
        $html = Template::RenderTemplate('pages/analiz.twig', (array) Repository::$data);
        return new Response($html);
    }

    static function get_ind_pages($link1)
    {
        $link1 = parse_url($link1);
        $link1['port'] = (empty($link1['port'])) ? '80' : $link1['port'];
        $fp = fsockopen($link1['host'], $link1['port'], $sock_errno, $sock_errstr, 30);
        if (!$fp)
        {
            print "$sock_errstr ($sock_errno)<br>\n";
        } else
        {
            $out = "GET {$link1['path']}?{$link1['query']} HTTP/1.1\r\n";
            $out .= "Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, */*\r\n";
            $out .= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET CLR 2.0.40607; .NET CLR 1.0.3705; IEMB3; IEMB3)\r\n";
            $out .= "Host: {$link1['host']}\r\n";
            $out .= "Connection: Close\r\n\r\n";
            fwrite($fp, $out);
            $text = '';
            while (!feof($fp))
            {
                $text .= fgets($fp, 128);
            }
            fclose($fp);
        }
        //$text = iconv('UTF-8', 'cp1251', $text);
        return ($text);
    }

    function get_key_posn($part, $server, $phrase, $url)
    {
        $phrase0 = $phrase;
        //$phrase = iconv('cp1251', 'UTF-8', $phrase);
        $phrase = urlencode($phrase);
        $posn = 0;
        if ($part == 0)
        {
            $page = 0;
            $pmax = 3;
        }
        if ($part == 1)
        {
            $page = 3;
            $pmax = 6;
        }
        if ($part == 2)
        {
            $page = 6;
            $pmax = 9;
        }
        if ($part == 3)
        {
            $page = 9;
            $pmax = 12;
        }
        if ($part == 4)
        {
            $page = 12;
            $pmax = 15;
        }
        if ($part == 5)
        {
            $page = 15;
            $pmax = 18;
        }
        if ($part == 6)
        {
            $page = 18;
            $pmax = 21;
        }
        if ($part == 7)
        {
            $page = 21;
            $pmax = 24;
        }
        if ($part == 8)
        {
            $page = 24;
            $pmax = 27;
        }
        if ($part == 9)
        {
            $page = 27;
            $pmax = 30;
        }
        $tmp = 'images/src_copy.php';
        do
        {
            if ($server == 'google')
            {
                $param1 = '<ol';
                $param2 = '</ol>';
                $link = 'http://www.google.ru/search?num=10&hl=ru&sa=N&q=' . $phrase . '&prmd=ivns&start=' . ($page * 10);
            }
            if ($server == 'yahoo')
            {
                $param1 = '<ol';
                $param2 = '</ol>';
                $link = 'http://search.yahoo.com/search?p=' . $phrase . '&n=10&xargs=0&pstart=1&b=' . ($page * 10 + 1);
            }
            if ($server == 'bing')
            {
                $param1 = '"sb_results">';
                $param2 = '<a href="/search';
                $link = 'http://www.bing.com/search?q=' . $phrase . '&first=' . ($page * 10 + 1) . '&setmkt=ru-RU&setlang=en-US';
            }
            if ($server == 'yandex')
            {
                $param1 = '<ol';
                $param2 = '</ol>';
                $link = 'http://путь к файлу "ya-poisk.php"/ya-poisk.php?page=' . $page . '&query=' . $phrase;
            }
            if (($server == 'yandex') || ($server == 'yahoo'))
                $rez1 = file_get_contents($link);
            else
                $rez1 = get_ind_pages($link);
            if ($server == 'bing')
            {
                $rez1 = ereg_replace("(.*)" . $param1, "<ol>", $rez1);
                $rez1 = ereg_replace($param2 . "(.*)", "</ol>", $rez1);
                $rez1 = ereg_replace("<ul", "<span", $rez1);
                $rez1 = ereg_replace("</ul", "</span", $rez1);
                $rez1 = ereg_replace("<div", "<span", $rez1);
                $rez1 = ereg_replace("</div", "</span", $rez1);
                $rez1 = ereg_replace("<li", "<span", $rez1);
                $rez1 = ereg_replace("</li", "</span", $rez1);
                $rez1 = ereg_replace("</h3>", "</li>", $rez1);
                $rez1 = ereg_replace("<h3>", "<li>", $rez1);
                $rez1 = ereg_replace("<p>", "", $rez1);
                $rez1 = ereg_replace("</p>", "", $rez1);
            } else
            {
                $rez1 = ereg_replace("(.*)" . $param1, $param1, $rez1);
                $rez1 = ereg_replace($param2 . "(.*)", $param2, $rez1);
            }
            $page++;
        } while (!strpos($rez1, $url) && $page < $pmax);
        $page--;
        if ($server == 'yandex')
            $adr = "http://yandex.ru/yandsearch?p=" . $page . "&text=" . $phrase . "&numdoc=10&lr=28810";
        else
            $adr = $link;
        file_put_contents($tmp, $rez1);
        $rez = @file_get_contents($tmp);
        $i = -1;
        preg_match_all("'<li(.+?)>(.*?)</li>'si", $rez, $ind_pg);
        do
        {
            $posn++;
            $i++;
        } while (!strpos($ind_pg[0][$i], $url) && ($i < count($ind_pg[0])));
        if (strpos($ind_pg[0][$i], $url))
        {
            $rzt = "<br> Позиция сайта<font color=green> " . $url . "</font> в<font color=red> " . $server
                    . "</font> по<br> фразе <font color=green> " . $phrase0 . "</font> равна<font color=red> "
                    . ($posn + $page * 10) . "</font> \n<br>";
            if ($server == 'bing')
                $rzt = $rzt . " В <font color=red>bing</font> реальная позиция может быть 
    <font color=red>немного больше</font><br>";
            $rzt = $rzt . " Посмотреть результаты поиска в <a href=" . $adr . " target=_blank>" . $server . "</a>\n<br>";
        }
        else
        {
            $rzt = "<br> Позиция сайта<font color=green> " . $url . "</font> в<font color=red> " . $server
                    . "</font> по<br> фразе <font color=green> " . $phrase0 . "</font> больше<font color=red> "
                    . ($posn + $page * 10 - 1) . "</font> \n<br>";
        }
        return $rzt;
    }

}


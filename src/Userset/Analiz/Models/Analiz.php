<?php

namespace Userset\Analiz\Models;

use RedBean_Facade as R;
use Core\Repository;
use RedBean_OODBBean;
use Symfony\Component\Config\Definition\Exception\Exception;

class Analiz
{

    static function Connect()
    {
        $config = Repository::GetGonfig();
        R::setup('mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'], $config['user'], $config['password']);
    }

    static function UpdateAnaliz($data)
    {
        self::Connect();
        $analiz_ = R::findOne('analiz', ' site = ?', array($data['url']));
        if ($analiz_) {
            $data_ser = json_encode($data);
            $date = new \DateTime(date('d.m.Y H:i:s'));
            $analiz = R::dispense('analiz');
            $analiz->id = $analiz_->id;
            $analiz->site = $data['url'];
//            if(isset($_GET['testim'])){
//                echo 'data:';
//                pre($data, 1);
//            }
            if (isset($data['title']) && !empty($data['title'])) {
                $analiz->title = $data['title'];
            }
            if (isset($data['description']) && !empty($data['description'])) {
                $analiz->description = $data['description'];
            }
            $analiz->base_analiz = $data_ser;
            $analiz->create_date = strtotime($date->format('d.m.Y H:i:s'));
            $date->modify('+1 minutes');
            $analiz->update_date = strtotime($date->format('d.m.Y H:i:s'));
            $analiz->role = $data['roleuser'];
            if ($data['roleuser'] > 0) {
                $analiz->user_id = Repository::$data['user_id'];
            } else {
                $analiz->user_id = 0;
            }
            if ($id = R::store($analiz)) {
                self::saveAnalizCategory($id, $data['category']);
                Repository::$data['warning'][] = 'Анализ сайта сохранён в базу данных';
            }
        }
    }

    /**
     * Получает id категории. Если категории нет, то создает её
     * @param $category
     * @return int
     */
    static function getCategoryId($category)
    {
        self::Connect();
        $categoryBean = R::getAll('
            SELECT
                `id`
            FROM
                `category`
            WHERE
                `name` LIKE ?
            LIMIT 1
        ', array($category));

        if (count($categoryBean) == 0) {
            $categoryBean = R::dispense('category');
            $categoryBean->name = $category;
            return R::store($categoryBean);
        } else {
            return $categoryBean[0]['id'];
        }
    }

    static function LastLinkAnaliz()
    {
        self::Connect();
        //$analiz = R::findAll('analiz', 'ORDER BY create_date LIMIT 10');
        $analiz = R::getAll('select title,site,create_date from analiz ORDER BY RAND() LIMIT 20');
        return $analiz;
    }

    /**
     * @param string|array $category
     * @return array
     */
    static function LastLinkAnalizByCategory($category)
    {
        self::Connect();
        $analiz = R::getAll('SELECT
                `a`.`title`,
                `a`.`site`,
                `a`.`create_date`
            FROM
                `analiz` as `a`,
                `category` as `c`,
                `analiz_category` as `ac`
            WHERE
                `c`.`name` LIKE ? AND
                `ac`.`category_id` = `c`.`id` AND
                `ac`.`analiz_id` = `a`.`id`
            ORDER BY
                RAND()
            DESC LIMIT 20
        ', array($category));
        return $analiz;
    }

    static function SaveAnaliz($data)
    {
        $data_ser = json_encode($data);
        // $data_ser = addslashes($data_ser);

        self::Connect();
        $analiz = R::findOne('analiz', ' site = ?', array($data['url']));
        if (!$analiz) {
            $date = new \DateTime(date('d.m.Y H:i:s'));
            $analiz = R::dispense('analiz');
            $analiz->site = $data['url'];
            $analiz->base_analiz = $data_ser;
            $analiz->create_date = strtotime($date->format('d.m.Y H:i:s'));
            $date->modify('+1 day');
            $analiz->update_date = strtotime($date->format('d.m.Y H:i:s'));
            $analiz->role = $data['roleuser'];
            $analiz->category_id = self::getCategoryId($data['category']);

            if (isset($data['title']) && !empty($data['title'])) {
                $analiz->title = $data['title'];
            }
            if (isset($data['description']) && !empty($data['description'])) {
                $analiz->description = $data['description'];
            }
            if (isset($data['keywords']) && !empty($data['keywords'])) {
                $analiz->keywords = $data['keywords'];
            }
            if (isset($data['alexa_rank']) && !empty($data['alexa_rank'])) {
                $analiz->alexa_rank = $data['alexa_rank'];
            }

            if ($data['roleuser'] > 0) {
                $analiz->user_id = Repository::$data['user_id'];
            } else {
                $analiz->user_id = 0;
            }
            if ($id = R::store($analiz)) {
                self::saveAnalizCategory($id, $data['category']);
                Repository::$data['warning'][] = 'Анализ сайта сохранён в базу данных';
            }
        }
    }

    static function Update($param)
    {

    }

    static function Delite($param)
    {

    }

    static function Get($url)
    {
        self::Connect();
        $site = R::findOne('analiz', ' site = ?', array($url));
        if (empty($site)) {
            return FALSE;
        }
        $data = json_decode($site['base_analiz']);
        $data->id = $site['id'];
        $data->categories = self::getAnalizCategories($site['id']);
//        $data->update_date = self::ChekDateUp($site['update_date']);
        $data->update_date = $site['update_date'];
//        if(isset($_COOKIE['testas'])){
//            pre($site['update_date']);
//            pre($data, 1);
//        }
        return $data;
    }

    /**
     * @param object $data
     * @param int $limit
     * @return array|bool
     */
    static function GetSimilar($data, $limit = 10)
    {
       if (!isset($_GET['testim'])) {
           try {
               error_reporting(E_ALL);
               ini_set('display_errors', 0);
            $return = array();
            $sites = array();
            $pdo = new \PDO('mysql:host=127.0.0.1;port=9306', '', '');

            $params = array();
            foreach (explode(' ', $data->title) as $titleWord) {
                if (strlen($titleWord) > 2)
                    $params[] = trim($titleWord, '- ,');
            }

            foreach (explode(' ', $data->description) as $descriptionWord) {
                if (strlen($descriptionWord) > 2)
                    $params[] = trim($descriptionWord, '- ,');
            }

            foreach (explode(' ', $data->keywords) as $keywordWord) {
                if (strlen($keywordWord) > 2)
                    $params[] = trim($keywordWord, '- ,');
            }

            foreach ($data->categories as $categoryWord) {
                $params[] = $categoryWord['name'];
            }
			
            foreach (explode(' ', $data->site) as $siteWord) {
                    $params[] = $siteWord;
            }
			
            $params = array_unique($params);
			
            $query = $pdo->prepare('SELECT * FROM `analiz` WHERE MATCH(:match) AND `a_id` != ' . intval($data->id) . ' LIMIT :limit OPTION field_weights=(category_name=60, title=40, keywords=50, description=30)');
            $query->bindValue(':match', '"' . join('" | "', $params) . '"', \PDO::PARAM_STR);
            $query->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $res = $query->execute();

            if (!$res)
                return array();

            foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $item) {
                $sites[] = R::findOne('analiz', 'id = :id', array('id' => $item['id']));
            }
            foreach ($sites as $site) {
                if(!$site)
                    continue;
                $data = json_decode($site['base_analiz']);
                $data->update_date = self::ChekDateUp($site['update_date']);
                $return[$site['id']] = $data;
            }
            return $return;
        } catch (Exception $e) {
            return array();
        }
       } else {

        self::Connect();
        $return = array();
        $categories = R::getAll('SELECT `category_id` FROM `analiz_category` WHERE `analiz_id` = ? ', array($data->id));
        foreach ($categories as &$category) {
            $category = $category['category_id'];
        }
        if (!$categories)
            return $return;
        $sites = R::getAll('SELECT
                DISTINCT `a`.*
            FROM
                `analiz` as `a`,
                `analiz_category` as `ac`
            WHERE
                `ac`.`category_id` IN ("' . join('" , "', $categories) . '")
                AND `ac`.`analiz_id` <> :id
                AND `a`.`id` = `ac`.`analiz_id`
            LIMIT :limit
        ', array(':id' => $data->id,
                ':limit' => max(1, intval($limit)))
        );
        if (!$sites)
            return $return;

        foreach ($sites as $site) {
            $data = json_decode($site['base_analiz']);
            $data->update_date = self::ChekDateUp($site['update_date']);
            $return[$site['id']] = $data;
        }
        return $return;
       }
    }

    static function ChekDateUpdate($url)
    {
        self::Connect();
        $site = R::findOne('analiz', ' site = ?', array($url));
        $date = new \DateTime(date('d.m.Y H:i:s'));
        if ($site['update_date'] < strtotime($date->format('d.m.Y H:i:s'))) {
            return true;
        }
        return false;
    }

    static function ChekDateUp($update_date)
    {
        $date = new \DateTime(date('d.m.Y H:i:s'));
        if ($update_date < strtotime($date->format('d.m.Y H:i:s'))) {
            return FALSE;
        }
        return date('d.m.Y H:i:s', $update_date);
    }

    static public function CheckSite($url)
    {
        self::Connect();
        $site = R::find('analiz', ' site = ?', array($url));
        return (!empty($site));
    }

    static function Sitemap()
    {
        self::Connect();
        //$analiz = R::findAll('analiz', 'ORDER BY create_date LIMIT 10');
        $analiz = R::getAll('select site,create_date from analiz');

        //=====СОДЕРЖИМОЕ=====
        $Sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $Sitemap .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";

        //Вноситься цикл для автоматической переборки URL-адресов и дат создания страниц.
        foreach ($analiz as $analiz) {
            $Sitemap .= "<url>";
            $Sitemap .= "<loc>http://botalizer.com/website/{$analiz['site']}</loc>";
            $Sitemap .= "<lastmod>" . date('Y-m-d', $analiz['create_date']) . "</lastmod>";
            $Sitemap .= "</url>";
        }
        $short = R::getAll('select short_url,date_created from short');

        $Sitemap .= "</urlset>";

//=====ФАЙЛ=====
//В переменную $file помещается имя создаваемого файла, в нашем случае Sitemap.
        $file = $_SERVER["DOCUMENT_ROOT"] . "/sitemap.xml";
//Далее создается файл Sitemap, если файл существует, он затирается.
        $fp = fopen($file, "w") or die('Ошибка: Не возможно открыть файл!');
//В созданный файл помещается содержимое подготовленное ранее
        fwrite($fp, $Sitemap) or die('Ошибка: Не возможно сделать запись в файл!');
//Закрытие файла. 
        fclose($fp);
        exit();
    }

    private static function saveAnalizCategory($id, $category)
    {
        R::setStrictTyping(false);
        R::exec('DELETE FROM `analiz_category` WHERE `analiz_id` = ?', array(intval($id)));
        $categories = explode(',', $category);
        foreach ($categories as $cat) {
            if (trim($cat))
                R::exec('INSERT INTO `analiz_category` SET `category_id` = ?, `analiz_id` = ?', array(self::getCategoryId(trim($cat)), $id));
        }
    }

    private static function getAnalizCategories($id)
    {
        return R::getAll('
            SELECT `c`.*
            FROM
                `category` as `c`,
                `analiz_category` as `ac`
            WHERE
                `ac`.`analiz_id` = ?
                AND `ac`.`category_id` = `c`.`id`
        ', array(intval($id)));
    }

    /**
     * @param $url
     * @return int
     */
    public static function getTaskId($url)
    {
        self::Connect();
        $rec = R::getRow('SELECT `id` FROM `analiztask` WHERE url LIKE :url', array(':url'=> $url));
        if(!$rec){
            $record = R::dispense('analiztask');
            $record->url = $url;
            return R::store($record);
        }
        return $rec['id'];
    }
}
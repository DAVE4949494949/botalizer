<?php

namespace Userset\ShortUrl\Controllers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Userset\ShortUrl\Models\ShortUrlModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Core\Template;
use Core\Repository;

class ShortUrlController
{

    public static function IndexAction()
    {
        $config = Repository::GetGonfig();
        Repository::$data['base_url'] = $config['base_url'];
        Repository::$data['last_result_short_link'] = \Userset\ShortUrl\Models\ShortUrlModel::LastLinkShort();
        $html = Template::RenderTemplate('pages/index.twig', Repository::$data);
        return new Response($html);
        //throw new NotFoundHttpException('Такой страницы нет');
    }
     public static function HomeAction()
    {
        $config = Repository::GetGonfig();
        Repository::$data['base_url'] = $config['base_url'];
        Repository::$data['last_result_short_link'] = \Userset\ShortUrl\Models\ShortUrlModel::LastLinkShort();
        Repository::$data['last_result_analiz_link'] = \Userset\Analiz\Models\Analiz::LastLinkAnaliz();
        $html = Template::RenderTemplate('pages/home.twig', Repository::$data);
        return new Response($html);
        //throw new NotFoundHttpException('Такой страницы нет');
    }

    public function CreateAction()
    {
        $request = new Request($_POST);
        $go = $request->query->get('url');
        if(!empty($go)){
            if(@\Userset\Analiz\Module\Url::file_get_contents_new($url)){
                exit('Не правильный URL');
            }
            $url = self::NormalizeURL($request->query->get('url'));
            
            if(empty($url)){Repository::$data['error'][] = 'Введите в форму Url который следует сократить';}
            Repository::$data['full_url'] = $url;
            Repository::$data['short_url'] = ShortUrlModel::SaveFullUrl($url);
            Repository::$data['base_url'] = Repository::$config['base_url'];
            if(Repository::$config['post_in_twitter']){
                $messager = Repository::$data['title'] . ' '.Repository::$config['base_url'].'/'.Repository::$data['short_url'];
                \Userset\Analiz\Module\PostInTwitter::post($messager);
            }
        }else{
            Repository::$data['error'][] = 'Введите в форму Url который следует сократить';
        }
        $html = Template::RenderTemplate('pages/create_shorturl.twig', Repository::$data);
        return new Response($html);
    }

    static public function RedirectToFullUrlAction($short_code)
    {
        $data = \Userset\ShortUrl\Models\ShortUrlModel::GetFullURL($short_code);
        if($data){
        //$source = \Userset\Analiz\Module\Url::Init($data['full_url']);
        Repository::$data['full_url'] = $data->full_url;
        Repository::$data['short_code'] = $data->short_url;
        Repository::$data['host'] = $data->host;
        Repository::$data['title'] = $data->title;
        Repository::$data['description'] = $data->description;
        Repository::$data['keywords'] = $data->keywords;
        //echo '<pre>';
        //print_r(Repository::$data);
        //echo '</pre>';
        $html = Template::RenderTemplate('pages/redirect.twig', Repository::$data);
        return new Response($html);
        }else{
            return new Response('Такой страницы нет',404);
        }
    }

    
    static function NormalizeURL($full_url)
    {
       
        $url_info = parse_url($full_url);

        if (empty($url_info['scheme']))
        {
            $get_url = 'http://' . $full_url;
        } else
        {
            $get_url = $full_url;
        }
        return $get_url;
    }

}

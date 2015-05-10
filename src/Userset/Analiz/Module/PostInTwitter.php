<?php

namespace Userset\Analiz\Module;

use Userset\Analiz\Module\GooglePR;
use Core\Repository;
use Core\Curl;

class PostInTwitter
{

    //static $CONSUMER_KEY = Repository::$config['CONSUMER_KEY'];
    //static $CONSUMER_SECRET = Repository::$config['CONSUMER_SECRET'];
    //static $OAUTH_TOKEN = Repository::$config['OAUTH_TOKEN'];
    //static $OAUTH_SECRET = Repository::$config['OAUTH_SECRET'];

    static public function post($message)
    {
       require_once(__DIR__.'/Twitter/twitteroauth.php'); 
       $CONSUMER_KEY = Repository::$config['CONSUMER_KEY'];
       $CONSUMER_SECRET = Repository::$config['CONSUMER_SECRET'];
       $OAUTH_TOKEN = Repository::$config['OAUTH_TOKEN'];
       $OAUTH_SECRET =  Repository::$config['OAUTH_SECRET'];

        $connection = new \TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $OAUTH_TOKEN, $OAUTH_SECRET);
        $content = $connection->get('account/verify_credentials');
        //var_dump($content);
        //Постим сообщение
        $connection->post('statuses/update', array('status' => $message ));
        //exit();
    }

}
<?php

return array(
    'base_url' => 'http://botalizer.com',
    /** 
    *  База данных 
    */
    'host' => 'localhost',
    'dbname' => 'seo49',
    'user' => 'seo',
    'password' => 'passdemo',
    //Записывать результат анализа сайта в Базу данных
    'write_result_in_db' => TRUE, // Включение и отключение , при FALSE данные базу сохранятся не будут.
    'cache_template' => FALSE, // Включение Кеширования
    // Строка Яндекс XML
    // Пример http://xmlsearch.yandex.ru/xmlsearch?user=user_name&key=03.154511994:817a3ff5565e74d1437061dd54456543
    'ya_xml' => '',
    // twitter
    // Постить в Твиттер?
   'post_in_twitter' => TRUE, // ВКЛЧЕНИЕ ОТКЛЮЧЕНИЕ ОТСЫЛКИ ССЫЛОК В ТВИТТЕР
   'CONSUMER_KEY' => '',
   'CONSUMER_SECRET' => '',
   'OAUTH_TOKEN'=>'',
   'OAUTH_SECRET'=>'',
   

);

<?php

namespace Userset\Analiz\Module;

use Userset\Analiz\Module\Url;

class CatCheck
{
	
    //dmoz for all categories number result	
    function CheckInDMOZ($url)
    {
        $url_sour = "http://www.dmoz.org/search?q=$url";
        
        if ($s = file_get_contents($url_sour))
        {
            if (preg_match('/<strong>DMOZ Sites<\/strong>\s*<small>\(\d+\-\d+ of (\d+)\)<\/small>/i', $s, $a))
            {
                return $a[1];
            } else
            {
                return 0;
            }
        }
    }

//CheckInDMOZ

    //Yahoo
function CheckInYahoo ($url) {

        $url = "http://dir.search.yahoo.com/search?p=".$url;
        $data = file_get_contents($url);
        if (ereg('<div class=\"res\">', $data)) {

            $value = 1;

        } else {

            $value = 0;

        } 

        return $value;

    }

//Yahoo

    //SafeBrowsing
function CheckInSafeBrowsing ($url) {

        $url = "http://www.google.com/safebrowsing/diagnostic?hl=en&site=".$url;
        $data = file_get_contents($url);
        if (ereg('This site is not currently listed as suspicious', $data)) {

            $value = 1;

        } else {

            $value = 0;

        } 

        return $value;

    }

//SafeBrowsing
}
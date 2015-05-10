<?php
namespace Userset\Analiz\Module;
use Userset\Analiz\Module\Url;

class IPSitesList {
   
   var $selfurl = "";
   var $selfip = "";
   var $sitescount = 0;
   var $lookmore = "http://www.bing.com/search?FORM=MSNH&q=IP:";	
   
   function AddRemHttpURL($url,$addu = 1) {
    $urlq = strtolower($url); 
    if ($addu == 0) {
     if (substr($urlq,0,7) == "http://") {
   	   $urlq = substr($urlq,7,strlen($urlq));
     }	
    }
    else {
      if (substr($urlq,0,7) != "http://") {	     	
   	  $urlq = "http://$urlq";
    }	   	
    }
    return $urlq;	
   } //AddRemHttpURL  
	
   private function GetContext() {	
	// Инициализация Класса обёртки cURL;
        $curl = new \Core\Curl(array('proxy' => true));
        // Получение исходного кода по URL;
        $sourse_bing = $curl->get($this->lookmore);
        //echo $this->lookmore;
        return $this->GetResultNumber($sourse_bing);
   }//GetContext	
	
   private function CheckCorrektIP($ip) {	 
	 return preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $ip);	
   }//CheckCorrektIP
   
   //parse number
   private function GetResultNumber($source) {   
         preg_match('|<span class="sb_count" id="count">(.*)</span>|sUSi',$source,$str);
         if(count($str) < 1){return 0;}
	 return filter_var($str[0],FILTER_SANITIZE_NUMBER_INT);	 	
   }//GetResultNumber
   
   //return information in variables
   function GetIP_info($ip_or_url) {    
    if (!$this->CheckCorrektIP($ip_or_url)) {    	
     $this->selfurl = $this->AddRemHttpURL($ip_or_url);
     $sinfo = @parse_url($this->selfurl); 
     $this->selfip = @gethostbyname($sinfo["host"]);
     } else { $this->selfip = $ip_or_url; }	
	$this->lookmore = $this->lookmore.$this->selfip;	
	$this->sitescount = $this->GetContext();
        
    if (!$this->sitescount) {$this->sitescount = "0";}	
	return true;	
   }//GetIP_info
   	
}//ip_sites_list
//---------------------------------------------------------------------------------

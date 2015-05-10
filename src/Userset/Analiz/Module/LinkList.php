<?php

namespace Userset\Analiz\Module;

use Core\Repository;

class LinkList {
	
	var $url = "";
	var $context = "";
	var $links;
	var $images;
	var $obj;
	var $actfag = false;
	
	//check inside url
	private function CheckInsideUrl($url) {
	  if ((substr($url,0,1) != "#") and (strtolower(substr($url,0,11)) != "javascript:")) {
	  	$sppos = @strpos($url,"://");
	  	if (($sppos <= 0) or ($sppos > 5)) {
	  	 if ($url != "") {return true;}	
	  	}	
	  }	   	
	 return false;	
	}//CheckInsideUrl
	
	function html_links($domain='',$datacontext="") {
	 if (($domain != '') or ($datacontext != '')) {
	  return $this->Init_data_source($domain,$datacontext);	
	 }	
	}
	
	//initialize url for context, post query if empty
	function Init_data_source() {
	  $this->context = Url::$sourse_html;
	  $this->url = Url::$url;
	  
	  $this->actfag = true;
	  return true;		
	}//Init_data_source
  
   private function free_www_data($strdata) {    
    if ($strdata == "") {return "";}
    $strdata = strtolower($strdata);
    if (substr($strdata,0,3) == "www") {
	 return substr($strdata,4);	
	}
	return $strdata;	
   }//free_www_data
   
   //получение текста ссылки
   private function GetTextLink($text) {
	$sdata = @iconv("UTF-8","iso8859-1",$text);
	if (trim($sdata) == '') { $sdata = $text; }
	return $sdata;
   }//GetTextLink
   
   //all links from page source
   function GetLinks($withsubdomeins = false) {
    if (!$this->actfag) {return false;}      
    $insidearray = array();
    $outsidearra = array();
    $subdomlinks = array();
    $regarr      = array();   
    $surl =  strtolower($this->url);
    if (substr($surl,0,7) != "http://") { $surl = "http://$surl"; }      
    $infoq = @parse_url($surl);
    if (!$infoq) {return false;}
    $infoq['host'] = $this->free_www_data($infoq['host']);
    
    $dom = new \DOMDocument();
    $html = Url::file_get_contents_new(Url::$url);
    if(Url::$charset == 1){
       // $html = @iconv("windows-1251", "UTF-8", $html);
    }
    @$dom->loadHTML($html);
    
    if (!$dom) {return false;}
    
    $xpath = new \DOMXPath($dom);    
    $hrefs = $xpath->evaluate("//a");    
    for ($i = 0; $i < $hrefs->length; $i++) {
	  $href = $hrefs->item($i);
	  $linkhref= strtolower($href->getAttribute('href'));
	  if ((substr($linkhref,0,1) != "#") and (substr($linkhref,0,11) != "javascript:") and (substr($linkhref,0,7) != 'mailto:')) {	  	
	   $sppos = @strpos($linkhref,"://");	   
	   if (($sppos <= 0) or ($sppos > 5)) {	   	
	    if ($linkhref != "") {	     
		 if (!@in_array($linkhref,$regarr)) {	
		  $stext = $href->nodeValue;
		  if ($stext != "") {	
	       $insidearray['orig'][]	= $linkhref;	
	       if (substr($linkhref,0,1) != "/") {$linkhref = "/".$linkhref;}
	       $insidearray['href'][]	= $infoq['scheme']."://".$infoq['host'].$linkhref;
	       $insidearray['text'][]   = $this->GetTextLink($stext); //"";//$stext;
	       $regarr[] = $linkhref;
	      }
		 }	     
	    }
	   } else {
	   	if (!@in_array($linkhref,$regarr)) {	   		
	   	$infoq1 = @parse_url($linkhref);
	   	if ($infoq1) { $infoq1['host'] = $this->free_www_data($infoq1['host']);	}
	   	$stext = $href->nodeValue;
	   	if ($infoq1['host'] == $infoq['host']) {
		  if (@$infoq1['path'] == "" or @$infoq1['path'] == "/") {
		   	$insidearray['orig'][]	= $linkhref;
		  } else { 
                      @$insidearray['orig'][] = $infoq1['path'].$infoq1['query']; }
		  $insidearray['href'][] = $linkhref;
		  $insidearray['text'][] = $this->GetTextLink($stext);//$href->nodeValue;		  			
		} else {		  	 
	   	  if ($withsubdomeins) {	   	  	
	   	   if (substr($infoq1['host'],strlen($infoq1['host'])-strlen($infoq['host'])-1) == ".".$infoq['host']) {
			$subdomlinks['href'][] = $linkhref;	
			$subdomlinks['text'][] = $this->GetTextLink($stext);
		   } else { $outsidearra['href'][] = $linkhref; $outsidearra['text'][] = $this->GetTextLink($stext); }								
		  }	else { $outsidearra['href'][] = $linkhref; $outsidearra['text'][] = $this->GetTextLink($stext); } 
		}		
		$regarr[] = $linkhref;
		}//in array	
	   }	  		  	  	
	  }	
	}   
     $this->links = array(
	 "inslinks"=>$insidearray,
	 "outlinks"=>$outsidearra,
	 "subdomainsl"=>$subdomlinks,
	 );
	 return true;			
   }//GetLinks
  
  //get all images
  function GetImages() {
    if (!$this->actfag) {return false;}
       
    $insidearray = array();
    $outsidearra = array();
    $regarr      = array();
    
     $surl =  strtolower($this->url);
     if (substr($surl,0,7) != "http://") {
      $surl = "http://$surl";
	 }
	  
    $infoq = @parse_url($surl);
    if (!$infoq) {return false;}
	
    $dom = new \DOMDocument();
     $html = Url::file_get_contents_new(Url::$url);
    if(Url::$charset == 1){
       // $html = @iconv("windows-1251", "UTF-8", $html);
    }
    @$dom->loadHTML($html);
    if (!$dom) {return false;}
    
    $xdoc = new \DOMXPath($dom);
    
    $atags = $xdoc ->evaluate("//a");
    
    $index=0;
    for ($i = 0; $i < $atags->length; $i++)
    {
	  $atag = $atags->item($i);
	  $imagetags=$atag->getElementsByTagName("img");
	  $imagetag=$imagetags->item(0);
	  
	  if(sizeof($imagetag)>0) {
	    $imagelinked['src'][$index]=$imagetag->getAttribute('src');
		$imagelinked['link'][$index]=$atag->getAttribute('href');
		$index=$index+1;	
	  }	
	  	
	}
	$imagetags = $xdoc ->evaluate("//img");
	$index=0;
	$indexlinked=0;
	for ($i = 0; $i < $imagetags->length; $i++) {
	  $imagetag = $imagetags->item($i);
	  $imagesrc=$imagetag->getAttribute('src');
	  
	  $imageheigth=$imagetag->getAttribute('height');
	  $imagewidth =$imagetag->getAttribute('width');
	  
	  $image['link'][$index]=null;
	  
	  $image['height'][$index]=$imageheigth;
	  $image['width'][$index] =$imagewidth;
	  
	  if($imagesrc === @$imagelinked['src'][$indexlinked]) { 
	  	if ($this->CheckInsideUrl($imagelinked['link'][$indexlinked])) {
	     $image['link'][$index]=$infoq['scheme']."://".$infoq['host']."/".$imagelinked['link'][$indexlinked];
	    } else {
		 $image['link'][$index]=$imagelinked['link'][$indexlinked];	
		}
		$indexlinked=$indexlinked+1;	
	  }	
	  
	  if ($this->CheckInsideUrl($imagesrc)) {
	   $image['src'][$index]=$infoq['scheme']."://".$infoq['host']."/".$imagesrc;
	  } else {
	   	$image['src'][$index]=$imagesrc;
	  }
	  $index=$index+1;
	}	  
	  
	@$this->images = $image;
	return true;  		
  }//GetImages
  	
  //	
	
}//html_links
//------------------------------------------------------------------------------------

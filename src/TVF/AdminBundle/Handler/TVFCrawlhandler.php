<?php

namespace TVF\AdminBundle\Handler;

class TVFCrawlhandler {
  public function __construct($img_dir)
  {
      $this->img_dir = $img_dir;
  }

  public function curl_request($url){
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);

    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";
    $header[] = "Pragma: "; //browsers keep this blank.

    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3');
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($curl, CURLOPT_AUTOREFERER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    $sourcecode = curl_exec($curl); //execute the curl command
    if (!$sourcecode)
    {
      echo "cURL error number:" .curl_errno($curl);
      echo "cURL error:" . curl_error($curl);
      exit;
    }
    curl_close($curl);
    return $sourcecode;
  }
}

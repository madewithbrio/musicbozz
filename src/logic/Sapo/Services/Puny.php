<?php

namespace Sapo\Services;

class Puny {
  const SERVICE_URL = 'http://pesquisa.sapo.pt/punify?xml=1&url=';
  const SERVICE_RANDOM_URL = 'http://sl.pt/punify?xml=1&random=1&url=';
  const MAX_RETRIES = 3;

  public static function punify($url, $try = 0, $random = false, $expiresTS = null)
  {
    $try++;
    if ($try >= self::MAX_RETRIES) return null;

    try {
      $requestUrl = ($random ? self::SERVICE_RANDOM_URL : self::SERVICE_URL) . urlencode($url);
      if ($random && $expiresTS) $requestUrl .= '&expires=' . urlencode(date('Y-m-d H:i:s', $expiresTS));  //2003-12-31 01:02:03
      $opts = array(
        'http'=>array(
          'method' => "GET",
          'header' => "User-Agent: SapoMobile/1.0\r\n" . "Accept-Charset: utf-8;\r\n",
          'timeout' => 1,
        )
      );

      $context = stream_context_create($opts);
      $response = @file_get_contents($requestUrl, null, $context);

      $punyUrl = simplexml_load_string($response)->ascii;
      if (!$punyUrl) return self::punify($url, $try);

      return $punyUrl;

    } catch(Exception $e) {
      return self::punify($url, $try);
    }
  }
	
}

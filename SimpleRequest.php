<?php

class SimpleRequest {
  
  # SimpleRequest v1.0
  # Github: renzbobz
  # 2/18/21
  
  public $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
  public $methodsThatHasPostField = ['POST', 'PUT', 'PATCH'];
  
  public $options = ['headers', 'curl_opts'];
  
  private $getSubDomainRegex = '/\.(.*?)\./';
  
  public function __construct($opts=[]) {
    $this->opts = $opts;
  }
   
  public function request($method, $url, $data=[], $opts=[]) {
    
    if (!in_array($method, $this->methods)) throw new Exception('Invalid method "'.$method.'"');
    
    $headers = [];
    $customHeaders = ($this->opts['headers'] ?? []) + ($opts['headers'] ?? []);
    if ($customHeaders) {
      foreach ($customHeaders as $key => $val) {
        if (!is_string($key)) {
          list($key, $val) = explode(':', $val, 2);
        }
        $headers[$key] = $val;
      }
    }
    
    if ($headers) {
      foreach ($headers as $key => $val) {
        $headers[] = $key.':'.$val;
        unset($headers[$key]);
      }
    }
    
    $curlopts = [
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_HEADER => true
    ];
    
    $customCurlopts = ($this->opts['curl_opts'] ?? []) + ($opts['curl_opts'] ?? []);
    if ($customCurlopts) {
      foreach ($customCurlopts as $opt => $val) {
        $curlopts[$opt] = $val;
      }
    }
    
    if (in_array($method, $this->methodsThatHasPostField)) {
      $curlopts[CURLOPT_POSTFIELDS] = $data;
    }
    
    if (preg_match($this->getSubDomainRegex, $url, $matched)) {
      $subDomain = $matched[1];
    }
    
    $urlParts = parse_url($url);
    if (!isset($urlParts['scheme'])) {
      if (isset($this->opts['base_url'])) {
        $baseUrl = $this->opts['base_url'];
        if ($subDomain) {
          $urlParts = parse_url($baseUrl);
          $host = $urlParts['host'];
          $host = explode('.', $host);
          if (count($host) == 3) {
            $domain = implode('.', array_slice($host, 1));
          } else {
            $domain = implode('.', $host);
          }
          $url = $urlParts['scheme'].'://'.$subDomain.'.'.$domain.preg_replace($this->getSubDomainRegex, '', $url);
        } else {
          $url = $baseUrl.$url;
        }
      } else {
        throw new Exception('Invalid url "'.$url.'"');
      }
    }
    
    if ($this->_session) {
      $curlopts[CURLOPT_COOKIE] = $this->_session;
    }
    
    $ch = curl_init($url);
    curl_setopt_array($ch, $curlopts);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    list($header, $body) = explode("\r\n\r\n", $response, 2);
    
    $status = $info['http_code'];
    $isOK = $status >= 200 && $status <= 299;
    
    $headers = $this->parseHeaders($header);
    if (!$headers) $headers = $header;
    
    $http = $this->parseHttp($header);
   
    return (object) [
      'ok'          => $isOK, 
      'raw'         => $response,
      'headers'     => $headers,
      'raw_header'  => $header,
      'body'        => $body,
      'info'        => $info,
      'code'        => $status,
      'http'        => $http
    ];
    
  }
  
  public function get($url, $opts=[]) {
    return $this->request('GET', $url, [], $opts);
  }
  
  public function post($url, $data=[], $opts=[]) {
    return $this->request('POST', $url, $data, $opts);
  }
  
  public function session($method, $url, $data=[], $opts=[]) {
    
    $isValidMethod = in_array($method, $this->methods);
    if (!$isValidMethod) {
      $url = $method;
      $method = 'GET';
    }
    
    $response = $this->request($method, $url, $data, $opts);
    
    $cookies = '';
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response['raw_header'], $matches);
    foreach($matches[1] as $item) $cookies .= $item.'; ';
    $this->_session = $cookies;
    
    return $response;
    
  }
  
  public function clearSession() {
    $this->_session = null;
    return true;
  }
  
  public function parseHeaders($header) {
    if (!preg_match_all('/(.*?)\:(.*)\\r/', $header, $matches) || !isset($matches[1], $matches[2])) return false;
    $headers = [];
    foreach ($matches[1] as $index => $key){
      $val = $matches[2][$index];
      if (isset($headers[$key])) {
        $headers[$key] = array_merge([$headers[$key]], [$val]);
        continue;
      }
      $headers[$key] = $val;
    }
    return $headers;
  }
  
  public function parseHttp($header) {
    $http = [];
    preg_match('/(.*?) (.*?) (.*)/', $header, $matches);
    $http['version'] = $matches[1];
    $http['code'] = $matches[2];
    $http['reason'] = $matches[3];
    return $http;
  }
  
  public function __destruct() {
    $this->clearSession();
  }
  
}

?>

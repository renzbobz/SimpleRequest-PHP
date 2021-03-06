# SimpleRequest
Send http request easily

Coded on phone - 2/18/21

## Getting started
Download the SimpleRequest.php and then require it to your project and then it's now ready to use!
```php
$sr = new SimpleRequest();
```
You can pass an array of options to make the default options for your upcoming request.
```php
$sr = new SimpleRequest([
  "base_url" => "https://example.com",
  "headers" => [
    "Content-type" => "application/json"
  ],
  "curl_opts" => [
    CURLOPT_FOLLOWLOCATION => true
  ]
]);
```

## Usage
#### Perform GET request
```php
# This will use the base_url
$res = $sr->get("/users/3");
# This will not use the base_url
$res = $sr->get("https://example.com/users/3");
echo $res->body;
```

#### Perform POST request
```php
$res = $sr->post("/users/3", $data);
// or
$res = $sr->post("https://example.com/users/3", $data);
```

#### Perform CUSTOM request
```php
$res = $sr->request("PATCH", "/users/3", $data);
// or
$res = $sr->request("PATCH", "https://example.com/users/3", $data);
```

#### With session
```php
// Login your account
$data = json_encode([
  "username" => "Renz",
  "password" => "admin123"
]);
$res = $sr->session("POST", "/auth/login", $data);
// If login success
// You can now access other api that needs authorization
$res = $sr->get("/billing/credit");
```
Clear session
```php
$sr->clearSession();
```
It will also trigger on destruct

#### With options
```php
$data = json_encode([
  "status" => 1
]);
$opts = [
  "headers" => [],
  "curl_opts" => []
];
$res = $sr->post("/users/3", $data, $opts);
// or
$res = $sr->request("POST", "/users/3", $data, $opts);
```
##### Change default options
```php
$sr->opts["curl_opts"] = [
  CURLOPT_FOLLOWLOCATION => false
];
```

#### Add subdomain to base_url
Use dot in the start and the end of the subdomain
```php
// Base url: https://example.com
$res = $sr->get(".auth./v1/pin");
// Url now: https://auth.example.com/v1/pin
```

### Response
Response object contains:
| Key        | Value   | Description                                                |
|------------|---------|------------------------------------------------------------|
| ok         | boolean | Returns true if status code is in range between 200 to 299 |
| raw        | string  | Raw http response includes header and body                 |
| headers    | array   | Parsed http header                                         |
| raw_header | string  | Raw http header response                                   |
| body       | string  | Raw http body response                                     |
| info       | array   | Curl_getinfo result                                        |
| code       | number  | Http response code                                         |
| http       | array   | Contains http version, code, reason(Code name)             |

### Methods
```php
$sr->request(<method><, url>[, data][, options]);
```
```php
$sr->get(<url>[, options]);
```
```php
$sr->post(<url>[, data][, options]);
```
```php
$sr->session(<method><, url>[, data][, options]);
```
```php
$sr->clearSession();
```
```php
$sr->parseHeaders(<header>);
```
```php
$sr->parseHttp(<header>);
```

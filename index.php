<?php

/*
 * Simple example to implement OAuth 2.0 in PHP Slim framework Ver 3.9
 * I am using "bshaffer" library @ https://github.com/bshaffer/oauth2-server-php
 * Say HI at email: ch.rajshekar@gmail.com, Skype: ch.rajshekar
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require './vendor/autoload.php';

$username='libauth';
$filename='/etc/libauth.js/oauth_pass';
$handle = fopen($filename, "r");
$passwd = fscanf($handle,"%s");
fclose($handle);

// configuration for Oauth2 DB
$config['displayErrorDetails'] = true;
$config['odb']['host'] = "10.0.0.33";
$config['odb']['user'] = $username;
$config['odb']['pass'] = $passwd[0];
$config['odb']['dbname'] = "oauth2";

$app = new Slim\App(["settings" => $config]);
$app->add(new \Eko3alpha\Slim\Middleware\CorsMiddleware([
    'https://oauth2.sexycoders.org' => ['POST','GET'],
    'https://data.sexycoders.org' => ['POST','GET'],
    'https://uniclient.sexycoders.org' => ['POST','GET'],
  ]));

$container = $app->getContainer();


// Container to create a oauth2 database connection
$container['oauth'] = function($c){
    $db = $c['settings']['odb'];

    OAuth2\Autoloader::register();
    $storage = new OAuth2\Storage\Pdo(array('dsn' => "mysql:dbname=".$db['dbname'].";host=".$db['host'], 'username' => $db['user'], 'password' => $db['pass']));
    return $storage;
};


$app->post('/token',function(Request $request, Response $response){

$ip="10.0.0.20";

$url = "http://".$ip;

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);


$headers = array(
   "Accept: application/json",
   "Content-Type: application/json",
);

curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$data = $request->getBody();

curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

$resp = curl_exec($curl);

curl_close($curl);

    $redis = new Redis();
    $redis->connect('10.0.0.250', 6379);
    $redis->set("test",json_encode($resp));

    //// @ generate a fresh token
    //// @ Token is valid till 1 hr or 3600 seconds after which it expires
    //// @ Token will not be auto refreshed
    //// @ generation of a new token should be handled at application level by calling this api

    //// @ add parameter : ,['access_lifetime'=>3600] if you want to extent token life time from default 3600 seconds

    //$server = new OAuth2\Server($this->oauth);
    //$server->addGrantType(new OAuth2\GrantType\ClientCredentials($this->oauth));
    //$server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->oauth));

    //// @ generate a Oauth 2.0 token in json with format below
    //// @ {"access_token":"ac7aeb0ee432bf9b73f78985c66a1ad878593530","expires_in":3600,"token_type":"Bearer","scope":null}
    //$t=$server->handleTokenRequest(OAuth2\Request::createFromGlobals());
    //$j=$request->getBody();
    //$a=[];
    //parse_str($j,$a); 
    //$redis = new Redis();
    //$redis->connect('10.0.0.250', 6379);
    //$redis->set("test",json_encode($t));
    ////$redis->set($t->access_token,$a['client_id']);
    //$t->send();

});

$app->post('/token_callback',function(Request $request, Response $response){

    // @ generate a fresh token
    // @ Token is valid till 1 hr or 3600 seconds after which it expires
    // @ Token will not be auto refreshed
    // @ generation of a new token should be handled at application level by calling this api

    // @ add parameter : ,['access_lifetime'=>3600] if you want to extent token life time from default 3600 seconds

    $server = new OAuth2\Server($this->oauth);
    $server->addGrantType(new OAuth2\GrantType\ClientCredentials($this->oauth));
    $server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->oauth));

    // @ generate a Oauth 2.0 token in json with format below
    // @ {"access_token":"ac7aeb0ee432bf9b73f78985c66a1ad878593530","expires_in":3600,"token_type":"Bearer","scope":null}
    $t=$server->handleTokenRequest(OAuth2\Request::createFromGlobals());
    $j=$request->getBody();
    $a=[];
    parse_str($j,$a); 
    $redis = new Redis();
    $redis->connect('10.0.0.250', 6379);
    $redis->set("test",json_encode($t));
    //$redis->set($t->access_token,$a['client_id']);
    $t->send();

});

$app->post('/validate',function(Request $request, Response $response){

    // @ Validate Oauth Token passed via http headers in "Authorization bearer"
    $validate = new Tokens($this->oauth);
    $validate->validateToken();

    // @ Pass a Message if Oauth 2.0 token is valid to complete test
    return json_encode(array('success' => true, 'message' => 'Token verified!'));

});


$app->run();

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

    $ip="10.0.0.2";

    $log_redis = new Redis();
    $log_redis->connect('10.0.0.252', 6379);

    $url = "http://".$ip."/token_callback";

    $log_redis->set("token_callback_ip",$ip);
    $log_redis->set("token_callback_url",$url);

    $data = $request->getParsedBody();
    $log_redis->set("token_callback_data",json_encode($data));

    $forwarded_data=$request->getBody()->getContents();
    $log_redis->set("token_callback_forwarded_data",$forwarded_data);

    $headers = array(
    "Content-Type: application/x-www-form-urlencoded",
    );
    $log_redis->set("token_callback_headers",json_encode($headers));


    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $forwarded_data);

    $resp = curl_exec($curl);

    curl_close($curl);
    $log_redis->set("token_callback_response",$resp);
    $log_redis->set("token_callback_inner_check","NO");

    $resp=json_decode($resp);

    $user_redis = new Redis();
    $user_redis->connect('10.0.0.250', 6379);
    $user_redis->set($resp->access_token,$data['client_id']);

    $to_return= new stdClass;
    $to_return->access_token=$resp->access_token;

    return json_encode($to_return);

});

$app->post('/token_callback',function(Request $request, Response $response){


    $log_redis = new Redis();
    $log_redis->connect('10.0.0.252', 6379);
    $log_redis->set("token_callback_inner_check","YES");
    $server = new OAuth2\Server($this->oauth);
    $server->addGrantType(new OAuth2\GrantType\ClientCredentials($this->oauth));
    $server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->oauth));
    $server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
    $log_redis->set("token_callback_inner_check","END");

});

$app->post('/validate',function(Request $request, Response $response){

    // @ Validate Oauth Token passed via http headers in "Authorization bearer"
    $validate = new Tokens($this->oauth);
    $validate->validateToken();

    // @ Pass a Message if Oauth 2.0 token is valid to complete test
    return json_encode(array('success' => true, 'message' => 'Token verified!'));

});


$app->post('/user',function(Request $request, Response $response){
    $data=$request->getParsedBody();
     
    $user_redis = new Redis();
    $user_redis->connect('10.0.0.250', 6379);
    $user=$user_redis->get($data['token']);

$filename='/etc/libauth.js/oauth_pass';
$handle = fopen($filename, "r");
$passwd = fscanf($handle,"%s");
fclose($handle);

    $pdo = new \pdo(
        "mysql:host=10.0.0.33; dbname=master; charset=utf8mb4; port=3306",'libauth',$passwd[0] ,
    [
        \pdo::ATTR_ERRMODE            => \pdo::ERRMODE_EXCEPTION,
        \pdo::ATTR_DEFAULT_FETCH_MODE => \pdo::FETCH_ASSOC,
        \pdo::ATTR_EMULATE_PREPARES   => false,
    ]); 

    $stmt = $pdo->prepare("select * from users where username=?");
    $stmt->execute([$user]);
    $user_data=$stmt->fetch();
    $response->getBody()->write(base64_encode(json_encode($user)));

});

$app->run();

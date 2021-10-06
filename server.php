<?php

include "cors.php";

require "../vendor/autoload.php";

CORS();

// connection for SQLite
//$pdo = new PDO('sqlite:/var/lib/libauth.js/oauth2-database');
//
// connection for MySQL
$password=file_get_contents("/home/sotiris/oauth_pass");
$pdo = new PDO('mysql:host=localhost;dbname=OAUTH2',"uniclient",$password);

// error reporting
ini_set('display_errors',1);error_reporting(E_ALL);

$storage = new OAuth2\Storage\Pdo($pdo);

// Pass a storage object or array of storage objects to the OAuth2 server class
$server = new OAuth2\Server($storage);

// Add the "Client Credentials" grant type (it is the simplest of the grant types)
$server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));

// Add the "Authorization Code" grant type (this is where the oauth magic happens)
$server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));

?>

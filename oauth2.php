<?php

require "../vendor/autoload.php";

function token($server)
    {
        // Handle a request for an OAuth2.0 Access Token and send the response to the client
        $server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
    }

function access($server)
    {

        // Handle a request to a resource and authenticate the access token
        if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
            $server->getResponse()->send();
            die;
        }
        echo json_encode(array('success' => true, 'message' => 'You accessed my APIs!'));
    }

function oauth2($parameter)
    {
        // connection for SQLite
        $pdo = new PDO('sqlite:/var/lib/uniclient/oauth2-database');

        // error reporting
        ini_set('display_errors',1);error_reporting(E_ALL);

        $storage = new OAuth2\Storage\Pdo($pdo);

        // Pass a storage object or array of storage objects to the OAuth2 server class
        $server = new OAuth2\Server($storage);

        // Add the "Client Credentials" grant type (it is the simplest of the grant types)
        $server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));

        // Add the "Authorization Code" grant type (this is where the oauth magic happens)
        $server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));

        if($parameter=='access')
            access($server);
        else
            token($server);
    }
    ?>

# php-slim-proxy

A Dependency-free implementation of a PHP proxy.

Only supports http(s) protocol

Uses apache .htaccess config to route all requests to the proxy.php script


## Possible use cases:
* Set up a CORS proxy to other API services for your app to consume.
* Set up a proxy to completely redirect all http(s) data from current location to another url on the WEB

## How it looks

### Request:
 ``Client`` -> ``[https://mywebsite-A.com (proxy)]`` -> ``[https://mywebsite-b.com]``
### Response:
 ``Client`` <- ``[https://othersite-B.com (proxy)]`` <- ``[https://mywebsite-b.com]``


This way the ``Client`` is never aware of ``https://othersite-B.com`` and thinks its always talking only to ``https://mywebsite-A.com``

You can also modify request to the target and the response that is sent to the client

For example you can add and remove headers, body or params to the request and response

## Requirements:
* PHP 7+ with CURL extension
* Apache with mod-rewrite for routing

## Installation

Just drop the following files into path your client will be calling:

* ``.htaccess`` - Apache routing config
* ``config.php`` - Proxy config
* ``index.php`` - Proxy script

Modify config.php to your requirements

## Example config.php

```php
<?php
$CONFIG = [
    /**
     * Regex pattern for the HOST part of the request URL that needs to be replaced
    */
	"redirect_from" => "/(http|https):\/\/([^\/\?\&]+)/i", 
    /**
     * Regex for the host part of the url to replace with:
    */
	"redirect_to" => "https://myhiddenwebsite.com/",

    //optioanl parameters below can be replaced with null or left empty

    /** 
     * Preflight response:
     * "*", "https://abc.com" or $_SERVER['HTTP_ORIGIN'],
    */
    "cors_origin" => $_SERVER['HTTP_ORIGIN'],
    "cors_headers" => "Origin, Content-Type, X-Auth-Token",
    "cors_methods" =>"GET, POST, PATCH, PUT, DELETE, OPTIONS",

    /**
     *  Modify request to the target 
    */
    "request"=>function($method, $request, $headers){
        //here you can modify request to the target
        return [$method, $request, $headers];
    },
    /** Modify response to the client */
    "response"=>function ($headers, $body, $status){
        return [$headers, $body, $status];
    }
];
```

## .htaccess file
This file is used with apache to route all requests to the proxy.php

```
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Handle Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

# Legal stuff:
* License: MIT
* Created by: Eldar Gerfanov
* .htaccess file was taken without modifications from the lumen php framework
* CODE IS SUPPPLIED FOR FREE AS-IS WITHOUT ANY GUARANTEE OF SECURITY OR FITNESS FOR ANY USAGE

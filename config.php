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


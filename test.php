<?php
//
// This file simply outputs everything that was sent to it.
// For debugging/testing only. Do not deploy it to the server.
//

ini_set('display_errors', '1');
error_reporting(E_ERROR);

$method = $_SERVER['REQUEST_METHOD'];
$req_url = $_SERVER['SCRIPT_URI'] ?? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
$request = $_REQUEST;
$get = $_GET;
$post = $_POST;
$body = file_get_contents('php://input');
$headers = getRequestHeaders();




response(200, [
    "req_url"=>$req_url,
    "method"=>$method,
    "get"=>$get,
    "post"=>$post,
    "body"=>$body,
    "headers"=>$headers,
]);
exit;

function response($status,$data)
{
    header("content-type: application/json");
	http_response_code($status);
	echo json_encode($data, JSON_PRETTY_PRINT);
	exit();
}

/**
 * @returns array [key=>value]
*/
function getRequestHeaders() {
    $headers = array();
    foreach($_SERVER as $key => $value) {
        if (substr($key, 0, 5) <> 'HTTP_') {
            continue;
        }
        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
        $headers[$header] = $value;
    }
    return $headers;
}

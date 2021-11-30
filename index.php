<?php
ini_set('display_errors', '1');
error_reporting(E_ERROR);

require_once "config.php";

$method = $_SERVER['REQUEST_METHOD'];
$req_url = $_SERVER['SCRIPT_URI'] ?? ($_SERVER['REQUEST_SCHEME'] ?? "http" ) . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
$get = $_GET;
$post = $_POST;
$body = file_get_contents('php://input');
$headers = getRequestHeaders();


// respond to preflights
if ($CONFIG['cors_origin'] && $method == 'OPTIONS') {
    // return only the headers and not the content
    header('Access-Control-Allow-Origin: '. $CONFIG['cors_origin']);
    header('Access-Control-Allow-Headers: '. $CONFIG['cors_headers']);
    header('Access-Control-Allow-Methods: '. $CONFIG['cors_methods']);
    exit;
}


//file_put_contents("server_dump_request.log", print_r($headers,true), FILE_APPEND );

$targetUrl = strtok(preg_replace($CONFIG['redirect_from'], $CONFIG['redirect_to'], $req_url), "?");

//echo $url;
//validate target URL
if (!preg_match("/^(http|https):\/\//", $targetUrl)) {
	response(400, "url is required");
}

$targetMethod = $method;
$targetGet = $get;
$targetPost = $post;
$targetHeaders = $headers;
$targetBody = $body;
//unset($targetHeaders['Host']);

//Modify request if request config value is callable
if (is_callable($CONFIG['request'])) {
    $processedRequest = call_user_func($CONFIG['request'], $targetMethod, $targetGet, $targetPost, $targetBody, $targetHeaders);
    $targetMethod = $processedRequest[0];
    $targetGet = $processedRequest[1];
    $targetPost = $processedRequest[2];
	$targetBody = $processedRequest[3];
    $targetHeaders = $processedRequest[4];
}

$targetResponseBody = httpRequest(
    $targetUrl,
    $targetMethod,
    $targetGet,
    $targetPost,
    $targetBody,
    $targetHeaders,
    $targetResponseStatus, //out
    $targetResponseHeaders //out
);

//file_put_contents("server_dump_request.log", "return headers" . print_r($retHeaders,true), FILE_APPEND );

$responseBody = $targetResponseBody;
$responseStatus= $targetResponseStatus;
$responseHeaders = $targetResponseHeaders;

//Modify response if response config value is callable
if (is_callable($CONFIG['response'])) {
    $processedResponse = call_user_func($CONFIG['response'], $targetResponseHeaders, $targetResponseBody, $targetResponseStatus);
    $responseHeaders = $processedResponse[0];
    $responseBody = $processedResponse[1];
    $responseStatus = $processedResponse[2];
}

if ($responseStatus == 0) {
    $responseStatus = 500;
}

//Set headers for response to the client
foreach ($responseHeaders as $key=>$header){
	header( $key . ': ' . $header);
}
//respond client
response($responseStatus, $responseBody);
exit;


function response($status,$data)
{
	http_response_code($status);
	echo $data;
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
/**
 * @param string $url
 * @param string $method - POST, GET
 * @param array $queryArray [key=>value]
 * @param array $headers [key=>value]
 * @param int &$status -  response status code
 * @return string|null
 */
function httpRequest($url, $method, $queryArray, $post=[], $body = null, $headers = null, &$status = null, &$returnHeaders = null) {
    $request = curl_init();
    // Set request options
    try {
        switch ($method) {
            case "PUT":  
            case 'DELETE':
            case 'OPTIONS':
            case 'POST':
                curl_setopt($request, CURLOPT_URL, $url . ($queryArray ? "?" . http_build_query($queryArray):""));
                if (count($post) > 0){
                    curl_setopt($request, CURLOPT_POST, 1);
                    curl_setopt($request, CURLOPT_POSTFIELDS, $post);
                    //these will be auto-calulated
                    unset($headers['Content-Type']);
                    unset($headers['Content-Length']);
                }else{
                    curl_setopt($request, CURLOPT_CUSTOMREQUEST, $method);
                    curl_setopt($request, CURLOPT_POSTFIELDS, $body);
                }
            break;
            case 'GET':
            default:
                curl_setopt_array($request, array(
                    CURLOPT_URL => $url . ($queryArray ? "?" . http_build_query($queryArray):""),
                ));
        }


        $k_headers = [];
        if (!empty($headers)){
            foreach ($headers as $key=>$value){
                $k_headers[] = $key . ": " . $value;
            }
        }
        curl_setopt($request, CURLOPT_HTTPHEADER, $k_headers);

        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_HEADER, true);
		
		//$returnHeaders = [];
		curl_setopt($request, CURLOPT_HEADERFUNCTION,
		  function($curl, $header) use (&$returnHeaders)
		  {
			$len = strlen($header);
			$header = explode(':', $header, 2);
			if (count($header) < 2) // ignore invalid headers
			  return $len;

			$returnHeaders[strtolower(trim($header[0]))] = trim($header[1]);

			return $len;
		  }
		);

        /*if (!empty($username) && !empty($password)) {
            curl_setopt($request, CURLOPT_USERPWD, "$username:$password");
            curl_setopt($request, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }*/
        // Execute request and get response and status code

        $response = curl_exec($request);
        $status = curl_getinfo($request, CURLINFO_HTTP_CODE);
		//list($returnHeaders, $body) = explode("\r\n\r\n", $response, 2);
		$header_size = curl_getinfo($request, CURLINFO_HEADER_SIZE);
		//$returnHeaders = substr($response, 0, $header_size);
		$body = substr($response, $header_size);

        curl_close($request);

        return $body;
    } catch (Exception $ex) {
        return null;
    }
    return null;
}
?>
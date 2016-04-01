<?php

session_start();

define("HTTPS",              isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on");
define("DOMAIN",             isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ""); 
define("DOCUMENT_ROOT",      __DIR__);
$temp = str_replace(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : __DIR__, "", DOCUMENT_ROOT);
$temp = strlen($temp) < 2 ? "/" : $temp;
$temp = preg_match("@^/@", $temp) ? $temp : ("/".$temp);
$temp = preg_match("@/$@", $temp) ? $temp : ($temp."/");
define("WEB_ROOT", $temp);
define("BASE_URL", "http".(HTTPS?"s":"")."://".DOMAIN.WEB_ROOT);

define("RECAPTCHA_SITE_KEY", "");
define("RECAPTCHA_SECRET_KEY", "");

const TITLE = "Story Creator";
const KEYWORDS = "Story Creator";
const DESCRIPTION = "Story Creator";

const SUPPORT_EMAIL = "support@localhost";

const EMAIL_RE = "/^(([\p{L&}\p{Nd}\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*[\p{L&}\p{Nd}\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+@((((([\p{L&}\p{Nd}]{1}[\p{L&}\p{Nd}\-]{0,62}[\p{L&}\p{Nd}]{1})|[\p{L&}])\.)+[\p{L&}]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?))+$/iu";

define("SALT", "k3r90jasd");
define("PDO_HOST", "localhost");
define("PDO_USERNAME", "root");
define("PDO_PASSWORD", "letmeinlocalpassword");
define("PDO_DATABASE", "story");

function randString($length = 128) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
	    $randomString .= $characters[rand(0, strlen($characters) - 1)];
	}
	return $randomString;
	//return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function curlRequest($url, $method = "GET", $parameters = array(), $headers = array(), $ret_headers = false, $user_agent = HTTP_USER_AGENT) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, $ret_headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 2);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	if (count($headers) > 0)
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	if ($user_agent)
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

	switch ($method) {
	    case "POST":
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		break;
	    case "HEAD":
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		break;
	    case "PUT":
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		break;
	    case "DELETE":
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		break;
	    case "PUTFILE":
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_INFILE, fopen($parameters, "r"));
		curl_setopt($ch, CURLOPT_INFILESIZE, filesize($parameters));
		break;
	    case "GET":
	    default:
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		$array = array();
		if (is_array($parameters) && count($parameters) > 0) {
			foreach ($parameters as $field=>$value) {
				$array[] = urlencode($field)."=".urlencode($value);
			}
			if (count($array) > 0) {
				$url .= (preg_match("/\?/", $url) ? "&" : "?").join("&", $array);
			}
		}
		break;
	}

	curl_setopt($ch, CURLOPT_URL, $url);
	$response = curl_exec($ch);
	curl_close($ch);

	return $response;
}

require_once("includes/PHPMailer/PHPMailerAutoload.php");
require_once("includes/classes/cError.php");  $cerror = new \Error\cError("logs/errors.txt");
require_once("includes/classes/cPDO.php");    $cpdo = new \Data\cPDO("logs/pdo.txt");
<?php // lint >= 8.1

namespace CurlSetOptArray;

enum RequestMethod
{
	case GET;
	case POST;
}

enum BackedRequestMethod: string
{
	case POST = 'POST';
	case GET = 'GET';
}

function allOptionsFine() {
	$curl = curl_init();
	curl_setopt_array($curl, [
		\CURLOPT_RETURNTRANSFER => true,
		\CURLOPT_ENCODING => '',
		\CURLOPT_MAXREDIRS => 10,
		\CURLOPT_TIMEOUT => 30,
		\CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_1_1,
	]);
}

function doFoo() {
	$curl = curl_init();
	curl_setopt_array($curl, [
		\CURLOPT_RETURNTRANSFER => true,
		\CURLOPT_ENCODING => '',
		\CURLOPT_MAXREDIRS => 10,
		\CURLOPT_TIMEOUT => 30,
		\CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_1_1,
		\CURLOPT_CUSTOMREQUEST => RequestMethod::POST, // invalid
	]);
}

function doFoo2() {
	$curl = curl_init();
	curl_setopt_array($curl, [
		\CURLOPT_RETURNTRANSFER => true,
		\CURLOPT_ENCODING => '',
		\CURLOPT_MAXREDIRS => 10,
		\CURLOPT_TIMEOUT => 30,
		\CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_1_1,
		\CURLOPT_CUSTOMREQUEST => BackedRequestMethod::POST,
	]);
}

function doFooBar() {
	$curl = curl_init();
	curl_setopt_array($curl, [
		\CURLOPT_RETURNTRANSFER => '123', // invalid
		\CURLOPT_ENCODING => '',
		\CURLOPT_MAXREDIRS => 10,
		\CURLOPT_TIMEOUT => 30,
		\CURLOPT_HTTP_VERSION => false, // invalid
	]);
}

function doBar($options) {
	$curl = curl_init();
	curl_setopt_array($curl, $options);
}


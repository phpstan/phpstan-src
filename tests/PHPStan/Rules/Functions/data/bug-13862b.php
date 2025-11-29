<?php

namespace Bug13862b;

$options = [];

if (rand()) {
	$options[CURLOPT_COOKIE] = 'foo=bar';
}

$ch = curl_init();
curl_setopt_array($ch, $options);

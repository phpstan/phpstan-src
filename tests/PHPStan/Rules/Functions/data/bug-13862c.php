<?php

namespace Bug13862c;

$options = [];

if (rand()) {
	$options[CURLOPT_COOKIE] = 123;
}

$ch = curl_init();
curl_setopt_array($ch, $options);

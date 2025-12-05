<?php

namespace CurlSetOptShare;

function curlShare()
{
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_SHARE, 'this is wrong');
}

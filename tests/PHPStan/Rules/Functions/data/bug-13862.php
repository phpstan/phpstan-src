<?php

namespace Bug13862;

class foo
{

	/**
	 * @param string $url
	 * @param string $curl_interface
	 * @param ?int $port
	 * @param ?string $post
	 * @return string
	 */
	public function getUrl(
		string  $url,
		string  $curl_interface = "",
		?int    $port = null,
		?string $post = null
	): string
	{

		if (empty($url))
			return "";

		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 15,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_PORT => 443,
			CURLOPT_ENCODING => "gzip, deflate",
			CURLOPT_HTTPHEADER => array("Accept-Encoding: gzip, deflate")
		);

		if ($curl_interface != "")
			$options[CURLOPT_INTERFACE] = $curl_interface;

		if ($port !== null)
			$options[CURLOPT_PORT] = $port;

		if ($post !== null) {
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $post;
			$options[CURLOPT_HTTPHEADER] = array(
				"Content-Type: application/json;charset=\"UTF-8\"",
				"Accept: application/json",
				"Accept-Encoding: gzip, deflate",
				"Content-Length: " . mb_strlen($post, "UTF-8")
			);
		}

		$curl = curl_init();
		curl_setopt_array($curl, $options);
		$rc = curl_exec($curl);
		if (is_bool($rc))
			return "";
		else
			return $rc;

	}

}

$foo = new foo();
$data = $foo->getUrl("https://example.tld");

<?php declare(strict_types = 1);

class HelloWorld
{
	public function bug7951(bool $verify): void
	{
		$ch = curl_init();
		curl_setopt($ch, \CURLOPT_SSL_VERIFYHOST, $verify); // should error, as only 0 and 2 are supported values
	}

	public function errors(int $i, string $s) {
		$curl = curl_init();
		// expecting string
		curl_setopt($curl, CURLOPT_URL, $i);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $i);
		// expecting bool
		curl_setopt($curl, CURLOPT_AUTOREFERER, $i);
		// expecting int
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, $s);
		curl_setopt($curl, CURLOPT_TIMEOUT, $s);
		// expecting array
		curl_setopt($curl, CURLOPT_CONNECT_TO, $s);
		// expecting resource
		curl_setopt($curl, CURLOPT_FILE, $s);
	}

	/**
	 * @param non-empty-string $url
	 */
	public function allGood(string $url, array $header) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
		curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);

		$fp = fopen("example_homepage.txt", "w");
		if ($fp === false) {
			throw new \Exception("Could not open file");
		}
		curl_setopt($curl, CURLOPT_FILE, $fp);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: text/plain', 'Content-length: 100'));
	}
}

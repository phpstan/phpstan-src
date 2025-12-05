<?php declare(strict_types = 1);

namespace CurlSetOptCall;

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
		curl_setopt($curl, CURLOPT_ABSTRACT_UNIX_SOCKET, null);
		curl_setopt($curl, CURLOPT_ENCODING, $i);
		curl_setopt($curl, CURLOPT_ACCEPT_ENCODING, $i);
		// expecting bool
		curl_setopt($curl, CURLOPT_AUTOREFERER, $i);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, $s);
		// expecting int
		curl_setopt($curl, CURLOPT_TIMEOUT, $s);
		// expecting array
		curl_setopt($curl, CURLOPT_CONNECT_TO, $s);
		// expecting resource
		curl_setopt($curl, CURLOPT_FILE, $s);
		// expecting string or array
		curl_setopt($curl, CURLOPT_POSTFIELDS, $i);
		// expecting non empty string
		curl_setopt($curl, CURLOPT_URL, '');
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, '');
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
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, null);

		$fp = fopen("example_homepage.txt", "w");
		if ($fp === false) {
			throw new \Exception("Could not open file");
		}
		curl_setopt($curl, CURLOPT_FILE, $fp);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: text/plain', 'Content-length: 100'));
		curl_setopt($curl, CURLOPT_POSTFIELDS, array('foo' => 'bar'));
		curl_setopt($curl, CURLOPT_POSTFIELDS, '');
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'para1=val1&para2=val2');
		curl_setopt($curl, CURLOPT_COOKIEFILE, '');
		curl_setopt($curl, CURLOPT_PRE_PROXY, '');
		curl_setopt($curl, CURLOPT_PROXY, '');
		curl_setopt($curl, CURLOPT_PRIVATE, '');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_ACCEPT_ENCODING, '');
	}

	public function bug9263() {
		$curl = curl_init();

		$header_dictionary = [
			'Accept' => 'application/json',
		];
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header_dictionary);

		$header_list = [
			'Accept: application/json',
		];
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header_list);
	}

	public function unionType() {
		$curl = curl_init();

		if (rand(0,1)) {
			$var = CURLOPT_AUTOREFERER;
			$value = 'yes'; // invalid, should be bool
		} else {
			$var = CURLOPT_TIMEOUT;
			$value = 1;
		}

		curl_setopt($curl, $var, $value);
	}

	public function curlShare() {
		$curl = curl_init();

		$share = curl_share_init();
		curl_share_setopt($share, CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
		curl_share_setopt($share, CURLSHOPT_SHARE, CURL_LOCK_DATA_CONNECT);
		curl_share_setopt($share, CURLSHOPT_SHARE, CURL_LOCK_DATA_SSL_SESSION);
		curl_setopt($curl, CURLOPT_SHARE, $share);

		if (function_exists('curl_share_init_persistent')) {
			$share = curl_share_init_persistent([
				CURL_LOCK_DATA_DNS,
				CURL_LOCK_DATA_CONNECT,
				CURL_LOCK_DATA_SSL_SESSION,
			]);
			curl_setopt($curl, CURLOPT_SHARE, $share);
		}
	}
}

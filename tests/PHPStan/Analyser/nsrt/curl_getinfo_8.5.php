<?php // lint >= 8.5

namespace CurlGetinfo85;

use CurlHandle;
use function PHPStan\Testing\assertType;

class Foo
{
	public function bar()
	{
		$handle = new CurlHandle();
		assertType('int|false', curl_getinfo($handle, CURLINFO_QUEUE_TIME_T));
		assertType('int|false', curl_getinfo($handle, CURLINFO_USED_PROXY));
		assertType('int|false', curl_getinfo($handle, CURLINFO_HTTPAUTH_USED));
		assertType('int|false', curl_getinfo($handle, CURLINFO_PROXYAUTH_USED));
		assertType('int|false', curl_getinfo($handle, CURLINFO_CONN_ID));
	}
}

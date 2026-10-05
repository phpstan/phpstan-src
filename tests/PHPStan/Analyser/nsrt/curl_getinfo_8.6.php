<?php // lint >= 8.6

namespace CurlGetinfo86;

use CurlHandle;
use function PHPStan\Testing\assertType;

class Foo
{
	public function bar()
	{
		$handle = new CurlHandle();
		assertType('int|false', curl_getinfo($handle, CURLINFO_SIZE_DELIVERED));
	}
}

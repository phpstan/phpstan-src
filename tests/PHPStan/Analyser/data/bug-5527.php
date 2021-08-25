<?php

namespace Bug5527;

use function PHPStan\Testing\assertType;

class Base64
{
	const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

	public function decode(string $buf): void
	{
		$chars = array_flip(str_split(self::chars));
		$res = $chars[$buf[0]] << 18;
		$res += $chars[$buf[1]] << 12;
		$res += $chars[$buf[2]] << 6;
		$res += $chars[$buf[3]];
		assertType('int', $res);
	}
}

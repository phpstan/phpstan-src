<?php declare(strict_types = 1);

namespace Bug6939;

class HelloWorld
{
	public static function read(int $pos, int $bytes): string
	{
		$data = substr('', $pos, $bytes);
		return $data === false ? '' : $data;
	}
}

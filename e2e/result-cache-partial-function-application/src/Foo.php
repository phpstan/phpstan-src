<?php

namespace ResultCacheE2EPartialFunctionApplication;

class Foo
{

	public function m(string $a, string $b): string
	{
		return $a . $b;
	}

	public static function s(string $a, string $b): string
	{
		return $a . $b;
	}

}

function f(string $a, string $b): string
{
	return $a . $b;
}

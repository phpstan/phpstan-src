<?php

namespace ResultCacheE2EFirstClassCallable;

class Dep
{

	public function m(): string
	{
		return 'a';
	}

	public static function s(): string
	{
		return 'a';
	}

}

function f(): string
{
	return 'a';
}

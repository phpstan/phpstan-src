<?php

namespace ArrayDestructuring;

class Foo
{

	public function doFoo(?array $arrayOrNull): void
	{
		[$a] = [0, 1, 2];
		[$a] = $arrayOrNull;
		[$a] = [];
		[[$a]] = [new \stdClass()];

		[[$a, $b, $c]] = [[1, 2]];
	}

	public function doBar(): void
	{
		['a' => $a] = ['a' => 1];

		['a' => $a] = ['b' => 1];
	}

}

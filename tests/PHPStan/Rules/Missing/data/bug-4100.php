<?php declare(strict_types = 1);

namespace Bug4100;

use Generator;

function foo(): Generator
{
	while (false) {
		return;
		yield 2;
	}
}

function withThrow(): Generator
{
	if (rand(0, 1)) {
		throw new \Exception();
		yield 1;
	}
}

class Foo
{

	public function method(): Generator
	{
		while (false) {
			return;
			yield 2;
		}
	}

}

function closures(): void
{
	$c = function (): Generator {
		while (false) {
			return;
			yield 2;
		}
	};
	$c;
}

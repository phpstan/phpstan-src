<?php declare(strict_types = 1);

namespace Bug5401;

function test1(bool $foo, bool $bar, bool $baz): void
{
	if ($foo) {
		$qux = $baz;
	}

	if ($foo && $bar) {
		// ...
	}

	if ($foo && $qux) {
		// ...
	}
}

function test2(bool $foo, bool $bar, bool $baz): void
{
	if ($foo) {
		$qux = $baz;
	}

	if ($foo && $bar) {
		// ...
	}

	if ($foo) {
		echo $qux;
	}
}

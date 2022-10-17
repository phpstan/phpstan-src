<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug3311b;

final class Foo
{
	/**
	 * @var array<int, string>
	 * @psalm-var list<string>
	 */
	public array $bar = [];
}

function () {
	$instance = new Foo;
	$instance->bar[1] = 'baz';
};

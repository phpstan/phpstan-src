<?php // lint >= 8.0

namespace InstantiationPromotedProperties;

class Foo
{

	public function __construct(
		private array $foo,
		/** @var array<string> */private array $bar
	) { }

}

class Bar
{

	/**
	 * @param array<string> $bar
	 */
	public function __construct(
		private array $foo,
		private array $bar
	) { }

}

function () {
	new Foo([], ['foo']);
	new Foo([], [1]);

	new Bar([], ['foo']);
	new Bar([], [1]);
};

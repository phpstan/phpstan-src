<?php

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

class PromotedPropertyNotNullable
{

	public function __construct(
		private int $intProp = null,
	) {}

}

function () {
	new PromotedPropertyNotNullable(null);
};

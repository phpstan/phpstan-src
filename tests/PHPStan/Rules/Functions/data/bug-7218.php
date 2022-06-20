<?php declare(strict_types=1);

namespace Bug7218;

/** @template T */
class Foo {}

/** @return Foo<string> */
function getFoo(): Foo
{
	/** @var Foo<string> */
	return new Foo();
}

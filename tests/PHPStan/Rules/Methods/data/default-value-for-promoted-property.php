<?php // lint >= 8.0

namespace DefaultValueForPromotedProperty;

class Foo
{

	public function __construct(
		private int $foo = 'foo',
		/** @var int */ private $foo = '',
		private int $baz = 1,
		private int $intProp = null,
	) {}

}

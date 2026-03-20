<?php // lint >= 8.0

namespace Bug14250PromotedProperties;

trait TraitWithDuplicatePromotedProperties
{
	private $foo;

	public function __construct(
		private $foo,
		private $bar,
		private $bar
	)
	{

	}
}

class Foo
{
	use TraitWithDuplicatePromotedProperties;
}

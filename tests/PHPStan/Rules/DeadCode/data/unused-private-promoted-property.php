<?php // lint >= 8.0

namespace UnusedPrivatePromotedProperty;

class Foo
{

	public function __construct(
		public $foo,
		protected $bar,
		private $baz,
		private $lorem
	) { }

	public function getBaz()
	{
		return $this->baz;
	}

}

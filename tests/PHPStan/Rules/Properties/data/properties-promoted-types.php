<?php

namespace PromotedPropertiesExistingClasses;

class Foo
{

	public function __construct(
		public \stdClass $foo,
		/** @var \stdClass */ public $bar,
		public SomeTrait $baz,
		/** @var SomeTrait */ public $lorem,
		public Bar $ipsum,
		/** @var Bar */ public $dolor
	) { }

}

trait SomeTrait
{

}

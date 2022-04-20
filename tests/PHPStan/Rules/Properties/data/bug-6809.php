<?php declare(strict_types = 1);

namespace Bug6809;

trait SomeTrait {
	public function __construct() {
		$isClassCool = static::$coolClass ?? true;
	}
}

class HelloWorld
{
	use SomeTrait;
}

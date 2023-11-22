<?php // lint >= 8.0

namespace Bug9654;
// https://github.com/phpstan/phpstan/issues/9654

trait Abc
{
	abstract public function __construct();

	public static function create(): static
	{
		return new static();  // this is safe as the constructor is defined abstract in this trait
	}
}

class Foo
{
	use Abc;

	public function __construct() {

	}
}

class Bar
{
	use Abc;

	public function __construct(int $i = 0)
	{
		$i = $i*2;
	}
}

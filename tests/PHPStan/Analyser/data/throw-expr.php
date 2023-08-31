<?php // lint >= 8.0

namespace ThrowExpr;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(bool $b): void
	{
		$result = $b ? true : throw new \Exception();
		assertType('true', $result);
	}

	public function doBar(): void
	{
		assertType('never', throw new \Exception());
	}

}

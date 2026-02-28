<?php declare(strict_types = 1);

namespace Bug12687;

use function PHPStan\Testing\assertType;

class A
{
}

interface I
{
	function method(): void;
}

class Testing
{
	/**
	 * @param A|null $arg
	 * @param-out ($arg is null ? A&I : A) $arg
	 */
	public static function testMethod(?A &$arg = null): void
	{
		if ($arg === null) {
			$arg = new A();
		}
	}

	public function doTest(): void
	{
		$a = new A();
		Testing::testMethod($a);
		assertType('Bug12687\A', $a);

		$b = null;
		Testing::testMethod($b);
		assertType('Bug12687\A&Bug12687\I', $b);

		Testing::testMethod($c);
		assertType('Bug12687\A&Bug12687\I', $c);
	}
}

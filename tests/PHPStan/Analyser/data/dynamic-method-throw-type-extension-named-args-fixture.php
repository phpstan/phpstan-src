<?php // onlyif PHP_VERSION_ID >= 80000

namespace DynamicMethodThrowTypeExtensionNamedArgs;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	/** @throws \Exception */
	public function throwOrNot(bool $need): int
	{
		if ($need) {
			throw new \Exception();
		}

		return 1;
	}

	/** @throws \Exception */
	public static function staticThrowOrNot(bool $need): int
	{
		if ($need) {
			throw new \Exception();
		}

		return 1;
	}

	public function doFoo1()
	{
		try {
			$result = $this->throwOrNot(need: true);
		} finally {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
		}
	}

	public function doFoo2()
	{
		try {
			$result = $this->throwOrNot(need: false);
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $result);
		}
	}

	public function doFoo3()
	{
		try {
			$result = self::staticThrowOrNot(need: true);
		} finally {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
		}
	}

	public function doFoo4()
	{
		try {
			$result = self::staticThrowOrNot(need: false);
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $result);
		}
	}

}


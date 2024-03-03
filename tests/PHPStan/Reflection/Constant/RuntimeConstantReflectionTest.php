<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Constant;

use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;

class RuntimeConstantReflectionTest extends PHPStanTestCase
{

	public function dataDeprecatedConstants(): array
	{
		return [
			[
				new Name('\DeprecatedConst\FINE'),
				TrinaryLogic::createNo(),
				null,
			],
			[
				new Name('\DeprecatedConst\MY_CONST'),
				TrinaryLogic::createYes(),
				null,
			],
			[
				new Name('\DeprecatedConst\MY_CONST2'),
				TrinaryLogic::createYes(),
				"don't use it!",
			],
		];
	}

	/**
	 * @dataProvider dataDeprecatedConstants
	 */
	public function testDeprecatedConstants(Name $constName, TrinaryLogic $isDeprecated, ?string $deprecationMessage): void
	{
		require_once __DIR__ . '/data/deprecated-constant.php';

		$reflectionProvider = $this->createReflectionProvider();

		$this->assertTrue($reflectionProvider->hasConstant($constName, null));
		$this->assertSame($isDeprecated, $reflectionProvider->getConstant($constName, null)->isDeprecated());
		$this->assertSame($deprecationMessage, $reflectionProvider->getConstant($constName, null)->getDeprecatedDescription());
	}

}

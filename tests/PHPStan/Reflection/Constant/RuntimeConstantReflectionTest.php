<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Constant;

use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use const PHP_VERSION_ID;

class RuntimeConstantReflectionTest extends PHPStanTestCase
{

	public function dataDeprecatedConstants(): iterable
	{
		yield [
			new Name('\FILTER_SANITIZE_STRING'),
			PHP_VERSION_ID >= 80100 ? TrinaryLogic::createYes() : TrinaryLogic::createNo(),
			null,
		];

		yield [
			new Name('\CURLOPT_FTP_SSL'),
			TrinaryLogic::createYes(),
			'use <b>CURLOPT_USE_SSL</b> instead.',
		];

		yield [
			new Name('\DeprecatedConst\FINE'),
			TrinaryLogic::createNo(),
			null,
		];
		yield [
			new Name('\DeprecatedConst\MY_CONST'),
			TrinaryLogic::createYes(),
			null,
		];
		yield [
			new Name('\DeprecatedConst\MY_CONST2'),
			TrinaryLogic::createYes(),
			"don't use it!",
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
		$this->assertSame($isDeprecated->describe(), $reflectionProvider->getConstant($constName, null)->isDeprecated()->describe());
		$this->assertSame($deprecationMessage, $reflectionProvider->getConstant($constName, null)->getDeprecatedDescription());
	}

}

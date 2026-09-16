<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Constant;

use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPUnit\Framework\Attributes\DataProvider;
use const PHP_VERSION_ID;

class RuntimeConstantReflectionTest extends PHPStanTestCase
{

	public static function dataDeprecatedConstants(): iterable
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
			new Name('\MB_ONIGURUMA_VERSION'),
			PHP_VERSION_ID >= 80600 ? TrinaryLogic::createYes() : TrinaryLogic::createNo(),
			null,
		];

		yield [
			new Name('\SORT_LOCALE_STRING'),
			PHP_VERSION_ID >= 80600 ? TrinaryLogic::createYes() : TrinaryLogic::createNo(),
			null,
		];

		yield [
			new Name('\FILTER_DEFAULT'),
			PHP_VERSION_ID >= 80500 ? TrinaryLogic::createYes() : TrinaryLogic::createNo(),
			PHP_VERSION_ID >= 80500 ? 'use FILTER_UNSAFE_RAW instead' : null,
		];

		yield [
			new Name('\INTL_IDNA_VARIANT_2003'),
			TrinaryLogic::createYes(),
			'Use {@see INTL_IDNA_VARIANT_UTS46} instead.',
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

	#[DataProvider('dataDeprecatedConstants')]
	public function testDeprecatedConstants(Name $constName, TrinaryLogic $isDeprecated, ?string $deprecationMessage): void
	{
		require_once __DIR__ . '/data/deprecated-constant.php';

		$reflectionProvider = self::createReflectionProvider();

		$this->assertTrue($reflectionProvider->hasConstant($constName, null));
		$this->assertSame($isDeprecated->describe(), $reflectionProvider->getConstant($constName, null)->isDeprecated()->describe());
		$this->assertSame($deprecationMessage, $reflectionProvider->getConstant($constName, null)->getDeprecatedDescription());
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Constant;

use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;

class Php86ConstantDeprecationTest extends PHPStanTestCase
{

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/data/php-8.6.neon'];
	}

	public static function dataDeprecatedConstants(): iterable
	{
		yield [
			new Name('\MB_ONIGURUMA_VERSION'),
			TrinaryLogic::createYes(),
			null,
		];

		yield [
			new Name('\SORT_LOCALE_STRING'),
			TrinaryLogic::createYes(),
			null,
		];

		yield [
			new Name('\FILTER_DEFAULT'),
			TrinaryLogic::createYes(),
			'use FILTER_UNSAFE_RAW instead',
		];

		yield [
			new Name('\SORT_STRING'),
			TrinaryLogic::createNo(),
			null,
		];
	}

	#[DataProvider('dataDeprecatedConstants')]
	public function testDeprecatedConstants(Name $constName, TrinaryLogic $isDeprecated, ?string $deprecationMessage): void
	{
		$reflectionProvider = self::createReflectionProvider();

		$this->assertTrue($reflectionProvider->hasConstant($constName, null));
		$this->assertSame($isDeprecated->describe(), $reflectionProvider->getConstant($constName, null)->isDeprecated()->describe());
		$this->assertSame($deprecationMessage, $reflectionProvider->getConstant($constName, null)->getDeprecatedDescription());
	}

	public function testStubbedConstantKeepsItsRealValueType(): void
	{
		$reflectionProvider = self::createReflectionProvider();

		$this->assertTrue($reflectionProvider->getConstant(new Name('\MB_ONIGURUMA_VERSION'), null)->getValueType()->isNonEmptyString()->yes());
		$this->assertSame('5', $reflectionProvider->getConstant(new Name('\SORT_LOCALE_STRING'), null)->getValueType()->describe(VerbosityLevel::precise()));
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/** @extends RuleTestCase<OverridingConstantRule> */
class OverridingConstantRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new OverridingConstantRule(true);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/overriding-constant.php'], [
			[
				'Type string of constant OverridingConstant\Bar::BAR is not covariant with type int of constant OverridingConstant\Foo::BAR.',
				30,
			],
			[
				'Type int|string of constant OverridingConstant\Bar::IPSUM is not covariant with type int of constant OverridingConstant\Foo::IPSUM.',
				39,
			],
		]);
	}

	public function testFinal(): void
	{
		$errors = [
			[
				'Constant OverridingFinalConstant\Bar::FOO overrides final constant OverridingFinalConstant\Foo::FOO.',
				18,
			],
			[
				'Constant OverridingFinalConstant\Bar::BAR overrides final constant OverridingFinalConstant\Foo::BAR.',
				19,
			],
		];

		if (PHP_VERSION_ID < 80100) {
			$errors[] = [
				'Constant OverridingFinalConstant\Baz::FOO overrides final constant OverridingFinalConstant\FooInterface::FOO.',
				34,
			];
		}

		$errors[] = [
			'Constant OverridingFinalConstant\Baz::BAR overrides final constant OverridingFinalConstant\FooInterface::BAR.',
			35,
		];

		if (PHP_VERSION_ID < 80100) {
			$errors[] = [
				'Constant OverridingFinalConstant\Lorem::FOO overrides final constant OverridingFinalConstant\BarInterface::FOO.',
				51,
			];
		}

		$errors[] = [
			'Type string of constant OverridingFinalConstant\Lorem::FOO is not covariant with type int of constant OverridingFinalConstant\BarInterface::FOO.',
			51,
		];

		$errors[] = [
			'Private constant OverridingFinalConstant\PrivateDolor::PROTECTED_CONST overriding protected constant OverridingFinalConstant\Dolor::PROTECTED_CONST should be protected or public.',
			69,
		];
		$errors[] = [
			'Private constant OverridingFinalConstant\PrivateDolor::PUBLIC_CONST overriding public constant OverridingFinalConstant\Dolor::PUBLIC_CONST should also be public.',
			70,
		];
		$errors[] = [
			'Private constant OverridingFinalConstant\PrivateDolor::ANOTHER_PUBLIC_CONST overriding public constant OverridingFinalConstant\Dolor::ANOTHER_PUBLIC_CONST should also be public.',
			71,
		];
		$errors[] = [
			'Protected constant OverridingFinalConstant\ProtectedDolor::PUBLIC_CONST overriding public constant OverridingFinalConstant\Dolor::PUBLIC_CONST should also be public.',
			80,
		];
		$errors[] = [
			'Protected constant OverridingFinalConstant\ProtectedDolor::ANOTHER_PUBLIC_CONST overriding public constant OverridingFinalConstant\Dolor::ANOTHER_PUBLIC_CONST should also be public.',
			81,
		];

		$this->analyse([__DIR__ . '/data/overriding-final-constant.php'], $errors);
	}

	public function testNativeTypes(): void
	{
		if (PHP_VERSION_ID < 80300) {
			$this->markTestSkipped('Test requires PHP 8.3.');
		}

		$this->analyse([__DIR__ . '/data/overriding-constant-native-types.php'], [
			[
				'Native type int|string of constant OverridingConstantNativeTypes\Bar::D is not covariant with native type int of constant OverridingConstantNativeTypes\Foo::D.',
				21,
			],
			[
				'Constant OverridingConstantNativeTypes\Ipsum::B overriding constant OverridingConstantNativeTypes\Lorem::B (int) should also have native type int.',
				37,
			],
			[
				'Constant OverridingConstantNativeTypes\PharChild::BZ2 overriding constant Phar::BZ2 (int) should also have native type int.',
				44,
			],
			[
				'Native type int|string of constant OverridingConstantNativeTypes\PharChild::NONE is not covariant with native type int of constant Phar::NONE.',
				48,
			],
		]);
	}

}

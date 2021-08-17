<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Php\PhpVersion;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<OverridingConstantRule> */
class OverridingConstantRuleTest extends RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new OverridingConstantRule(new PhpVersion(PHP_VERSION_ID), true);
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
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

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

		$this->analyse([__DIR__ . '/data/overriding-final-constant.php'], $errors);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/** @extends RuleTestCase<OverridingConstantRule> */
class OverridingConstantRuleTest extends RuleTestCase
{

	private ?bool $checkMissingOverrideConstantAttribute = false;

	protected function getRule(): Rule
	{
		return new OverridingConstantRule(
			true,
			new OverrideAttributeOnConstantCheck(
				$this->checkMissingOverrideConstantAttribute,
				checkMissingOverrideMethodAttribute: true,
			),
		);
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

	#[RequiresPhp('>= 8.3.0')]
	public function testNativeTypes(): void
	{
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

	#[RequiresPhp('>= 8.2.0')]
	public function testOverrideAttribute(): void
	{
		$this->checkMissingOverrideConstantAttribute = true;
		$this->analyse([__DIR__ . '/data/constant-override-attr.php'], [
			[
				'Constant ConstantOverrideAttr\\Bar::PRIVATE_FROM_PARENT has #[\\Override] attribute but does not override any constant.',
				28,
			],
			[
				'Constant ConstantOverrideAttr\\Bar::NOT_OVERRIDING has #[\\Override] attribute but does not override any constant.',
				31,
			],
			[
				'Constant ConstantOverrideAttr\\Baz::FROM_PARENT overrides constant ConstantOverrideAttr\\Foo::FROM_PARENT but is missing the #[\\Override] attribute.',
				39,
			],
			[
				'Constant ConstantOverrideAttr\\Baz::ALSO_NOT_OVERRIDING has #[\\Override] attribute but does not override any constant.',
				41,
			],
			[
				'Constant ConstantOverrideAttr\\BarInterface::NOT_OVERRIDING has #[\\Override] attribute but does not override any constant.',
				50,
			],
			[
				'Constant ConstantOverrideAttr\\UsesTraitWithoutParent::FROM_PARENT has #[\\Override] attribute but does not override any constant.',
				56,
			],
		]);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testMissingOverrideAttributeNotCheckedByDefaultBeforePhp86(): void
	{
		$this->checkMissingOverrideConstantAttribute = null;
		$errors = [];
		if (PHP_VERSION_ID >= 80600) {
			$errors[] = [
				'Constant ConstantOverrideAttr\\Baz::FROM_PARENT overrides constant ConstantOverrideAttr\\Foo::FROM_PARENT but is missing the #[\\Override] attribute.',
				39,
			];
		}

		$this->analyse([__DIR__ . '/data/constant-override-attr.php'], [
			[
				'Constant ConstantOverrideAttr\\Bar::PRIVATE_FROM_PARENT has #[\\Override] attribute but does not override any constant.',
				28,
			],
			[
				'Constant ConstantOverrideAttr\\Bar::NOT_OVERRIDING has #[\\Override] attribute but does not override any constant.',
				31,
			],
			...$errors,
			[
				'Constant ConstantOverrideAttr\\Baz::ALSO_NOT_OVERRIDING has #[\\Override] attribute but does not override any constant.',
				41,
			],
			[
				'Constant ConstantOverrideAttr\\BarInterface::NOT_OVERRIDING has #[\\Override] attribute but does not override any constant.',
				50,
			],
			[
				'Constant ConstantOverrideAttr\\UsesTraitWithoutParent::FROM_PARENT has #[\\Override] attribute but does not override any constant.',
				56,
			],
		]);
	}

	public function testFixOverrideAttribute(): void
	{
		$this->checkMissingOverrideConstantAttribute = true;
		$this->fix(__DIR__ . '/data/constant-override-attr-fix.php', __DIR__ . '/data/constant-override-attr-fix.php.fixed');
	}

}

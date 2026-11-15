<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_merge;
use function usort;
use const PHP_VERSION_ID;

/** @extends RuleTestCase<OverridingConstantRule> */
class OverridingConstantRuleTest extends RuleTestCase
{

	private ?bool $checkMissingOverrideConstantAttribute = null;

	private ?bool $checkMissingOverridePropertyAttribute = null;

	private bool $checkMissingOverrideMethodAttribute = false;

	private int $phpVersionId = PHP_VERSION_ID;

	protected function getRule(): Rule
	{
		return new OverridingConstantRule(
			true,
			new OverrideAttributeOnClassConstantCheck(
				new PhpVersion($this->phpVersionId),
				$this->checkMissingOverrideConstantAttribute,
				$this->checkMissingOverridePropertyAttribute,
				$this->checkMissingOverrideMethodAttribute,
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

	public static function dataOverrideAttribute(): iterable
	{
		$missingErrors = [
			[
				'Constant ConstantOverrideAttr\PP::C2 overrides constant ConstantOverrideAttr\P::C2 but is missing the #[\Override] attribute.',
				36,
			],
			[
				'Constant ConstantOverrideAttr\C::C3 overrides constant ConstantOverrideAttr\PP::C3 but is missing the #[\Override] attribute.',
				49,
			],
		];

		// checkMissingOverrideConstantAttribute, checkMissingOverridePropertyAttribute, checkMissingOverrideMethodAttribute, PHP version
		yield [null, null, false, 80500, []];
		yield [null, null, false, 80600, []];
		yield [null, null, true, 80500, []];
		yield [null, null, true, 80600, $missingErrors];
		yield [null, true, false, 80500, []];
		yield [null, true, false, 80600, $missingErrors];
		yield [null, false, true, 80600, $missingErrors];
		yield [null, false, false, 80600, []];
		yield [true, null, false, 80500, $missingErrors];
		yield [true, null, false, 80600, $missingErrors];
		yield [true, false, false, 80600, $missingErrors];
		yield [false, null, true, 80600, []];
		yield [false, true, true, 80600, []];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string|null}> $expectedErrors
	 */
	#[RequiresPhp('>= 8.2.0')]
	#[DataProvider('dataOverrideAttribute')]
	public function testOverrideAttribute(
		?bool $checkMissingOverrideConstantAttribute,
		?bool $checkMissingOverridePropertyAttribute,
		bool $checkMissingOverrideMethodAttribute,
		int $phpVersionId,
		array $expectedErrors,
	): void
	{
		$this->checkMissingOverrideConstantAttribute = $checkMissingOverrideConstantAttribute;
		$this->checkMissingOverridePropertyAttribute = $checkMissingOverridePropertyAttribute;
		$this->checkMissingOverrideMethodAttribute = $checkMissingOverrideMethodAttribute;
		$this->phpVersionId = $phpVersionId;

		$errors = array_merge([
			[
				'Constant ConstantOverrideAttr\Unrelated::U has #[\Override] attribute but does not override any constant.',
				18,
			],
			[
				'Constant ConstantOverrideAttr\C::PRIVATE has #[\Override] attribute but does not override any constant.',
				55,
			],
			[
				'Constant ConstantOverrideAttr\C::NOTHING has #[\Override] attribute but does not override any constant.',
				57,
			],
			[
				'Constant ConstantOverrideAttr\C::C5 has #[\Override] attribute but does not override any constant.',
				59,
			],
			[
				'Constant ConstantOverrideAttr\C::C6 has #[\Override] attribute but does not override any constant.',
				59,
			],
			[
				'Constant ConstantOverrideAttr\E::C has #[\Override] attribute but does not override any constant.',
				67,
			],
			[
				'Constant ConstantOverrideAttr\UsesTraitWithoutParent::C1 has #[\Override] attribute but does not override any constant.',
				73,
			],
			[
				'Constant ConstantOverrideAttr\RedeclaresTraitConstant::T has #[\Override] attribute but does not override any constant.',
				106,
			],
			[
				'Constant class@anonymous/tests/PHPStan/Rules/Constants/data/constant-override-attr.php:115::I has #[\Override] attribute but does not override any constant.',
				116,
			],
		], $expectedErrors);
		usort($errors, static fn (array $a, array $b): int => $a[1] <=> $b[1]);

		$this->analyse([__DIR__ . '/data/constant-override-attr.php'], $errors);
	}

	public function testFixOverrideAttribute(): void
	{
		$this->checkMissingOverrideConstantAttribute = true;
		$this->fix(__DIR__ . '/data/constant-override-attr-fix.php', __DIR__ . '/data/constant-override-attr-fix.php.fixed');
	}

}

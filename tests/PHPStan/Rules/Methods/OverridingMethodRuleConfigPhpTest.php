<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<OverridingMethodRule>
 */
class OverridingMethodRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$phpVersion = self::getContainer()->getByType(PhpVersion::class);
		$phpClassReflectionExtension = self::getContainer()->getByType(PhpClassReflectionExtension::class);

		return new OverridingMethodRule(
			$phpVersion,
			new MethodSignatureRule(new ParentMethodHelper($phpClassReflectionExtension), true, true, false),
			false,
			new MethodParameterComparisonHelper($phpVersion),
			new MethodVisibilityComparisonHelper(),
			new MethodPrototypeFinder($phpVersion, $phpClassReflectionExtension),
			false,
			true,
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testTentativeReturnTypesInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/tentative-return-types.php'], TentativeReturnTypesErrors::get());
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testAbstractTraitMethodsInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/overriding-trait-methods.php'], [
			[
				'Parameter #1 $i (int) of method OverridingTraitMethods\Bar::doBar() is not contravariant with parameter #1 $i (string) of method OverridingTraitMethods\Foo::doBar().',
				27,
			],
			[
				'Parameter #1 $i (int) of method OverridingTraitMethods\Baz::doBar() is not contravariant with parameter #1 $i (string) of method OverridingTraitMethods\FooPrivate::doBar().',
				45,
			],
			[
				'Static method OverridingTraitMethods\Ipsum::doBar() overrides non-static method OverridingTraitMethods\Foo::doBar().',
				65,
			],
			[
				'Non-static method OverridingTraitMethods\Dolor::doBar() overrides static method OverridingTraitMethods\FooStatic::doBar().',
				80,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range.neon',
		];
	}

}

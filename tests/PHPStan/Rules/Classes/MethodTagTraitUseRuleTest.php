<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MethodTagTraitUseRule>
 */
class MethodTagTraitUseRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		$reflectionProvider = $this->createReflectionProvider();

		return new MethodTagTraitUseRule(
			new MethodTagCheck(
				$reflectionProvider,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck(self::getContainer()),
				),
				new GenericObjectTypeCheck(),
				new MissingTypehintCheck(true, []),
				new UnresolvableTypeHelper(),
				true,
				true,
				true,
			),
		);
	}

	public function testRule(): void
	{
		$fooTraitLine = 12;
		$this->analyse([__DIR__ . '/data/method-tag-trait.php'], [
			[
				'PHPDoc tag @method for method MethodTagTrait\Foo::doFoo() return type contains unknown class MethodTagTrait\intt.',
				$fooTraitLine,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @method for method MethodTagTrait\Foo::doBar() parameter #1 $a contains unresolvable type.',
				$fooTraitLine,
			],
			[
				'PHPDoc tag @method for method MethodTagTrait\Foo::doBaz2() parameter #1 $a default value contains unresolvable type.',
				$fooTraitLine,
			],
		]);
	}

	public function testEnum(): void
	{
		if (PHP_VERSION_ID < 80100) {
			self::markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/method-tag-trait-enum.php'], [
			[
				'PHPDoc tag @method for method MethodTagTraitEnum\Foo::doFoo() return type contains unknown class MethodTagTraitEnum\intt.',
				8,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testBug11591(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11591-method-tag.php'], []);
	}

}

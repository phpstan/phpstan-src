<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Classes\ForbiddenClassNameExtension;
use PHPStan\DependencyInjection\LazyExtensionsCollection;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<MethodTagTraitUseRule>
 */
class MethodTagTraitUseRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new MethodTagTraitUseRule(
			new MethodTagCheck(
				$reflectionProvider,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck(new LazyExtensionsCollection($container, ForbiddenClassNameExtension::class)),
					$reflectionProvider,
					new LazyExtensionsCollection($container, RestrictedClassNameUsageExtension::class),
				),
				new GenericObjectTypeCheck(),
				new MissingTypehintCheck(true, [], true),
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

	#[RequiresPhp('>= 8.1.0')]
	public function testEnum(): void
	{
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

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

/**
 * @extends RuleTestCase<MethodTagTraitRule>
 */
class MethodTagTraitRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		$reflectionProvider = $this->createReflectionProvider();

		return new MethodTagTraitRule(
			new MethodTagCheck(
				$reflectionProvider,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck(self::getContainer()),
				),
				new GenericObjectTypeCheck(),
				new MissingTypehintCheck(true, true, true, true, []),
				new UnresolvableTypeHelper(),
				true,
			),
			$reflectionProvider,
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
			[
				'Trait MethodTagTrait\Foo has PHPDoc tag @method for method doMissingIterablueValue() return type with no value type specified in iterable type array.',
				$fooTraitLine,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
		]);
	}

}

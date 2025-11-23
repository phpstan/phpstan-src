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
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new MethodTagTraitRule(
			new MethodTagCheck(
				$reflectionProvider,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
				),
				new GenericObjectTypeCheck(),
				new MissingTypehintCheck(true, []),
				new UnresolvableTypeHelper(),
				true,
				true,
				true,
			),
			$reflectionProvider,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/method-tag-trait.php'], [
			[
				'Trait MethodTagTrait\Foo has PHPDoc tag @method for method doMissingIterablueValue() return type with no value type specified in iterable type array.',
				12,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
		]);
	}

	public function testBug11591(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11591-method-tag.php'], []);
	}

}

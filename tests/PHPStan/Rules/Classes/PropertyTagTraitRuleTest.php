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
 * @extends RuleTestCase<PropertyTagTraitRule>
 */
class PropertyTagTraitRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		$reflectionProvider = $this->createReflectionProvider();

		return new PropertyTagTraitRule(
			new PropertyTagCheck(
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
		$this->analyse([__DIR__ . '/data/property-tag-trait.php'], [
			[
				'PHPDoc tag @property for property PropertyTagTrait\Foo::$foo contains unknown class PropertyTagTrait\intt.',
				8,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

}

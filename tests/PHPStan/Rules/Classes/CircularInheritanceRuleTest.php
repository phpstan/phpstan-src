<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CircularInheritanceRule>
 */
class CircularInheritanceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CircularInheritanceRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/circular-inheritance.php'], [
			[
				'Class CircularInheritance\ExtendsSelf extends itself.',
				5,
			],
			[
				'Class CircularInheritance\FirstOfPair extends CircularInheritance\SecondOfPair, which extends CircularInheritance\FirstOfPair.',
				10,
			],
			[
				'Class CircularInheritance\SecondOfPair extends CircularInheritance\FirstOfPair, which extends CircularInheritance\SecondOfPair.',
				15,
			],
			[
				'Class CircularInheritance\FirstOfThree extends CircularInheritance\SecondOfThree, which extends CircularInheritance\ThirdOfThree, which extends CircularInheritance\FirstOfThree.',
				20,
			],
			[
				'Class CircularInheritance\SecondOfThree extends CircularInheritance\ThirdOfThree, which extends CircularInheritance\FirstOfThree, which extends CircularInheritance\SecondOfThree.',
				25,
			],
			[
				'Class CircularInheritance\ThirdOfThree extends CircularInheritance\FirstOfThree, which extends CircularInheritance\SecondOfThree, which extends CircularInheritance\ThirdOfThree.',
				30,
			],
			[
				'Interface CircularInheritance\InterfaceExtendsSelf extends itself.',
				40,
			],
			[
				'Interface CircularInheritance\FirstInterfaceOfPair extends CircularInheritance\SecondInterfaceOfPair, which extends CircularInheritance\FirstInterfaceOfPair.',
				45,
			],
			[
				'Interface CircularInheritance\SecondInterfaceOfPair extends CircularInheritance\FirstInterfaceOfPair, which extends CircularInheritance\SecondInterfaceOfPair.',
				50,
			],
			[
				'Trait CircularInheritance\TraitUsesSelf uses itself.',
				85,
			],
			[
				'Trait CircularInheritance\FirstTraitOfPair uses CircularInheritance\SecondTraitOfPair, which uses CircularInheritance\FirstTraitOfPair.',
				92,
			],
			[
				'Trait CircularInheritance\SecondTraitOfPair uses CircularInheritance\FirstTraitOfPair, which uses CircularInheritance\SecondTraitOfPair.',
				99,
			],
			[
				'Class CircularInheritance\ExtendsSelfWithDifferentCase extends itself.',
				139,
			],
		]);
	}

}

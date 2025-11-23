<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Rules\Properties\UninitializedPropertyRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<PromoteParameterRule<ClassPropertiesNode>>
 */
class PromoteParameterRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$container = self::getContainer();
		return new PromoteParameterRule(
			new UninitializedPropertyRule(new ConstructorsHelper(
				$container,
				[],
			)),
			$container,
			ClassPropertiesNode::class,
			false,
			'checkUninitializedProperties',
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/promote-parameter.php'], [
			[
				'Class PromoteParameter\Foo has an uninitialized property $test. Give it default value or assign it in the constructor.',
				8,
				'This error would be reported if the <fg=cyan>checkUninitializedProperties: true</> parameter was enabled in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

}

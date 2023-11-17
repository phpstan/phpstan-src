<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidCallablePropertyTypeRule>
 */
class InvalidCallablePropertyTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidCallablePropertyTypeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-callable-property-type.php'], [
			[
				'Property InvalidCallablePropertyType\HelloWorld::$a cannot have callable in its type declaration.',
				9,
			],
			[
				'Property InvalidCallablePropertyType\HelloWorld::$b cannot have callable in its type declaration.',
				12,
			],
			[
				'Property InvalidCallablePropertyType\HelloWorld::$c cannot have callable in its type declaration.',
				15,
			],
			[
				'Property InvalidCallablePropertyType\HelloWorld::$callback cannot have callable in its type declaration.',
				23,
			],
		]);
	}

}

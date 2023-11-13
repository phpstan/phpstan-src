<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<PropertiesInInterfaceRule>
 */
class PropertiesInInterfaceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PropertiesInInterfaceRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/properties-in-interface.php'], [
			[
				'Interfaces may not include properties.',
				7,
			],
			[
				'Interfaces may not include properties.',
				9,
			],
		]);
	}

}

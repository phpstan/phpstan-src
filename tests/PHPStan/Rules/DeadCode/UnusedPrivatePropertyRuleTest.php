<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnusedPrivatePropertyRule>
 */
class UnusedPrivatePropertyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedPrivatePropertyRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-private-property.php'], [
			[
				'Class UnusedPrivateProperty\Foo has a write-only property $bar.',
				10,
			],
			[
				'Class UnusedPrivateProperty\Foo has an unused property $baz.',
				12,
			],
			[
				'Class UnusedPrivateProperty\Foo has a read-only property $lorem.',
				14,
			],
		]);
	}

}

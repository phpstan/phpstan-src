<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<VirtualNodeGetTypeRule>
 */
class VirtualNodeGetTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new VirtualNodeGetTypeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/virtual-node-get-type.php'], [
			[
				'mixed',
				7,
			],
		]);
	}

}

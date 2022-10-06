<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IncompatibleSelfOutTypeRule>
 */
class IncompatibleSelfOutTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IncompatibleSelfOutTypeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-self-out-type.php'], [
			[
				'Out type int is not compatible with IncompatibleSelfOutType\A.',
				23,
			],
		]);
	}

}

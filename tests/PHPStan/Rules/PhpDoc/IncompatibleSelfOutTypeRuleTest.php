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
				'Self-out type int of method IncompatibleSelfOutType\A::three is not subtype of IncompatibleSelfOutType\A.',
				23,
			],
			[
				'Self-out type IncompatibleSelfOutType\A|null of method IncompatibleSelfOutType\A::four is not subtype of IncompatibleSelfOutType\A.',
				28,
			],
		]);
	}

}

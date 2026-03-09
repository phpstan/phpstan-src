<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidParameterNameRule>
 */
class InvalidParameterNameRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidParameterNameRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14241.php'], [
			[
				'Cannot re-assign auto-global variable $_FILES.',
				5,
			],
			[
				'Cannot re-assign auto-global variable $_GET.',
				7,
			],
			[
				'Cannot re-assign auto-global variable $_POST.',
				7,
			],
			[
				'Cannot re-assign auto-global variable $_SERVER.',
				13,
			],
			[
				'Cannot re-assign auto-global variable $_SESSION.',
				15,
			],
			[
				'Cannot re-assign auto-global variable $_COOKIE.',
				18,
			],
			[
				'Cannot re-assign auto-global variable $_REQUEST.',
				20,
			],
			[
				'Cannot re-assign auto-global variable $_ENV.',
				22,
			],
			[
				'Cannot re-assign auto-global variable $GLOBALS.',
				24,
			],
			[
				'Cannot use $this as parameter.',
				26,
			],
		]);
	}

}

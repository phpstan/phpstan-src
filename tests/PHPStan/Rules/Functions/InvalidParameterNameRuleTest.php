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
				'Superglobal variable $_FILES cannot be used as a parameter.',
				5,
			],
			[
				'Superglobal variable $_GET cannot be used as a parameter.',
				7,
			],
			[
				'Superglobal variable $_POST cannot be used as a parameter.',
				7,
			],
			[
				'Superglobal variable $_SERVER cannot be used as a parameter.',
				13,
			],
			[
				'Superglobal variable $_SESSION cannot be used as a parameter.',
				15,
			],
			[
				'Superglobal variable $_COOKIE cannot be used as a parameter.',
				18,
			],
			[
				'Superglobal variable $_REQUEST cannot be used as a parameter.',
				20,
			],
			[
				'Superglobal variable $_ENV cannot be used as a parameter.',
				22,
			],
			[
				'Superglobal variable $GLOBALS cannot be used as a parameter.',
				24,
			],
			[
				'Cannot use $this as parameter.',
				26,
			],
		]);
	}

}

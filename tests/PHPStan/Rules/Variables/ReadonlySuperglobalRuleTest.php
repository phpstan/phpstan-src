<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReadonlySuperglobalRule>
 */
class ReadonlySuperglobalRuleTest extends RuleTestCase
{

	private bool $readonlySuperglobals;

	protected function getRule(): Rule
	{
		return new ReadonlySuperglobalRule($this->readonlySuperglobals);
	}

	public function testRule(): void
	{
		$this->readonlySuperglobals = true;
		$tip = 'Superglobals are read-only because the readonlySuperglobals parameter is enabled.';
		$this->analyse([__DIR__ . '/data/readonly-superglobals.php'], [
			['Superglobal $_GET is written to.', 7, $tip],
			['Superglobal $_POST is written to.', 8, $tip],
			['Superglobal $_COOKIE is written to.', 9, $tip],
			['Superglobal $_REQUEST is written to.', 10, $tip],
			['Superglobal $_GET is written to.', 11, $tip],
			['Superglobal $_SERVER is written to.', 12, $tip],
			['Superglobal $_ENV is written to.', 13, $tip],
			['Superglobal $_GET is written to.', 14, $tip],
			['Superglobal $_GET is written to.', 14, $tip],
			['Superglobal $_POST is written to.', 15, $tip],
			['Superglobal $_GET is written to.', 17, $tip],
			['Superglobal $_POST is written to.', 18, $tip],
			['Superglobal $GLOBALS is written to.', 19, $tip],
			['Superglobal $_SESSION is written to.', 20, $tip],
			['Superglobal $_FILES is written to.', 21, $tip],
		]);
	}

	public function testRuleDisabled(): void
	{
		$this->readonlySuperglobals = false;
		$this->analyse([__DIR__ . '/data/readonly-superglobals.php'], []);
	}

}

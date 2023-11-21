<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidLexicalVariablesInClosureUseRule>
 */
class InvalidLexicalVariablesInClosureUseRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidLexicalVariablesInClosureUseRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-lexical-variables-in-closure-use.php'], [
			[
				'Cannot use $this as lexical variable.',
				25,
			],
			[
				'Cannot use superglobal variable $GLOBALS as lexical variable.',
				35,
			],
			[
				'Cannot use superglobal variable $_COOKIE as lexical variable.',
				35,
			],
			[
				'Cannot use superglobal variable $_ENV as lexical variable.',
				35,
			],
			[
				'Cannot use superglobal variable $_FILES as lexical variable.',
				35,
			],
			[
				'Cannot use superglobal variable $_GET as lexical variable.',
				35,
			],
			[
				'Cannot use superglobal variable $_POST as lexical variable.',
				35,
			],
			[
				'Cannot use superglobal variable $_REQUEST as lexical variable.',
				35,
			],
			[
				'Cannot use superglobal variable $_SERVER as lexical variable.',
				35,
			],
			[
				'Cannot use superglobal variable $_SESSION as lexical variable.',
				35,
			],
			[
				'Cannot use lexical variable $baz since a parameter with the same name already exists.',
				55,
			],
			[
				'Cannot use $this as lexical variable.',
				68,
			],
			[
				'Cannot use superglobal variable $GLOBALS as lexical variable.',
				81,
			],
			[
				'Cannot use superglobal variable $_COOKIE as lexical variable.',
				82,
			],
			[
				'Cannot use superglobal variable $_ENV as lexical variable.',
				83,
			],
			[
				'Cannot use superglobal variable $_FILES as lexical variable.',
				84,
			],
			[
				'Cannot use superglobal variable $_GET as lexical variable.',
				85,
			],
			[
				'Cannot use superglobal variable $_POST as lexical variable.',
				86,
			],
			[
				'Cannot use superglobal variable $_REQUEST as lexical variable.',
				87,
			],
			[
				'Cannot use superglobal variable $_SERVER as lexical variable.',
				88,
			],
			[
				'Cannot use superglobal variable $_SESSION as lexical variable.',
				89,
			],
			[
				'Cannot use lexical variable $baz since a parameter with the same name already exists.',
				111,
			],
			[
				'Cannot use lexical variable $bar since a parameter with the same name already exists.',
				112,
			],
		]);
	}

}

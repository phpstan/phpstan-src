<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<StringReplaceSubjectParameterRule>
 */
class StringReplaceSubjectParameterRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new StringReplaceSubjectParameterRule($this->createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/string-replace-subject.php'], [
			[
				'Argument #0 in unexpected position in call to function strtr.',
				11,
			],
			[
				'Argument #2 in unexpected position in call to function str_replace.',
				12,
			],
			[
				'Argument #2 in unexpected position in call to function preg_replace.',
				13,
			],
			[
				'Argument #2 in unexpected position in call to function str_replace.',
				14,
			],
			[
				'Argument #2 in unexpected position in call to function preg_replace.',
				15,
			],
			[
				'Argument #0 in unexpected position in call to function strtr.',
				17,
			],
			[
				'Argument #2 in unexpected position in call to function str_replace.',
				18,
			],
			[
				'Argument #2 in unexpected position in call to function preg_replace.',
				19,
			],
			[
				'Argument #0 in unexpected position in call to function strtr.',
				21,
			],
			[
				'Argument #2 in unexpected position in call to function str_replace.',
				22,
			],
			[
				'Argument #2 in unexpected position in call to function preg_replace.',
				23,
			],
			[
				'Argument #0 in unexpected position in call to function strtr.',
				25,
			],
			[
				'Argument #2 in unexpected position in call to function str_replace.',
				26,
			],
			[
				'Argument #2 in unexpected position in call to function preg_replace.',
				27,
			],
			[
				'Argument #0 in unexpected position in call to function strtr.',
				29,
			],
			[
				'Argument #2 in unexpected position in call to function str_replace.',
				30,
			],
			[
				'Argument #2 in unexpected position in call to function preg_replace.',
				31,
			],
			[
				'Argument #0 in unexpected position in call to function strtr.',
				33,
			],
			[
				'Argument #2 in unexpected position in call to function str_replace.',
				34,
			],
			[
				'Argument #2 in unexpected position in call to function preg_replace.',
				35,
			],
		]);
	}

}

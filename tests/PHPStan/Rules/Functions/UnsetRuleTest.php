<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

/**
 * @extends \PHPStan\Testing\RuleTestCase<UnsetRule>
 */
class UnsetRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new UnsetRule(true);
	}

	public function testUnsetRule(): void
	{
		require_once __DIR__ . '/data/unset.php';
		$this->analyse([__DIR__ . '/data/unset.php'], [
			[
				'Call to function unset() contains undefined variable $notSetVariable.',
				3,
			],
			[
				'Cannot unset offset \'a\' on 3.',
				7,
			],
			[
				'Cannot unset offset \'b\' on 1.',
				11,
			],
			[
				'Cannot unset offset \'c\' on 1.',
				15,
			],
			[
				'Cannot unset offset \'b\' on 1.',
				15,
			],
			[
				'Call to function unset() contains possibly undefined variable $maybeSet.',
				21,
			],
			[
				'Cannot unset offset \'string\' on iterable<int, int>.',
				30,
			],
		]);
	}

}

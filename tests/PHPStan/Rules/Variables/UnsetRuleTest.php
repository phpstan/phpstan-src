<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

/**
 * @extends \PHPStan\Testing\RuleTestCase<UnsetRule>
 */
class UnsetRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new UnsetRule();
	}

	public function testUnsetRule(): void
	{
		require_once __DIR__ . '/data/unset.php';
		$this->analyse([__DIR__ . '/data/unset.php'], [
			[
				'Call to function unset() contains undefined variable $notSetVariable.',
				6,
			],
			[
				'Cannot unset offset \'a\' on 3.',
				10,
			],
			[
				'Cannot unset offset \'b\' on 1.',
				14,
			],
			[
				'Cannot unset offset \'c\' on 1.',
				18,
			],
			[
				'Cannot unset offset \'b\' on 1.',
				18,
			],
			[
				'Cannot unset offset \'string\' on iterable<int, int>.',
				31,
			],
			[
				'Call to function unset() contains undefined variable $notSetVariable.',
				36,
			],
		]);
	}

	public function testBug2752(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2752.php'], []);
	}

	public function testBug4289(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4289.php'], []);
	}

}

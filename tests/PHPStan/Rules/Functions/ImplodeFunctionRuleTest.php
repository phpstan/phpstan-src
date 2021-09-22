<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ImplodeFunctionRule>
 */
class ImplodeFunctionRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createReflectionProvider();
		return new ImplodeFunctionRule($broker, new RuleLevelHelper($broker, true, false, true, false));
	}

	public function testFile(): void
	{
		$this->analyse([__DIR__ . '/data/implode.php'], [
			[
				'Call to function implode() with invalid non-string argument type array<int, string>|string.',
				9,
			],
			[
				'Call to function implode() with invalid non-string argument type array<int, string>.',
				11,
			],
			[
				'Call to function implode() with invalid non-string argument type array<int, int>.',
				12,
			],
			[
				'Call to function implode() with invalid non-string argument type array<int, int|true>.',
				13,
			],
			[
				'Call to function implode() with invalid non-string argument type array<int, string>.',
				15,
			],
			[
				'Call to function join() with invalid non-string argument type array<int, string>.',
				16,
			],
		]);
	}

}

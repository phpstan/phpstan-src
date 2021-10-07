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
				'Parameter #2 $array of function implode expects array<string>, array<int, array<int, string>|string> given.',
				9,
			],
			[
				'Parameter #1 $array of function implode expects array<string>, array<int, array<int, string>> given.',
				11,
			],
			[
				'Parameter #1 $array of function implode expects array<string>, array<int, array<int, int>> given.',
				12,
			],
			[
				'Parameter #1 $array of function implode expects array<string>, array<int, array<int, int|true>> given.',
				13,
			],
			[
				'Parameter #2 $array of function implode expects array<string>, array<int, array<int, string>> given.',
				15,
			],
			[
				'Parameter #2 $array of function join expects array<string>, array<int, array<int, string>> given.',
				16,
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/implode-nullsafe.php'], [
			[
				'Parameter #1 $array of function implode expects array<string>, array<int, array<string>>|null given.',
				17,
			],
		]);
	}

}

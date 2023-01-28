<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ImplodeFunctionRule>
 */
class ImplodeFunctionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ImplodeFunctionRule($broker, new RuleLevelHelper($broker, true, false, true, false, false, true, false));
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

	public function testBug6000(): void
	{
		$this->analyse([__DIR__ . '/../Arrays/data/bug-6000.php'], []);
	}

	public function testBug8467a(): void
	{
		$this->analyse([__DIR__ . '/../Arrays/data/bug-8467a.php'], []);
	}

}

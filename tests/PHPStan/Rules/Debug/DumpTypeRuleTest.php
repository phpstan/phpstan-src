<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DumpTypeRule>
 */
class DumpTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DumpTypeRule($this->createReflectionProvider());
	}

	public function testRuleInPhpStanNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/dump-type.php'], [
			[
				'Dumped type: non-empty-array',
				10,
			],
			[
				'Missing argument for PHPStan\dumpType() function call.',
				11,
			],
		]);
	}

	public function testRuleInDifferentNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/dump-type-ns.php'], [
			[
				'Dumped type: non-empty-array',
				10,
			],
		]);
	}

	public function testRuleInUse(): void
	{
		$this->analyse([__DIR__ . '/data/dump-type-use.php'], [
			[
				'Dumped type: non-empty-array',
				12,
			],
			[
				'Dumped type: non-empty-array',
				13,
			],
		]);
	}

	public function testBug7803(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7803.php'], [
			[
				'Dumped type: int<4, max>',
				11,
			],
			[
				'Dumped type: non-empty-array<int, string>',
				12,
			],
			[
				'Dumped type: int<4, max>',
				13,
			],
		]);
	}

}

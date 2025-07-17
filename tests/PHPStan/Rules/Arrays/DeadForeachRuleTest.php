<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DeadForeachRule>
 */
class DeadForeachRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DeadForeachRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/dead-foreach.php'], [
			[
				'Empty array passed to foreach.',
				16,
			],
			[
				'Empty array passed to foreach.',
				30,
			],
		]);
	}

	public function testBug7913(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7913.php'], []);
	}

	public function testBug8292(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8292.php'], []);
	}

	public function testBug13248(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13248.php'], []);
	}

}

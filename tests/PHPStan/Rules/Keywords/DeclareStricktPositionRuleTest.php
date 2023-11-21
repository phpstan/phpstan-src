<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DeclareStrictPositionRule>
 */
class DeclareStricktPositionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DeclareStrictPositionRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/declare-position.php'], [
			[
				'Declare strict_types must be the very first statement.',
				5,
			],
		]);
	}

	public function testRule2(): void
	{
		$this->analyse([__DIR__ . '/data/declare-position2.php'], [
			[
				'Declare strict_types must be the very first statement.',
				1,
			],
		]);
	}

	public function testNested(): void
	{
		$this->analyse([__DIR__ . '/data/declare-position-nested.php'], [
			[
				'Declare strict_types must be the very first statement.',
				7,
			],
			[
				'Declare strict_types must be the very first statement.',
				12,
			],
		]);
	}

	public function testValidPosition(): void
	{
		$this->analyse([__DIR__ . '/data/declare-position-valid.php'], []);
	}

	public function testTicks(): void
	{
		$this->analyse([__DIR__ . '/data/declare-ticks.php'], []);
	}

	public function testMulti(): void
	{
		$this->analyse([__DIR__ . '/data/declare-multi.php'], []);
	}

}

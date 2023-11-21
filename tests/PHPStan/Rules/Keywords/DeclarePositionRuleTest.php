<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DeclarePositionRule>
 */
class DeclarePositionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DeclarePositionRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/declare-position.php'], [
			[
				'Declare must be the very first statement.',
				5,
			],
		]);
	}

	public function testRule2(): void
	{
		$this->analyse([__DIR__ . '/data/declare-position2.php'], [
			[
				'Declare must be the very first statement.',
				1,
			],
		]);
	}

	public function testNested(): void
	{
		$this->analyse([__DIR__ . '/data/declare-position-nested.php'], [
			[
				'Declare must be the very first statement.',
				7,
			],
			[
				'Declare must be the very first statement.',
				12,
			],
		]);
	}

	public function testValidPosition(): void
	{
		$this->analyse([__DIR__ . '/data/declare-position-valid.php'], []);
	}

}

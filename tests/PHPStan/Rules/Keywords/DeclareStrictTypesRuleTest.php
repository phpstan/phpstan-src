<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DeclareStrictTypesRule>
 */
class DeclareStrictTypesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DeclareStrictTypesRule(new ExprPrinter(new Printer()));
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

	public function testShebang(): void
	{
		$this->analyse([__DIR__ . '/data/declare-shebang.php'], []);
		$this->analyse([__DIR__ . '/data/declare-shebang2.php'], []);
		$this->analyse([__DIR__ . '/data/declare-shebang3.php'], []);
	}

	public function testHtmlBeforeDecalre(): void
	{
		$this->analyse([__DIR__ . '/data/declare-inline-html.php'], [
			[
				'Declare strict_types must be the very first statement.',
				2,
			],
		]);
	}

	public function testNonsense(): void
	{
		$this->analyse([__DIR__ . '/data/declare-strict-nonsense.php'], [
			[
				"Declare strict_types must have 0 or 1 as its value, 'foo' given.",
				1,
			],
		]);
	}

	public function testNonsenseBool(): void
	{
		$this->analyse([__DIR__ . '/data/declare-strict-nonsense-bool.php'], [
			[
				'Declare strict_types must have 0 or 1 as its value, \true given.',
				1,
			],
		]);
	}

}

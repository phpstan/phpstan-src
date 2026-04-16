<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<InvalidVariableAssignRule>
 */
class InvalidVariableAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidVariableAssignRule();
	}

	public function testBug3585(): void
	{
		$this->analyse([__DIR__ . '/../Operators/data/bug-3585.php'], [
			[
				'Cannot re-assign $this.',
				9,
			],
			[
				'Cannot re-assign $this.',
				10,
			],
			[
				'Cannot re-assign $this.',
				11,
			],
			[
				'Cannot re-assign $this.',
				12,
			],
			[
				'Cannot re-assign $this.',
				17,
			],
			[
				'Cannot re-assign $this.',
				23,
			],
			[
				'Cannot re-assign $this.',
				28,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug14352(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14352.php'], [
			/*
			[
				'Cannot re-assign $this.',
				13,
			],
			*/
			[
				'Cannot re-assign $this.',
				37,
			],
			[
				'Cannot re-assign $this.',
				39,
			],
			[
				'Cannot re-assign $this.',
				47,
			],
			[
				'Cannot re-assign $this.',
				49,
			],
			[
				'Cannot re-assign $this.',
				57,
			],
			[
				'Cannot re-assign $this.',
				63,
			],
		]);
	}

	public function testBug14351(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14351.php'], [
			[
				'Cannot re-assign $this.',
				9,
			],
		]);
	}

	public function testBug14349(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14349.php'], [
			[
				'Cannot re-assign $this.',
				11,
			],
			[
				'Cannot re-assign $this.',
				15,
			],
			[
				'Cannot re-assign $this.',
				19,
			],
			[
				'Cannot re-assign $this.',
				27,
			],
			[
				'Cannot re-assign $this.',
				28,
			],
			[
				'Cannot re-assign $this.',
				29,
			],
			[
				'Cannot re-assign $this.',
				30,
			],
			[
				'Cannot re-assign $this.',
				35,
			],
			[
				'Cannot re-assign $this.',
				42,
			],
		]);
	}

}

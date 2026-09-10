<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<UnusedVariableRule>
 */
class UnusedVariableRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedVariableRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable.php'], [
			[
				'Variable $a is never read.',
				27,
			],
			[
				'Value assigned to variable $a is never read.',
				32,
			],
			[
				'Variable $a is never read.',
				40,
			],
			[
				'Value assigned to variable $x is never read.',
				46,
			],
			[
				'Variable $a is never read.',
				70,
			],
			[
				'Variable $a is never read.',
				76,
			],
			[
				'Variable $a is never read.',
				93,
			],
			[
				'Variable $a is never read.',
				95,
			],
			[
				'Variable $a is never read.',
				101,
			],
			[
				'Variable $a is never read.',
				113,
			],
			[
				'Foreach key variable $k is never read.',
				119,
			],
			[
				'Foreach value variable $v is never read.',
				126,
			],
			[
				'Foreach value variable $v is never read.',
				133,
			],
			[
				'Variable $a is never read.',
				148,
			],
			[
				'Value of variable $i after ++ is never read.',
				157,
			],
			[
				'Variable $x is never read.',
				223,
			],
			[
				'Value assigned to variable $x is never read.',
				251,
			],
			[
				'Value assigned to variable $s is never read.',
				264,
			],
			[
				'Variable $a is never read.',
				276,
			],
			[
				'Variable $f is never read.',
				283,
			],
			[
				'Variable $a is never read.',
				303,
			],
			[
				'Variable $x is never read.',
				337,
			],
			[
				'Value assigned to variable $title is never read.',
				422,
			],
			[
				'Variable $b is never read.',
				614,
			],
			[
				'Variable $a is never read.',
				632,
			],
			[
				'Variable $a is never read.',
				637,
			],
			[
				'Value assigned to variable $a is never read.',
				703,
			],
			[
				'Variable $a is never read.',
				739,
			],
			[
				'Value assigned to variable $a is never read.',
				744,
			],
			[
				'Value assigned to variable $tags is never read.',
				840,
			],
			[
				'Value of variable $i after -- is never read.',
				864,
			],
			[
				'Value assigned to variable $x is never read.',
				870,
			],
			[
				'Foreach value variable $v is never read.',
				877,
			],
			[
				'Value of variable $i after ++ is never read.',
				885,
			],
			[
				'Value of variable $i after -- is never read.',
				892,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testPhp8(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-php8.php'], [
			[
				'Catch variable $e is never read.',
				23,
			],
			[
				'Value assigned to variable $nightsFrom is never read.',
				98,
			],
		]);
	}

	public function testRedundantAssignment(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-redundant.php'], [
			[
				'Variable $x is assigned value true but it already has that value.',
				26,
			],
			[
				'Value assigned to variable $x is never read.',
				42,
			],
			[
				'Variable $x is assigned value 1 but it already has that value.',
				43,
			],
			[
				'Variable $x is assigned value null but it already has that value.',
				51,
			],
			[
				'Variable $s is assigned value \'a\' but it already has that value.',
				60,
			],
			[
				'Variable $a is assigned value array{k: 1} but it already has that value.',
				69,
			],
			[
				'Value assigned to variable $x is never read.',
				95,
			],
			[
				'Variable $x is assigned value 1 but it already has that value.',
				118,
			],
		]);
	}

}

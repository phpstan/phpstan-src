<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
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
		return new UnusedVariableRule(self::getContainer()->getByType(PhpVersion::class), self::getContainer()->getByType(ExprPrinter::class));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable.php'], [
			[
				'Value assigned to variable $a is never used.',
				27,
			],
			[
				'Value assigned to variable $a is never used.',
				32,
			],
			[
				'Value assigned to variable $a is never used.',
				40,
			],
			[
				'Value assigned to variable $x is never used.',
				46,
			],
			[
				'Value assigned to variable $a is never used.',
				70,
			],
			[
				'Value assigned to variable $a is never used.',
				76,
			],
			[
				'Value assigned to variable $a is never used.',
				93,
			],
			[
				'Value assigned to variable $a is never used.',
				95,
			],
			[
				'Value assigned to variable $a is never used.',
				101,
			],
			[
				'Value assigned to variable $a is never used.',
				113,
			],
			[
				'Value assigned to variable $k is never used.',
				119,
			],
			[
				'Value assigned to variable $v is never used.',
				126,
			],
			[
				'Value assigned to variable $v is never used.',
				133,
			],
			[
				'Value assigned to variable $a is never used.',
				148,
			],
			[
				'Value assigned to variable $i is never used.',
				157,
			],
			[
				'Value assigned to variable $x is never used.',
				223,
			],
			[
				'Value assigned to $x[] is never used.',
				250,
			],
			[
				"Value assigned to \$x['k'] is never used.",
				251,
			],
			[
				'Value assigned to variable $s is never used.',
				263,
			],
			[
				'Value assigned to variable $s is never used.',
				264,
			],
			[
				'Value assigned to variable $a is never used.',
				269,
			],
			[
				'Value assigned to variable $a is never used.',
				276,
			],
			[
				'Value assigned to variable $f is never used.',
				283,
			],
			[
				'Value assigned to variable $a is never used.',
				303,
			],
			[
				'Value assigned to variable $x is never used.',
				337,
			],
			[
				'Value assigned to variable $title is never used.',
				422,
			],
			[
				'Value assigned to variable $b is never used.',
				614,
			],
			[
				'Value assigned to variable $a is never used.',
				632,
			],
			[
				'Value assigned to variable $a is never used.',
				637,
			],
			[
				'Value assigned to variable $a is never used.',
				702,
			],
			[
				'Value assigned to variable $a is never used.',
				703,
			],
			[
				'Value assigned to variable $b is never used.',
				709,
			],
			[
				'Value assigned to variable $b is never used.',
				719,
			],
			[
				'Value assigned to variable $a is never used.',
				739,
			],
			[
				'Value assigned to variable $a is never used.',
				744,
			],
			[
				'Value assigned to variable $tags is never used.',
				840,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testPhp8(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-php8.php'], [
			[
				'Value assigned to variable $e is never used.',
				23,
			],
			[
				'Value assigned to variable $nightsFrom is never used.',
				98,
			],
			[
				'Value assigned to variable $a is never used.',
				107,
			],
			[
				'Value assigned to variable $b is never used.',
				108,
			],
			[
				'Value assigned to variable $b is never used.',
				116,
			],
		]);
	}

	public function testValueFlow(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-value-flow.php'], [
			[
				'Value assigned to variable $a is never used.',
				27,
			],
			[
				'Value assigned to variable $a is never used.',
				28,
			],
			[
				'Value assigned to variable $s is never used.',
				40,
			],
			[
				'Value assigned to variable $s is never used.',
				41,
			],
			[
				'Value assigned to variable $s is never used.',
				42,
			],
			[
				'Value assigned to variable $i is never used.',
				54,
			],
			[
				'Value assigned to variable $i is never used.',
				55,
			],
			[
				'Value assigned to variable $i is never used.',
				56,
			],
			[
				'Value assigned to variable $i is never used.',
				57,
			],
			[
				'Value assigned to variable $i is never used.',
				58,
			],
			[
				'Value assigned to variable $i is never used.',
				71,
			],
			[
				'Value assigned to variable $i is never used.',
				76,
			],
			[
				'Value assigned to variable $j is never used.',
				77,
			],
			[
				'Value assigned to variable $i is never used.',
				77,
			],
			[
				'Value assigned to variable $n is never used.',
				98,
			],
			[
				'Value assigned to variable $n is never used.',
				100,
			],
			[
				'Value assigned to variable $a is never used.',
				117,
			],
			[
				'Value assigned to variable $ok is never used.',
				118,
			],
			[
				'Value assigned to variable $a is never used.',
				130,
			],
			[
				'Value assigned to variable $b is never used.',
				131,
			],
			[
				'Value assigned to variable $b is never used.',
				144,
			],
			[
				'Value assigned to variable $b is never used.',
				150,
			],
			[
				'Value assigned to variable $a is never used.',
				155,
			],
			[
				'Value assigned to variable $arr is never used.',
				156,
			],
			[
				'Value assigned to variable $a is never used.',
				168,
			],
			[
				'Value assigned to variable $b is never used.',
				169,
			],
			[
				'Value assigned to variable $c is never used.',
				170,
			],
			[
				'Value assigned to variable $a is never used.',
				175,
			],
			[
				'Value assigned to variable $b is never used.',
				176,
			],
			[
				'Value assigned to variable $c is never used.',
				177,
			],
			[
				'Value assigned to variable $d is never used.',
				178,
			],
			[
				'Value assigned to variable $a is never used.',
				183,
			],
			[
				'Value assigned to variable $b is never used.',
				184,
			],
			[
				'Value assigned to variable $a is never used.',
				189,
			],
			[
				'Value assigned to variable $b is never used.',
				190,
			],
			[
				'Value assigned to variable $d is never used.',
				195,
			],
			[
				'Value assigned to variable $b is never used.',
				196,
			],
			[
				'Value assigned to variable $b is never used.',
				202,
			],
			[
				'Value assigned to variable $b is never used.',
				209,
			],
			[
				'Value assigned to variable $d is never used.',
				210,
			],
			[
				'Value assigned to variable $b is never used.',
				216,
			],
			[
				'Value assigned to variable $b is never used.',
				222,
			],
			[
				'Value assigned to variable $b is never used.',
				228,
			],
			[
				'Value assigned to variable $b is never used.',
				234,
			],
			[
				'Value assigned to variable $f is never used.',
				240,
			],
			[
				'Value assigned to variable $f is never used.',
				248,
			],
			[
				'Value assigned to variable $b is never used.',
				254,
			],
			[
				'Value assigned to variable $b is never used.',
				260,
			],
			[
				'Value assigned to variable $b is never used.',
				266,
			],
			[
				'Value assigned to variable $b is never used.',
				272,
			],
			[
				'Value assigned to variable $b is never used.',
				278,
			],
			[
				'Value assigned to variable $b is never used.',
				284,
			],
			[
				'Value assigned to variable $b is never used.',
				289,
			],
			[
				'Value assigned to variable $a is never used.',
				295,
			],
			[
				'Value assigned to variable $b is never used.',
				302,
			],
			[
				'Value assigned to variable $c is never used.',
				308,
			],
			[
				'Value assigned to variable $a is never used.',
				309,
			],
			[
				'Value assigned to variable $b is never used.',
				309,
			],
			[
				'Value assigned to variable $b is never used.',
				315,
			],
			[
				'Value assigned to variable $a is never used.',
				321,
			],
			[
				'Value assigned to variable $b is never used.',
				322,
			],
			[
				'Value assigned to variable $v is never used.',
				329,
			],
			[
				"Value assigned to \$a['x'] is never used.",
				331,
			],
			[
				'Value assigned to variable $a is never used.',
				344,
			],
			[
				'Value assigned to variable $b is never used.',
				345,
			],
			[
				'Value assigned to variable $i is never used.',
				357,
			],
			[
				'Value assigned to variable $a is never used.',
				358,
			],
			[
				'Value assigned to variable $b is never used.',
				359,
			],
			[
				'Value assigned to variable $a is never used.',
				364,
			],
			[
				'Value assigned to variable $a is never used.',
				369,
			],
			[
				'Value assigned to variable $b is never used.',
				370,
			],
			[
				'Value assigned to variable $c is never used.',
				371,
			],
			[
				'Value assigned to variable $d is never used.',
				372,
			],
			[
				'Value assigned to variable $s is never used.',
				395,
			],
			[
				'Value assigned to variable $v is never used.',
				396,
			],
			[
				'Value assigned to variable $s is never used.',
				397,
			],
			[
				'Value assigned to variable $a is never used.',
				403,
			],
			[
				'Value assigned to variable $a is never used.',
				404,
			],
			[
				'Value assigned to variable $s is never used.',
				477,
			],
			[
				'Value assigned to variable $s is never used.',
				483,
			],
			[
				'Value assigned to variable $x is never used.',
				484,
			],
			[
				'Value assigned to variable $s is never used.',
				484,
			],
		]);
	}

	public function testOffsets(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-offsets.php'], [
			[
				"Offset 'x' of array assigned to variable \$a is never used.",
				27,
			],
			[
				'Value assigned to variable $a is never used.',
				39,
			],
			[
				"Offset 'x' of array assigned to variable \$a is never used.",
				44,
			],
			[
				'Offset 1 of array assigned to variable $a is never used.',
				50,
			],
			[
				'Offset 2 of array assigned to variable $a is never used.',
				50,
			],
			[
				"Offset 'y' of array assigned to variable \$a is never used.",
				58,
			],
			[
				"Offset 'x' of array assigned to variable \$a is never used.",
				67,
			],
			[
				"Offset 'y' of array assigned to variable \$a is never used.",
				95,
			],
			[
				"Offset 'y' of array assigned to variable \$a is never used.",
				103,
			],
			[
				"Offset 'z' of array assigned to variable \$a is never used.",
				117,
			],
			[
				"Offset 'x' of array assigned to variable \$a is never used.",
				124,
			],
			[
				'Offset 2 of array assigned to variable $a is never used.',
				136,
			],
			[
				'Offset 6 of array assigned to variable $a is never used.',
				142,
			],
			[
				"Offset 'x' of array assigned to variable \$a is never used.",
				149,
			],
			[
				'Value assigned to variable $v is never used.',
				156,
			],
			[
				"Offset 'x' of array assigned to variable \$a is never used.",
				157,
			],
			[
				"Offset 'y' of array assigned to variable \$a is never used.",
				163,
			],
			[
				"Offset 'y' of array assigned to variable \$a is never used.",
				169,
			],
			[
				"Offset 'y' of array assigned to variable \$a is never used.",
				175,
			],
			[
				"Value assigned to \$a['x'] is never used.",
				191,
			],
			[
				"Value assigned to \$a['x'] is never used.",
				211,
			],
			[
				"Value assigned to \$a['x'] is never used.",
				219,
			],
			[
				'Value assigned to $a[$i] is never used.',
				234,
			],
			[
				'Value assigned to $a[] is never used.',
				247,
			],
			[
				"Value assigned to \$a['x']['y'] is never used.",
				260,
			],
			[
				"Offset 'x' of array assigned to variable \$a is never used.",
				280,
			],
			[
				"Value assigned to \$a['x'] is never used.",
				295,
			],
			[
				"Value assigned to \$a['n'] is never used.",
				315,
			],
			[
				'Value assigned to $s[0] is never used.',
				328,
			],
			[
				"Value assigned to \$a['x'] is never used.",
				356,
			],
			[
				"Value assigned to \$a['x'] is never used.",
				380,
			],
			[
				"Value assigned to \$a['y'] is never used.",
				380,
			],
			[
				"Value assigned to \$a['x'] is never used.",
				393,
			],
			[
				"Value assigned to \$p['x'] is never used.",
				399,
			],
			[
				"Value assigned to \$a['x'] is never used.",
				412,
			],
			[
				"Value assigned to \$a['x'] is never used.",
				430,
			],
			[
				'Value assigned to variable $a is never used.',
				444,
			],
			[
				'Value assigned to variable $a is never used.',
				450,
			],
			[
				"Offset 'x' of array assigned to variable \$a is never used.",
				474,
			],
			[
				"Value assigned to \$a['x'] is never used.",
				482,
			],
			[
				"Offset 'y' of array assigned to variable \$a is never used.",
				510,
			],
			[
				"Offset 'y' of array assigned to variable \$a is never used.",
				539,
			],
			[
				'Value assigned to variable $v is never used.',
				565,
			],
			[
				"Value assigned to \$cache['x'] is never used.",
				565,
			],
		]);
	}

	public function testBug12789(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12789.php'], [
			[
				'Value assigned to variable $RetVal is never used.',
				12,
			],
		]);
	}

	public function testBug13472(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13472.php'], [
			[
				'Value assigned to variable $v is never used.',
				14,
			],
			[
				'Value assigned to variable $item is never used.',
				41,
			],
		]);
	}

	public function testBug14258(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14258.php'], [
			[
				'Value assigned to variable $cutsomerId is never used.',
				15,
			],
		]);
	}

	public function testBug12012(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12012.php'], [
			[
				'Value assigned to variable $s1 is never used.',
				9,
			],
			[
				'Value assigned to variable $s1 is never used.',
				10,
			],
			[
				'Value assigned to variable $s1 is never used.',
				12,
			],
		]);
	}

	public function testBug11483(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11483.php'], [
			[
				'Value assigned to variable $hello is never used.',
				9,
			],
		]);
	}

	public function testBug10202(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10202.php'], [
			[
				'Value assigned to variable $x is never used.',
				9,
			],
			[
				'Value assigned to variable $x is never used.',
				12,
			],
			[
				'Value assigned to variable $x is never used.',
				14,
			],
		]);
	}

}

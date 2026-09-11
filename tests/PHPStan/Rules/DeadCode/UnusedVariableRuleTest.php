<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<UnusedVariableRule>
 */
class UnusedVariableRuleTest extends RuleTestCase
{

	private bool $polluteScopeWithAlwaysIterableForeach = true;

	protected function shouldPolluteScopeWithAlwaysIterableForeach(): bool
	{
		return $this->polluteScopeWithAlwaysIterableForeach;
	}

	protected function getRule(): Rule
	{
		return new UnusedVariableRule(self::getContainer()->getByType(ExprPrinter::class));
	}

	public function testThrowableCatchAfterDocumentedException(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-throwable-catch.php'], []);
	}

	public function testOverridingThrowsReachesCatch(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-overriding-throws.php'], [
			[
				'Value assigned to variable $before is never read.',
				13,
			],
			[
				'Value assigned to variable $before is never read.',
				24,
			],
			[
				'Value assigned to variable $before is never read.',
				32,
			],
			[
				'Value assigned to variable $before is never read.',
				43,
			],
			[
				'Value assigned to variable $before is never read.',
				82,
			],
			[
				'Variable $before is never read.',
				93,
			],
			[
				'Variable $before is never read.',
				95,
			],
		]);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable.php'], [
			['Variable $a is never read.', 27],
			['Value assigned to variable $a is never read.', 32],
			['Variable $a is never read.', 40],
			['Value assigned to variable $x is never read.', 46],
			['Variable $a is never read.', 70],
			['Variable $a is never read.', 76],
			['Variable $a is never read.', 93],
			['Variable $a is never read.', 95],
			['Variable $a is never read.', 101],
			['Variable $a is never read.', 113],
			['Foreach key variable $k is never read.', 119],
			['Foreach value variable $v is never read.', 126],
			['Foreach value variable $v is never read.', 133],
			['Variable $a is never read.', 148],
			['Value of variable $i after ++ is never read.', 157],
			['Variable $x is never read.', 223],
			['Value assigned to $x[] is never read.', 250],
			['Value assigned to $x[\'k\'] is never read.', 251],
			['Value assigned to variable $s only flows into values that are never used.', 263],
			['Value assigned to variable $s is never read.', 264],
			['Variable $a is never read.', 269],
			['Variable $a is never read.', 276],
			['Variable $f is never read.', 283],
			['Variable $a is never read.', 303],
			['Variable $x is never read.', 337],
			['Value assigned to variable $title is never read.', 422],
			['Variable $b is never read.', 614],
			['Variable $a is never read.', 632],
			['Variable $a is never read.', 637],
			['Value assigned to variable $a only flows into values that are never used.', 702],
			['Value assigned to variable $a is never read.', 703],
			['Value assigned to variable $b only flows into values that are never used.', 709],
			['Value assigned to variable $b only flows into values that are never used.', 719],
			['Variable $a is never read.', 739],
			['Value assigned to variable $a is never read.', 744],
			['Value assigned to variable $tags is never read.', 840],
			['Value of variable $i after -- is never read.', 864],
			['Value assigned to variable $x is never read.', 870],
			['Foreach value variable $v is never read.', 877],
			['Value of variable $i after ++ is never read.', 885],
			['Value of variable $i after -- is never read.', 892],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testPhp8(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-php8.php'], [
			['Catch variable $e is never read.', 23],
			['Value assigned to variable $nightsFrom is never read.', 98],
			['Value assigned to variable $a only flows into values that are never used.', 107],
			['Variable $b is never read.', 108],
			['Variable $b is never read.', 116],
		]);
	}

	public function testBug12789(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12789.php'], [
			[
				'Variable $RetVal is never read.',
				12,
			],
		]);
	}

	public function testBug13472(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13472.php'], [
			[
				'Value assigned to variable $v is never read.',
				14,
			],
			[
				'Foreach value variable $item is never read.',
				41,
			],
		]);
	}

	public function testBug14258(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14258.php'], [
			[
				'Variable $cutsomerId is never read.',
				15,
			],
		]);
	}

	public function testBug12012(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12012.php'], [
			['Value assigned to variable $s1 only flows into values that are never used.', 9],
			['Value assigned to variable $s1 is never read.', 10],
			['Value assigned to variable $s1 is never read.', 12],
		]);
	}

	public function testBug11483(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11483.php'], [
			[
				'Value assigned to variable $hello is never read.',
				9,
			],
		]);
	}

	public function testBug10202(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10202.php'], [
			[
				'Variable $x is never read.',
				9,
			],
			[
				'Variable $x is never read.',
				12,
			],
			[
				'Variable $x is never read.',
				14,
			],
		]);
	}

	#[RequiresPhp('< 8.0.0')]
	public function testCatchVariableNotReportedBeforePhp80(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-catch.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testCatchVariableReportedSincePhp80(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-catch.php'], [
			[
				'Catch variable $e is never read.',
				9,
			],
		]);
	}

	public function testRedundantAssignment(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-redundant.php'], [
			['Variable $x is assigned value true but it already has that value.', 26],
			['Value assigned to variable $x is never read.', 42],
			['Variable $x is assigned value 1 but it already has that value.', 43],
			['Variable $x is assigned value null but it already has that value.', 51],
			['Variable $s is assigned value \'a\' but it already has that value.', 60],
			['Variable $a is assigned value array{k: 1} but it already has that value.', 69],
			['Value assigned to variable $x is never read.', 95],
			['Variable $x is assigned value 1 but it already has that value.', 118],
		]);
	}

	public function testByRefReturn(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-by-ref-return.php'], [
			[
				'Value assigned to variable $x is never read.',
				26,
			],
			[
				'Variable $unused is never read.',
				32,
			],
			[
				'Value assigned to variable $x is never read.',
				68,
			],
		]);
	}

	public function testDeadBranchWrites(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-dead-branch.php'], [
			[
				'Value assigned to variable $x is never read.',
				79,
			],
		]);
	}

	public function testResultFlow(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-result-flow.php'], [
			['Value assigned to variable $shadowed is never read.', 15],
			['Value assigned to variable $value is never read.', 16],
			['Value assigned to variable $value is never read.', 73],
			['Value assigned to variable $value is never read.', 98],
			['Value assigned to variable $value is never read.', 128],
			['Variable $value is never read.', 136],
			['Value assigned to variable $value is never read.', 148],
			['Value assigned to variable $value is never read.', 160],
			['Value assigned to variable $value is never read.', 167],
		]);
	}

	public function testForeachWithoutPollution(): void
	{
		$this->polluteScopeWithAlwaysIterableForeach = false;
		$this->analyse([__DIR__ . '/data/unused-variable-foreach-pollution.php'], []);
	}

	public function testUsageFlow(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-usage-flow.php'], []);
	}

	public function testNestedOffsetWrites(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-nested-offset-writes.php'], []);
	}

	public function testArrowReferenceParameters(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-arrow-reference.php'], [
			['Value assigned to variable $value is never read.', 14],
		]);
	}

	public function testBroadCatchesIncludeImplicitThrows(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-broad-catch.php'], [
			['Value assigned to variable $file is never read.', 71],
		]);
	}

	public function testValueFlow(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-value-flow.php'], [
			['Value assigned to variable $a only flows into values that are never used.', 27],
			['Value assigned to variable $a is never read.', 28],
			['Value assigned to variable $s only flows into values that are never used.', 40],
			['Value assigned to variable $s only flows into values that are never used.', 41],
			['Value assigned to variable $s is never read.', 42],
			['Value assigned to variable $i only flows into values that are never used.', 54],
			['Value of variable $i after ++ only flows into values that are never used.', 55],
			['Value of variable $i after ++ only flows into values that are never used.', 56],
			['Value of variable $i after -- only flows into values that are never used.', 57],
			['Value of variable $i after -- is never read.', 58],
			['Value of variable $i after ++ is never read.', 71],
			['Value assigned to variable $i only flows into values that are never used.', 76],
			['Value of variable $i after ++ is never read.', 77],
			['Variable $j is never read.', 77],
			['Value assigned to variable $n only flows into values that are never used.', 98],
			['Value assigned to variable $n only flows into values that are never used.', 100],
			['Value assigned to variable $a only flows into values that are never used.', 117],
			['Variable $ok is never read.', 118],
			['Value assigned to variable $a only flows into values that are never used.', 130],
			['Variable $b is never read.', 131],
			['Variable $b is never read.', 144],
			['Variable $b is never read.', 150],
			['Value assigned to variable $a only flows into values that are never used.', 155],
			['Variable $arr is never read.', 156],
			['Value assigned to variable $a only flows into values that are never used.', 168],
			['Variable $b is never read.', 169],
			['Variable $c is never read.', 170],
			['Value assigned to variable $a only flows into values that are never used.', 175],
			['Variable $b is never read.', 176],
			['Variable $c is never read.', 177],
			['Variable $d is never read.', 178],
			['Value assigned to variable $a only flows into values that are never used.', 183],
			['Variable $b is never read.', 184],
			['Value assigned to variable $a only flows into values that are never used.', 189],
			['Variable $b is never read.', 190],
			['Value assigned to variable $d only flows into values that are never used.', 195],
			['Variable $b is never read.', 196],
			['Variable $b is never read.', 202],
			['Variable $b is never read.', 209],
			['Variable $d is never read.', 210],
			['Variable $b is never read.', 216],
			['Variable $b is never read.', 222],
			['Variable $b is never read.', 228],
			['Variable $b is never read.', 234],
			['Variable $f is never read.', 240],
			['Variable $f is never read.', 248],
			['Variable $b is never read.', 254],
			['Variable $b is never read.', 260],
			['Variable $b is never read.', 266],
			['Variable $b is never read.', 272],
			['Variable $b is never read.', 278],
			['Variable $b is never read.', 284],
			['Variable $b is never read.', 289],
			['Variable $a is never read.', 295],
			['Variable $b is never read.', 302],
			['Value assigned to variable $c only flows into values that are never used.', 308],
			['Variable $b is never read.', 309],
			['Variable $a is never read.', 309],
			['Variable $b is never read.', 315],
			['Value assigned to variable $a only flows into values that are never used.', 321],
			['Variable $b is never read.', 322],
			['Value assigned to variable $v only flows into values that are never used.', 329],
			['Value assigned to $a[\'x\'] is never read.', 331],
			['Value assigned to variable $a only flows into values that are never used.', 344],
			['Variable $b is never read.', 345],
			['Value assigned to variable $i only flows into values that are never used.', 357],
			['Value assigned to variable $a only flows into values that are never used.', 358],
			['Variable $b is never read.', 359],
			['Variable $a is never read.', 364],
			['Value assigned to variable $a only flows into values that are never used.', 369],
			['Value assigned to variable $b only flows into values that are never used.', 370],
			['Value assigned to variable $c only flows into values that are never used.', 371],
			['Variable $d is never read.', 372],
			['Value assigned to variable $s only flows into values that are never used.', 395],
			['Foreach value variable $v only flows into values that are never used.', 396],
			['Value assigned to variable $s only flows into values that are never used.', 397],
			['Value assigned to variable $a only flows into values that are never used.', 403],
			['Value assigned to variable $a is never read.', 404],
			['Value assigned to variable $s is never read.', 477],
			['Value assigned to variable $s only flows into values that are never used.', 483],
			['Value assigned to variable $s is never read.', 484],
			['Variable $x is never read.', 484],
		]);
	}

	public function testOffsets(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-offsets.php'], [
			['Offset \'x\' of array assigned to variable $a is never read.', 27],
			['Variable $a is never read.', 39],
			['Offset \'x\' of array assigned to variable $a is never read.', 44],
			['Offset 1 of array assigned to variable $a is never read.', 50],
			['Offset 2 of array assigned to variable $a is never read.', 50],
			['Offset \'y\' of array assigned to variable $a is never read.', 58],
			['Offset \'x\' of array assigned to variable $a is never read.', 67],
			['Offset \'y\' of array assigned to variable $a is never read.', 95],
			['Offset \'y\' of array assigned to variable $a is never read.', 103],
			['Offset \'z\' of array assigned to variable $a is never read.', 117],
			['Offset \'x\' of array assigned to variable $a is never read.', 124],
			['Offset 2 of array assigned to variable $a is never read.', 136],
			['Offset 6 of array assigned to variable $a is never read.', 142],
			['Offset \'x\' of array assigned to variable $a is never read.', 149],
			['Value assigned to variable $v only flows into values that are never used.', 156],
			['Offset \'x\' of array assigned to variable $a is never read.', 157],
			['Offset \'y\' of array assigned to variable $a is never read.', 163],
			['Offset \'y\' of array assigned to variable $a is never read.', 169],
			['Offset \'y\' of array assigned to variable $a is never read.', 175],
			['Value assigned to $a[\'x\'] is never read.', 191],
			['Value assigned to $a[\'x\'] is never read.', 211],
			['Value assigned to $a[\'x\'] is never read.', 219],
			['Value assigned to $a[$i] is never read.', 234],
			['Value assigned to $a[] is never read.', 247],
			['Value assigned to $a[\'x\'][\'y\'] is never read.', 260],
			['Offset \'x\' of array assigned to variable $a is never read.', 280],
			['Value assigned to variable $a only flows into values that are never used.', 294],
			['Value assigned to $a[\'x\'] is never read.', 295],
			['Value assigned to variable $a only flows into values that are never used.', 314],
			['Value of $a[\'n\'] after ++ is never read.', 315],
			['Value assigned to $s[0] is never read.', 328],
			['Value assigned to $a[\'x\'] is never read.', 356],
			['Value assigned to $a[\'x\'] is never read.', 380],
			['Value assigned to $a[\'y\'] is never read.', 380],
			['Foreach value $a[\'x\'] is never read.', 393],
			['Value assigned to $p[\'x\'] is never read.', 399],
			['Value assigned to $a[\'x\'] is never read.', 412],
			['Value assigned to $a[\'x\'] is never read.', 430],
			['Variable $a is never read.', 444],
			['Value assigned to variable $a is never read.', 450],
			['Offset \'x\' of array assigned to variable $a is never read.', 474],
			['Value assigned to $a[\'x\'] is never read.', 482],
			['Offset \'y\' of array assigned to variable $a is never read.', 510],
			['Offset \'y\' of array assigned to variable $a is never read.', 539],
			['Value assigned to $cache[\'x\'] is never read.', 565],
			['Variable $v is never read.', 565],

			['Offset \'x\' of array assigned to variable $a only flows into values that are never used.', 570],
			['Variable $b is never read.', 571],
		]);
	}

	public function testDynamicOffsetOverwritten(): void
	{
		$errors = $this->gatherAnalyserErrors([__DIR__ . '/data/unused-variable-offset-overwrite.php']);
		$this->assertCount(1, $errors);
		$this->assertSame('Value assigned to $a[$i] is never read.', $errors[0]->getMessage());
		$this->assertSame('assign.unused', $errors[0]->getIdentifier());
		$this->assertSame(8, $errors[0]->getLine());
	}

	public function testUnsetCallsDestructor(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-destructor.php'], [
			['Variable $unused is never read.', 33],
		]);
	}

	public function testFlowMessages(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-flow-messages.php'], [
			['Value assigned to variable $a only flows into values that are never used.', 18],
			['Value assigned to variable $a only flows into values that are never used.', 20],
			['Value assigned to variable $s only flows into values that are never used.', 26],
			['Value assigned to variable $s only flows into values that are never used.', 28],
			['Offset \'k\' of array assigned to variable $a only flows into values that are never used.', 34],
			['Value assigned to $a[\'k\'] only flows into values that are never used.', 36],
			['Value assigned to variable $a only flows into values that are never used.', 42],
			['Value assigned to variable $a only flows into values that are never used.', 44],
			['Value assigned to variable $i only flows into values that are never used.', 50],
			['Value of variable $i after ++ only flows into values that are never used.', 52],
			['Value assigned to variable $i only flows into values that are never used.', 58],
			['Value of variable $i after ++ only flows into values that are never used.', 60],
			['Value assigned to variable $i only flows into values that are never used.', 66],
			['Value of variable $i after -- only flows into values that are never used.', 68],
			['Value assigned to variable $i only flows into values that are never used.', 74],
			['Value of variable $i after -- only flows into values that are never used.', 76],
			['Foreach key variable $k only flows into values that are never used.', 83],
			['Foreach value variable $v only flows into values that are never used.', 83],
			['Value assigned to variable $v only flows into values that are never used.', 85],
			['Value assigned to variable $k only flows into values that are never used.', 86],
			['Offset \'x\' of array assigned to variable $a only flows into values that are never used.', 93],
			['Value assigned to $a[\'x\'] only flows into values that are never used.', 95],
			['Value assigned to variable $a only flows into values that are never used.', 102],
			['Variable $b is never read.', 103],
			['Value assigned to variable $a only flows into values that are never used.', 108],
			['Value assigned to variable $a only flows into values that are never used.', 109],
			['Value assigned to variable $a is never read.', 110],
			['Value assigned to variable $c only flows into values that are never used.', 115],
			['Variable $a is never read.', 116],
			['Variable $b is never read.', 116],
			['Value assigned to variable $v only flows into values that are never used.', 121],
			['Offset \'x\' of array assigned to variable $a is never read.', 122],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testFlowMessagesCatch(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-flow-messages-catch.php'], [
			['Catch variable $e only flows into values that are never used.', 15],
			['Value assigned to variable $e only flows into values that are never used.', 17],
			['Catch variable $e only flows into values that are never used.', 26],
			['Variable $copy is never read.', 27],
		]);
	}

}

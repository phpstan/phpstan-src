<?php declare(strict_types = 1);

namespace UnusedVariableValueFlow;

/** @phpstan-impure */
function cond(): bool
{
	return (bool) rand(0, 1);
}

/**
 * @phpstan-impure
 * @return mixed
 */
function source()
{
	return rand(0, 1);
}

/** @param mixed $v */
function sink($v): void
{
}

function chainNeverSunk(): void
{
	$a = 5; // unused $a
	$a = $a + 1; // unused $a
}

function chainSunk(): void
{
	$a = 5;
	$a = $a + 1;
	sink($a);
}

function concatChain(): void
{
	$s = 'a'; // unused $s
	$s .= 'b'; // unused $s
	$s = $s . 'c'; // unused $s
}

function concatChainSunk(): string
{
	$s = 'a';
	$s .= 'b';
	return $s;
}

function incrementChain(): void
{
	$i = 0; // unused $i
	$i++; // unused $i
	++$i; // unused $i
	$i--; // unused $i
	--$i; // unused $i
}

function incrementChainSunk(): int
{
	$i = 0;
	$i++;
	return $i;
}

function incrementConsumedBySink(): void
{
	$i = 0;
	sink($i++); // unused $i
}

function incrementIntoAssignment(): void
{
	$i = 0; // unused $i
	$j = $i++; // unused $j, $i
}

function incrementIntoAssignmentSunk(): void
{
	$i = 0;
	$j = $i++;
	sink($j);
	sink($i);
}

function loopCounterSunkByCondition(): void
{
	$i = 0;
	while ($i < 10) {
		$i = $i + 1;
	}
}

function loopAccumulatorUnused(): void
{
	$n = 0; // unused $n
	while (cond()) {
		$n = $n + 1; // unused $n
	}
}

function loopAccumulatorSunkByBreak(): void
{
	$n = 0;
	while (cond()) {
		$n = $n + 1;
		if ($n > 3) {
			break;
		}
	}
}

function comparisonResultUnused(): void
{
	$a = source(); // unused $a
	$ok = $a === 1; // unused $ok
}

function comparisonResultSunk(): void
{
	$a = source();
	$ok = $a === 1;
	sink($ok);
}

function ternaryBranchFlow(): void
{
	$a = source(); // unused $a
	$b = cond() ? $a : 0; // unused $b
}

function ternaryBranchFlowSunk(): void
{
	$a = source();
	$b = cond() ? $a : 0;
	sink($b);
}

function ternaryConditionIsSink(): void
{
	$a = source();
	$b = $a ? 1 : 0; // unused $b
}

function shortTernaryConditionIsSink(): void
{
	$a = source();
	$b = $a ?: 1; // unused $b
}

function arrayLiteralFlow(): void
{
	$a = source(); // unused $a
	$arr = [$a, 'k' => $a]; // unused $arr
}

function arrayLiteralFlowSunk(): void
{
	$a = source();
	$arr = [$a];
	sink($arr);
}

function castFlow(): void
{
	$a = source(); // unused $a
	$b = (int) $a; // unused $b
	$c = (string) $a; // unused $c
}

function unaryFlow(): void
{
	$a = 1; // unused $a
	$b = -$a; // unused $b
	$c = !$a; // unused $c
	$d = ~$a; // unused $d
}

function interpolationFlow(): void
{
	$a = 'x'; // unused $a
	$b = "v: $a"; // unused $b
}

function errorSuppressFlow(): void
{
	$a = source(); // unused $a
	$b = @$a; // unused $b
}

function coalesceRightSideFlow(): void
{
	$d = 1; // unused $d
	$b = source() ?? $d; // unused $b
}

function coalesceLeftSideIsSink(): void
{
	$a = source();
	$b = $a ?? 1; // unused $b
}

function booleanOperandsAreSinks(): void
{
	$a = cond();
	$c = cond();
	$b = $a && $c; // unused $b
	$d = $a || $c; // unused $d
}

function callArgumentIsSink(): void
{
	$a = 'x';
	$b = strlen($a); // unused $b
}

function methodReceiverIsSink(): void
{
	$o = new \ArrayObject([]);
	$b = $o->count(); // unused $b
}

function propertyReceiverIsSink(): void
{
	$o = new \stdClass();
	$b = $o->foo ?? null; // unused $b
}

function newArgumentIsSink(): void
{
	$a = [];
	$b = new \ArrayObject($a); // unused $b
}

function closureUseIsSink(): void
{
	$a = 1;
	$f = function () use ($a): int { // unused $f
		return $a;
	};
}

function arrowFunctionCaptureIsSink(): void
{
	$a = 1;
	$f = fn (): int => $a; // unused $f
}

function instanceofIsSink(): void
{
	$o = source();
	$b = $o instanceof \stdClass; // unused $b
}

function printIsSink(): void
{
	$a = 'x';
	$b = print $a; // unused $b
}

function yieldIsSink(): iterable
{
	$a = 1;
	$b = yield $a; // unused $b
}

function cloneIsSink(): void
{
	$o = new \stdClass();
	$b = clone $o; // unused $b
}

function issetIsSink(): void
{
	$a = source();
	$b = isset($a); // unused $b
}

function emptyIsSink(): void
{
	$a = source();
	$b = empty($a); // unused $b
}

function nestedAssignOuterSunk(): void
{
	$a = $b = source(); // unused $b
	sink($a);
}

function nestedAssignInnerSunk(): void
{
	$a = $b = source(); // unused $a
	sink($b);
}

function nestedAssignValueFlowsToOuter(): void
{
	$c = source();
	$a = $b = $c + 1; // unused $b
	sink($a);
}

function nestedAssignAllUnused(): void
{
	$c = source(); // unused $c
	$a = $b = $c + 1; // unused $a, $b
}

function readInFlowThenSunk(): void
{
	$a = 1;
	$b = $a + 1; // unused $b
	sink($a);
}

function flowThenOverwrite(): void
{
	$a = 1; // unused $a
	$b = $a + 1; // unused $b
	$a = 2;
	sink($a);
}

function flowIntoArrayOffsetWrite(): void
{
	$v = source(); // unused $v
	$a = [];
	$a['x'] = $v; // unused $a['x']
}

function flowIntoArrayOffsetWriteSunk(): void
{
	$v = source();
	$a = [];
	$a['x'] = $v;
	sink($a);
}

function offsetReadFlow(): void
{
	$a = ['k' => 1]; // unused $a
	$b = $a['k']; // unused $b
}

function offsetReadFlowSunk(): void
{
	$a = ['k' => 1];
	$b = $a['k'];
	sink($b);
}

function dimensionFlow(): void
{
	$i = 0; // unused $i
	$a = source(); // unused $a
	$b = $a[$i]; // unused $b
}

function parameterInFlow(int $p): void
{
	$a = $p + 1; // unused $a
}

function flowThroughSeveralVariables(): void
{
	$a = 1; // unused $a
	$b = $a * 2; // unused $b
	$c = $b + $a; // unused $c
	$d = $c; // unused $d
}

function flowThroughSeveralVariablesSunk(): void
{
	$a = 1;
	$b = $a * 2;
	$c = $b + $a;
	$d = $c;
	sink($d);
}

function flowInLoopSunkAfter(): void
{
	$s = '';
	foreach ([1, 2] as $v) {
		$s = $s . $v;
	}
	sink($s);
}

function flowInLoopNeverSunk(): void
{
	$s = ''; // unused $s
	foreach ([1, 2] as $v) { // unused $v
		$s = $s . $v; // unused $s
	}
}

function chainFedByFunctionCallIsStillUnused(): void
{
	$a = source(); // unused $a
	$a = $a + 1; // unused $a
}

function chainKeptAliveByReference(): void
{
	$a = 1;
	$r = &$a;
	$a = $a + 1;
	sink($r);
}

function compactReadsChain(): array
{
	$a = 1;
	$a = $a + 1;
	return compact('a');
}

function returnIsSink(): int
{
	$a = 1;
	$a = $a + 1;
	return $a;
}

function throwIsSink(): void
{
	$m = 'x';
	$m = $m . 'y';
	throw new \RuntimeException($m);
}

function echoIsSink(): void
{
	$a = 1;
	$a = $a + 1;
	echo $a;
}

function ifConditionIsSink(): void
{
	$a = 1;
	$a = $a + 1;
	if ($a > 1) {
		sink(1);
	}
}

function propertyWriteIsSink(\stdClass $o): void
{
	$a = 1;
	$a = $a + 1;
	$o->x = $a;
}

function staticPropertyWriteIsSink(): void
{
	$a = 1;
	$a = $a + 1;
	Holder::$x = $a;
}

class Holder
{

	/** @var int */
	public static $x = 0;

}

function assignOpValueFlowsToOuter(): void
{
	$s = '';
	$x = ($s .= 'a'); // unused $s
	sink($x);
}

function assignOpValueFlowsToOuterUnused(): void
{
	$s = ''; // unused $s
	$x = ($s .= 'a'); // unused $x, $s
}

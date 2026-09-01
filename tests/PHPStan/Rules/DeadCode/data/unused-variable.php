<?php

namespace UnusedVariableRule;

/** @phpstan-impure */
function cond(): bool
{
	return (bool) rand(0, 1);
}

/** @param mixed $v */
function sink($v): void
{
}

/**
 * @return mixed
 * @phpstan-impure
 */
function source()
{
	return rand();
}

function simpleUnused(): void
{
	$a = 1; // unused $a
}

function overwrittenBeforeRead(): void
{
	$a = 1; // unused $a
	$a = 2;
	sink($a);
}

function writtenOnOneBranchNeverRead(): void
{
	if (cond()) {
		$a = 1; // unused $a
	}
}

function nullInitOverwritten(): void
{
	$x = null; // unused $x
	$x = source();
	sink($x);
}

function nullInitConditionallyOverwritten(): void
{
	$x = null;
	if (cond()) {
		$x = source();
	}
	sink($x);
}

function readOnOneBranch(): void
{
	$a = 1;
	if (cond()) {
		sink($a);
	}
}

function writeThenThrow(): void
{
	$a = 1; // unused $a
	throw new \Exception();
}

function writeThenExit(): void
{
	$a = 1; // unused $a
	exit(1);
}

function bothBranchesWriteThenRead(): void
{
	if (cond()) {
		$a = 1;
	} else {
		$a = 2;
	}
	sink($a);
}

function bothBranchesWriteNeverRead(): void
{
	if (cond()) {
		$a = 1; // unused $a
	} else {
		$a = 2; // unused $a
	}
}

function destructuringPartiallyUsed(): void
{
	[$a, $b] = [1, 2]; // unused $a
	sink($b);
}

function destructuringSkipped(): void
{
	[, $b] = [1, 2];
	sink($b);
}

function listPartiallyUsed(): void
{
	list($a, $b) = [1, 2]; // unused $a
	sink($b);
}

function foreachKeyUnused(array $arr): void
{
	foreach ($arr as $k => $v) { // unused $k
		sink($v);
	}
}

function foreachValueUnused(array $arr): void
{
	foreach ($arr as $k => $v) { // unused $v
		sink($k);
	}
}

function foreachValueOnlyUnused(array $arr): void
{
	foreach ($arr as $v) { // unused $v
		sink(1);
	}
}

function foreachListUsed(array $arr): void
{
	foreach ($arr as [$a, $b]) {
		sink($a);
		sink($b);
	}
}

function foreachListPartiallyUsed(array $arr): void
{
	foreach ($arr as [$a, $b]) { // unused $a
		sink($b);
	}
}

function incrementLast(): void
{
	$i = 0;
	sink($i);
	$i++; // unused $i
}

function forLoop(): void
{
	for ($i = 0; $i < 3; $i++) {
		sink($i);
	}
}

function forLoopCounterOnlyInCondition(): void
{
	for ($i = 0; $i < 3; $i++) {
		sink(1);
	}
}

function whileAssignInCondition(): void
{
	while (($line = source()) !== false) {
		sink($line);
	}
}

function doWhile(): void
{
	$i = 5;
	do {
		sink(1);
	} while (--$i > 0);
	sink($i);
}

function doWhileNoReadAfter(): void
{
	$i = 5;
	do {
		sink(1);
	} while (--$i > 0);
}

function backEdgeRead(): void
{
	$x = 0;
	while (cond()) {
		sink($x);
		$x = source();
	}
}

function backEdgeReadWithContinue(): void
{
	$x = 0;
	while (cond()) {
		if (cond()) {
			$x = 1;
			continue;
		}
		sink($x);
		$x = 2;
	}
}

function loopWriteNeverRead(): void
{
	while (cond()) {
		$x = source(); // unused $x
	}
}

function loopWriteReadNextIterationOnly(): void
{
	while (cond()) {
		if (cond()) {
			$x = 1;
		}
		if (isset($x)) {
			sink($x);
		}
	}
}

function arrayBuildReturned(): array
{
	$x = [];
	$x[] = 1;
	$x['k'] = 2;
	return $x;
}

function arrayBuildUnused(): void
{
	$x = [];
	$x[] = 1;
	$x['k'] = 2; // unused $x
}

function stringAppendReturned(): string
{
	$s = 'a';
	$s .= 'b';
	return $s;
}

function stringAppendUnused(): void
{
	$s = 'a';
	$s .= 'b'; // unused $s
}

function unsetAfterWrite(): void
{
	$a = 1; // known false negative: unset() walks the variable as a read
	unset($a);
}

function closureBodyDeadWrite(): void
{
	$f = function (): void {
		$a = 1; // unused $a
	};
	$f();
}

function closureAssignedUnused(): void
{
	$f = function (): void { // unused $f
	};
}

function switchBranchesRead(): void
{
	switch (rand(0, 2)) {
		case 0:
			$a = true;
			break;
		default:
			$a = false;
	}
	sink($a);
}

function switchBranchDeadWrite(): void
{
	switch (rand(0, 2)) {
		case 0:
			$a = 1; // unused $a
			break;
		default:
			sink(1);
	}
}

function tryFinallyRead(): void
{
	$var = '';
	try {
		if (cond()) {
			throw new \Exception();
		}
		$var = 'hello';
	} finally {
		sink($var);
	}
}

function tryCatchRead(): void
{
	try {
		$x = source();
	} catch (\Exception $e) {
		sink($e);
		$x = null;
	}
	sink($x);
}

function tryDeadWrite(): void
{
	try {
		$x = source(); // unused $x
	} catch (\Exception $e) {
		sink($e);
	}
}

function issetRead(): void
{
	if (cond()) {
		$j = 'hello';
	}
	if (isset($j)) {
		sink($j);
	}
}

function emptyRead(): void
{
	$j = source();
	if (empty($j)) {
		sink(1);
	}
}

function coalesceRead(): void
{
	$j = source();
	sink($j ?? 1);
}

function coalesceAssign(?int $b, int $c): void
{
	$b ??= $c;
	sink($b);
}

function compactRead(): array
{
	$a = 1;
	$b = 2;
	return compact('a', 'b');
}

function compactDynamic(string $name): array
{
	$a = 1;
	return compact($name);
}

function variableVariableRead(string $name): void
{
	$a = 1;
	sink($$name);
}

function variableVariableConstantRead(): void
{
	$a = 1;
	$name = 'a';
	sink(${$name});
}

function extractAfterWrite(array $arr): void
{
	$a = 1;
	extract($arr);
	sink($a);
}

function getDefinedVarsRead(): array
{
	$a = 1;
	return get_defined_vars();
}

function includeReadsEverything(): void
{
	$title = 'x';
	include 'template.php';
}

function includeThenOverwrite(): void
{
	$title = 'x';
	include 'template.php';
	$title = 'y'; // unused $title
}

function evalReadsEverything(): void
{
	$title = 'x';
	eval('echo $title;');
}

function gotoOpaque(): void
{
	$a = 1;
	goto end;
	end:
	sink(1);
}

function staticVar(): void
{
	static $token;
	$token = source();
}

function staticVarRead(): int
{
	static $token;
	if (!$token) {
		$token = rand(1, 10);
	}
	return $token;
}

function globalVar(): void
{
	global $a;
	$a = 'hello';
}

function byRefParam(array &$p): void
{
	$p = [0];
}

function pregMatchByRef(): array
{
	preg_match('/x/', 'x', $m);
	return $m;
}

function pregMatchByRefUnused(): void
{
	preg_match('/x/', 'x', $m);
}

function sortByRef(array $arr): void
{
	sort($arr);
}

function arrayPushByRef(): array
{
	$arr = [];
	array_push($arr, 1);
	return $arr;
}

function foreachByRef(array $a): array
{
	foreach ($a as &$v) {
		$v = 1;
	}
	return $a;
}

function assignRef(): void
{
	$b = 1;
	$a = &$b;
	$a = 2;
}

function closureUseByValue(): void
{
	$i = 0;
	$f = function () use ($i): int {
		return $i + 1;
	};
	$f();
}

function closureUseByRef(): void
{
	$i = 0;
	$f = function () use (&$i): void {
		$i = 1;
	};
	$f();
	sink($i);
}

function closureUseByRefNoReadAfter(): void
{
	$i = 0;
	$f = function () use (&$i): void {
		$i = 1;
	};
	$f();
}

function recursiveClosure(): void
{
	$f = function () use (&$f): void {
		$f();
	};
	$f();
}

function arrowFunctionCapture(): void
{
	$x = 1;
	$f = fn (): int => $x + 1;
	$f();
}

function dynamicMethodName(object $o): void
{
	$name = 'foo';
	$o->$name();
}

function dynamicClassConst(): void
{
	$class = \stdClass::class;
	sink($class::FOO);
}

function dynamicNew(): void
{
	$class = \stdClass::class;
	new $class();
}

function dynamicStaticProperty(): void
{
	$class = \stdClass::class;
	$class::$prop = 1;
}

function dimWriteOnObject(\ArrayAccess $o): void
{
	$o['k'] = 1;
}

function dimWriteOnObjectFromNew(): void
{
	$o = new \ArrayObject();
	$o['k'] = 1;
}

/** @param mixed $m */
function dimWriteOnMixed($m): void
{
	$m['k'] = 1;
}

function superglobalDimWrite(): void
{
	$_SESSION['k'] = 1;
}

function propertyWriteThroughLocal(): void
{
	$o = new \stdClass();
	$o->p = 1;
}

/** @param mixed $y */
function varAnnotation($y): void
{
	/** @var \stdClass $y */
	$y->m();
}

function varAnnotationAfterWrite(): void
{
	/** @var \stdClass $y */
	$y = source();
	$y->m();
}

function chainedAssign(): void
{
	$a = $b = 1; // unused $b
	sink($a);
}

function underscorePrefix(): void
{
	$_ = source();
	$_unused = source();
}

function parameterOverwritten($a): int
{
	$a = 1;
	return $a;
}

function parameterOverwrittenUnread($a): void
{
	$a = 1; // unused $a
}

function nestedFunctionScopes(): void
{
	$a = 1; // unused $a
	$f = function (): void {
		$a = 2;
		sink($a);
	};
	$f();
}

function ternaryRead(): int
{
	$a = source();
	return cond() ? $a : 0;
}

function instanceofRead(): bool
{
	$a = source();
	return $a instanceof \stdClass;
}

function usedAsArrayKey(): array
{
	$k = 'a';
	return [$k => 1];
}

function usedInStringInterpolation(): string
{
	$name = 'x';
	return "hello $name";
}

function yieldRead(): \Generator
{
	$a = 1;
	yield $a;
}

function throwRead(): void
{
	$e = new \Exception();
	throw $e;
}

function echoRead(): void
{
	$a = 1;
	echo $a;
}

function castRead(): int
{
	$a = '1';
	return (int) $a;
}

function cloneRead(): object
{
	$o = new \stdClass();
	return clone $o;
}

function selfReferentialChain(): void
{
	// the Psalm layer will also report the first write; phase 1 sees it read by the second
	$a = 5;
	$a = $a + 1; // unused $a
}

function articleExample(): void
{
	// Psalm reports every write of $b; phase 1 sees each read by the self-chain
	$b = $a = 0;
	while (cond()) {
		if (cond() && cond()) {
			$a = 5;
			break;
		}
		if (cond()) {
			continue;
		}
		$a = $a + 1;
		$b = $b + 1;
	}
	sink($a);
}

class Foo
{

	/** @var mixed */
	private $prop;

	public function __construct()
	{
		$x = 1;
		$this->prop = $x;
		$this->init();
	}

	private function init(): void
	{
		$a = 1; // unused $a
	}

	public function overwritten(): void
	{
		$a = 1; // unused $a
		$a = 2;
		sink($a);
	}

	public static function staticMethod(): void
	{
		$x = 1;
		self::helper($x);
	}

	/** @param mixed $x */
	private static function helper($x): void
	{
	}

	public function thisPropertyWrite(): void
	{
		$this->prop = 1;
	}

}

/**
 * @param list<array{int, string}> $tokens
 * @return list<array{comment: string|null}>
 */
function nestedWritesReadOnNextIteration(array $tokens): array
{
	$comment = null;
	$expected = null;
	$open = 0;
	$ids = [];
	foreach ($tokens as [$type, $content]) {
		if ($type === 1) {
			if ($open > 0) {
				$comment .= $content;
			}
			$open++;
			$expected = null;
			continue;
		}
		if ($type === 2) {
			$open--;
			if ($open === 0) {
				$key = array_key_last($ids);
				if ($key !== null) {
					$ids[$key]['comment'] = $comment;
					$comment = null;
				}
				$expected = [3, 4];
			} else {
				$comment .= $content;
			}
			continue;
		}
		if ($open > 0) {
			$comment .= $content;
			continue;
		}
		if ($expected !== null && !in_array($type, $expected, true)) {
			throw new \Exception();
		}
		$ids[] = ['comment' => null];
		$expected = [1];
	}

	return $ids;
}

/**
 * @param list<\stdClass> $xs
 */
function branchDeadInFirstIteration(array $xs): array
{
	$winners = [];
	$winning = null;
	foreach ($xs as $x) {
		if ($winning === null) {
			$winners[] = $x;
			$winning = $x;
		} else {
			$c = $winning == $x;
			if ($c) {
				$winners = [$x];
				$winning = $x;
			}
		}
	}

	return $winners;
}

function recursiveClosureReportsOnce(): void
{
	$check = function (int $n) use (&$check): void {
		$tags = []; // unused $tags
		if ($n > 0) {
			$tags = [$n];
			sink($tags);
		}
		$check($n - 1);
	};
	$check(3);
}

function closureBodyWritesStayInClosure(): void
{
	$outer = 1;
	$f = function () use ($outer): int {
		$inner = 2;
		return $outer + $inner;
	};
	sink($f());
}

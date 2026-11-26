<?php declare(strict_types = 1);

namespace LoopWideningFixpoint;

use function PHPStan\Testing\assertType;

/** @param list<string> $tokens */
function bracketDepth(array $tokens): void
{
	$depth = 0;
	foreach ($tokens as $t) {
		if ($t === '(') {
			$depth++;
		} elseif ($t === ')') {
			$depth--;
			if ($depth < -5) {
				throw new \LogicException();
			}
		}
		// both bounds move between passes, so both are opened; was the
		// unsound int<-4, 4> of the last pass the iteration limit reached
		assertType('int', $depth);
	}
}

function shiftChain(int $n): void
{
	$a = $b = $c = $d = $e = 'x';
	while ($n-- > 0) {
		$e = $d;
		$d = $c;
		$c = $b;
		$b = $a;
		$a = 'y';
	}
	assertType('literal-string&lowercase-string&non-falsy-string', $e);
}

function resetCounter(int $n): void
{
	$j = 0;
	while ($n-- > 0) {
		$j = $j + 1;
		if ($j > 100) {
			$j = 0;
		}
	}
	assertType('int<0, 100>', $j);
}

function forExit(): void
{
	for ($i = 0; $i < 10; $i++) {
	}
	assertType('10', $i);
}

/** @param list<int> $keys */
function growingKeySet(array $keys, bool $opt): void
{
	$validLengths = [0 => true];
	foreach ($keys as $value) {
		$newValidLengths = [];
		foreach (array_keys($validLengths) as $length) {
			if ($opt) {
				$newValidLengths[$length] = true;
			}
			if ($value === $length) {
				$newValidLengths[$length + 1] = true;
			}
		}
		$validLengths = $newValidLengths;
		if ($validLengths === []) {
			return;
		}
	}
	assertType('non-empty-array<int<0, max>, true>', $validLengths);
}

<?php declare(strict_types = 1);

namespace UnusedVariableOverwritten;

/** @phpstan-impure */
function cond(): bool
{
	return rand(0, 1) === 1;
}

/** @param mixed $v */
function sink($v): void
{
}

/** @return mixed */
function source()
{
	return rand();
}

function overwritten(): void
{
	$a = source(); // overwritten
	$a = source();
	sink($a);
}

function overwrittenOnOnePath(): void
{
	$a = source(); // overwritten on one path, never read on the other
	if (cond()) {
		$a = source();
		sink($a);
	}
}

function lastWrite(): void
{
	sink($a ?? null);
	$a = source(); // never read, nothing overwrites it
}

function arrowWriteDoesNotOverwrite(): void
{
	sink($a ?? null);
	$a = source(); // never read - the arrow function writes its own copy
	$f = fn () => $a = 2;
	sink($f);
}

function loopRunningTheSameWrite(array $items): void
{
	sink($a ?? null);
	foreach ($items as $item) {
		$a = $item; // never read - only the same write replaces it
	}
}

function overwrittenInLoop(array $items): void
{
	foreach ($items as $item) {
		$a = source(); // overwritten
		$a = $item;
		sink($a);
	}
}

function incrementOverwritten(): void
{
	$i = 0;
	$i++; // overwritten
	$i = 5;
	sink($i);
}

function foreachValueOverwritten(array $items): void
{
	foreach ($items as $v) { // overwritten
		$v = source();
		sink($v);
	}
}

function foreachKeyOverwritten(array $items): void
{
	foreach ($items as $k => $v) { // overwritten
		$k = source();
		sink([$k, $v]);
	}
}

function offsetOverwritten(): void
{
	$a = [];
	$a['x'] = 1; // overwritten
	$a['x'] = 2;
	sink($a);
}

function offsetOverwrittenByWholeVariable(): void
{
	$a = [];
	$a['x'] = 1; // overwritten
	$a = [];
	sink($a);
}

function offsetNotOverwrittenByOtherOffset(): void
{
	$a = [];
	$a['x'] = 1; // never read
	$a['y'] = 2;
	sink($a['y']);
}

function wholeVariableNotOverwrittenByOffset(): void
{
	$a = source(); // read - the offset write keeps the rest of the array
	$a['x'] = 1;
	sink($a);
}

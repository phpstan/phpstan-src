<?php declare(strict_types = 1);

namespace UnusedVariableOffsets;

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

function literalPartiallyRead(): void
{
	$a = ['x' => 1, 'y' => 2]; // unused offset 'x' of $a
	sink($a['y']);
}

function literalFullyRead(): void
{
	$a = ['x' => 1, 'y' => 2];
	sink($a);
}

function literalNeverRead(): void
{
	$a = ['x' => 1, 'y' => 2]; // unused $a
}

function literalMissingOffsetRead(): void
{
	$a = ['x' => 1]; // unused offset 'x' of $a
	sink($a['y'] ?? null);
}

function literalListPartiallyRead(): void
{
	$a = [1, 2, 3]; // unused offset 1 of $a, offset 2 of $a
	sink($a[0]);
}

function literalMultiLine(): void
{
	$a = [
		'x' => 1,
		'y' => 2, // unused offset 'y' of $a
		'z' => 3,
	];
	sink($a['x']);
	sink($a['z']);
}

function literalOffsetOverwritten(): void
{
	$a = ['x' => 1, 'y' => 2]; // unused offset 'x' of $a
	$a['x'] = 3;
	sink($a);
}

function literalDynamicOffsetRead(int $i): void
{
	$a = [1, 2];
	sink($a[$i]);
}

function literalIterated(): void
{
	$a = ['x' => 1];
	foreach ($a as $v) {
		sink($v);
	}
}

function literalCopiedThenOffsetRead(): void
{
	$a = ['x' => 1, 'y' => 2];
	$b = $a;
	sink($b['x']);
}

function literalIssetIsOffsetRead(): void
{
	$a = ['x' => 1, 'y' => 2]; // unused offset 'y' of $a
	if (isset($a['x'])) {
		sink(1);
	}
}

function literalEmptyIsOffsetRead(): void
{
	$a = ['x' => 1, 'y' => 2]; // unused offset 'y' of $a
	if (empty($a['x'])) {
		sink(1);
	}
}

function literalPassedToFunction(): void
{
	$a = ['x' => 1];
	sink(count($a));
}

function literalNestedOffsetRead(): void
{
	$a = ['x' => ['y' => 1], 'z' => 2]; // unused offset 'z' of $a
	sink($a['x']['y']);
}

/** @param array<string, int> $in */
function literalWithSpread(array $in): void
{
	$a = [...$in, 'x' => 1]; // unused offset 'x' of $a
	sink($a['y'] ?? null);
}

function literalWithUnknownKey(string $k): void
{
	$a = [$k => 1, 'x' => 2];
	sink($a['x']);
}

function literalNumericStringKey(): void
{
	$a = ['1' => 'a', 2 => 'b']; // unused offset 2 of $a
	sink($a[1]);
}

function literalImplicitIndexAfterExplicit(): void
{
	$a = [5 => 'a', 'b', 'c']; // unused offset 6 of $a
	sink($a[5]);
	sink($a[7]);
}

function literalOffsetUnset(): void
{
	$a = ['x' => 1, 'y' => 2]; // unused offset 'x' of $a
	unset($a['x']);
	sink($a);
}

function literalItemValueFlow(): void
{
	$v = source(); // unused $v
	$a = ['x' => $v, 'y' => 2]; // unused offset 'x' of $a
	sink($a['y']);
}

function literalInTernary(): void
{
	$a = cond() ? ['x' => 1] : ['x' => 2, 'y' => 3]; // unused offset 'y' of $a
	sink($a['x']);
}

function literalReturnedFromOffsetRead(): int
{
	$a = ['x' => 1, 'y' => 2]; // unused offset 'y' of $a
	return $a['x'];
}

function literalOffsetReadInLoop(): void
{
	$a = ['x' => 1, 'y' => 2]; // unused offset 'y' of $a
	while (cond()) {
		sink($a['x']);
	}
}

function literalAssignedToOffset(): void
{
	$a = [];
	$a['k'] = ['x' => 1, 'y' => 2];
	sink($a['k']['x']);
}

function dimWriteUnread(): void
{
	$a = [];
	$a['x'] = 1; // unused $a['x']
}

function dimWriteRead(): void
{
	$a = [];
	$a['x'] = 1;
	sink($a['x']);
}

function dimWriteWholeRead(): void
{
	$a = [];
	$a['x'] = 1;
	sink($a);
}

function dimWriteOverwritten(): void
{
	$a = [];
	$a['x'] = 1; // unused $a['x']
	$a['x'] = 2;
	sink($a);
}

function dimWriteOtherOffsetRead(): void
{
	$a = [];
	$a['x'] = 1; // unused $a['x']
	$a['y'] = 2;
	sink($a['y']);
}

function dimWriteDynamicKey(int $i): void
{
	$a = [];
	$a[$i] = 1;
	sink($a['x'] ?? null);
}

function dimWriteDynamicKeyUnread(int $i): void
{
	$a = [];
	$a[$i] = 1; // unused $a[$i]
}

function dimWriteAppendThenOffsetRead(): void
{
	$a = [];
	$a[] = 1;
	sink($a[0]);
}

function dimWriteAppendUnread(): void
{
	$a = [];
	$a[] = 1; // unused $a[]
}

function dimWriteNested(): void
{
	$a = [];
	$a['x']['y'] = 1;
	sink($a['x']);
}

function dimWriteNestedUnread(): void
{
	$a = [];
	$a['x']['y'] = 1; // unused $a['x']['y']
}

function dimWriteNestedExtendsLiteralOffset(): void
{
	$a = ['x' => ['y' => 1]];
	$a['x']['z'] = 2;
	sink($a);
}

function dimWriteNestedExtendsEarlierDimWrite(): void
{
	$a = [];
	$a['x'] = ['y' => 1];
	$a['x']['z'] = 2;
	sink($a);
}

function dimWriteReplacesLiteralOffset(): void
{
	$a = ['x' => 1]; // unused offset 'x' of $a
	$a['x'] = 2;
	sink($a['x']);
}

function dimWriteReadModifyWrite(): void
{
	$a = ['x' => 'a'];
	$a['x'] .= 'b';
	sink($a);
}

function dimWriteReadModifyWriteUnread(): void
{
	$a = ['x' => 'a'];
	$a['x'] .= 'b'; // unused $a['x']
}

function dimWriteCoalesceAssign(): void
{
	$a = [];
	$a['x'] ??= 1;
	sink($a);
}

function dimWriteIncrement(): void
{
	$a = ['n' => 0];
	$a['n']++;
	sink($a['n']);
}

function dimWriteIncrementUnread(): void
{
	$a = ['n' => 0];
	$a['n']++; // unused $a['n']
}

function stringOffsetWrite(): void
{
	$s = 'abc';
	$s[0] = 'x';
	sink($s);
}

function stringOffsetWriteUnread(): void
{
	$s = 'abc';
	$s[0] = 'x'; // unused $s[0]
}

function stringOffsetRead(): void
{
	$s = 'abc';
	sink($s[0]);
}

function dimWriteOnArrayAccessIsNotASite(\ArrayAccess $o): void
{
	$o['x'] = 1;
}

/** @param mixed $m */
function dimWriteOnMixedIsNotASite($m): void
{
	$m['x'] = 1;
}

function dimWriteWithoutInit(): void
{
	$a['x'] = 1;
	sink($a);
}

function dimWriteWithoutInitUnread(): void
{
	$a['x'] = 1; // unused $a['x']
}

function dimWriteInLoop(): void
{
	$a = [];
	foreach ([1, 2] as $v) {
		$a[$v] = $v;
	}
	sink($a);
}

function dimWriteInLoopOffsetReadAfter(): void
{
	$a = [];
	while (cond()) {
		$a['x'] = 1;
	}
	sink($a['x'] ?? null);
}

function dimWriteListTargets(): void
{
	$a = [];
	[$a['x'], $a['y']] = [1, 2]; // unused $a['x'], $a['y']
}

function dimWriteListTargetsRead(): void
{
	$a = [];
	[$a['x'], $a['y']] = [1, 2];
	sink($a);
}

function dimWriteForeachTarget(): void
{
	$a = [];
	foreach ([1, 2] as $a['x']) { // unused $a['x']
	}
}

function dimWriteOnParameter(array $p): void
{
	$p['x'] = 1; // unused $p['x']
}

function dimWriteOnParameterReturned(array $p): array
{
	$p['x'] = 1;

	return $p;
}

function dimWriteThenWholeOverwrite(): void
{
	$a = [];
	$a['x'] = 1; // unused $a['x']
	$a = [];
	sink($a);
}

function dimWriteInBranchRead(): void
{
	$a = [];
	if (cond()) {
		$a['x'] = 1;
	}
	sink($a['x'] ?? null);
}

function dimWriteInBranchUnread(): void
{
	$a = [];
	if (cond()) {
		$a['x'] = 1; // unused $a['x']
	}
	sink($a['y'] ?? null);
}

function offsetPassedByReference(): void
{
	$a = ['x' => [2, 1]];
	sort($a['x']);
	sink($a);
}

function unsetVariable(): void
{
	$a = 1; // unused $a
	unset($a);
}

function unsetThenReassign(): void
{
	$a = 1; // unused $a
	unset($a);
	$a = 2;
	sink($a);
}

function unsetAfterRead(): void
{
	$a = 1;
	sink($a);
	unset($a);
}

function unsetInBranch(): void
{
	$a = 1;
	if (cond()) {
		unset($a);
	}
	sink($a ?? null);
}

function unsetOffsetThenWholeRead(): void
{
	$a = ['x' => 1, 'y' => 2]; // unused offset 'x' of $a
	unset($a['x']);
	sink($a);
}

function unsetDimWriteOffset(): void
{
	$a = [];
	$a['x'] = 1; // unused $a['x']
	unset($a['x']);
	sink($a);
}

function unsetDynamicOffset(int $i): void
{
	$a = [1, 2];
	unset($a[$i]);
	sink($a);
}

function unsetNestedOffset(): void
{
	$a = ['x' => ['y' => 1]];
	unset($a['x']['y']);
	sink($a);
}

function unsetOffsetSelectedByOffsetRead(): void
{
	$a = ['x' => 1];
	unset($a[$a['x']]);
	sink($a);
}

function foreachOverOffset(): void
{
	$a = ['x' => [1, 2], 'y' => 3]; // unused offset 'y' of $a
	foreach ($a['x'] as $v) {
		sink($v);
	}
}

function offsetReadThroughCompact(): array
{
	$a = ['x' => 1, 'y' => 2];

	return compact('a');
}

function offsetsOfVariableVariableAreRead(): void
{
	$a = ['x' => 1, 'y' => 2];
	$name = 'a';
	sink($$name);
}

function literalOffsetsWhenTargetIsOffsetWriteAreNotTracked(): void
{
	$a = [];
	$a['k'] = ['x' => 1, 'y' => 2];
	sink($a['k']['x']);
}

function callOnOffset(): void
{
	$a = ['x' => static fn (): int => 1, 'y' => 2]; // unused offset 'y' of $a
	sink($a['x']());
}

function dimWriteCoalesceAssignReadInLoop(): void
{
	$cache = [];
	foreach (['a', 'b'] as $k) {
		$v = $cache[$k] ??= source();
		sink($v);
	}
}

function dimWriteCoalesceAssignResultConsumed(): void
{
	$cache = [];
	while (cond()) {
		if (($cache['x'] ??= cond()) === true) {
			sink(1);
		}
	}
}

function dimWriteCoalesceAssignValueFlow(): void
{
	$cache = [];
	$v = $cache['x'] ??= source(); // unused $v, $cache['x']
}

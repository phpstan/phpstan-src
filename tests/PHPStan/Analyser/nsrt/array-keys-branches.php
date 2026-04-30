<?php

use function PHPStan\Testing\assertType;

function (array $generalArray) {
	$array = [
		'i' => 0,
		'j' => 0,
		'k' => 0,
		'l' => 0,
		'm' => 0,
	];

	/** @var \DateTimeImmutable|null $nullableDateTime */
	$nullableDateTime = doFoo();
	$array['key'] = $nullableDateTime;

	$arrayAppendedInIf = ['foo', 'bar'];
	if ($array['key'] === null) {
		$array['key'] = new \DateTimeImmutable();
		$arrayAppendedInIf[] = 'baz';
	}

	if ($generalArray['key'] === null) {
		$generalArray['key'] = new \DateTimeImmutable();
	}

	foreach ([1, 2] as $x) {
		$array['i'] += $x;
		$array['k']++;
	}

	/** @var int[] $ints */
	$ints = doFoo();
	$arrayAppendedInForeach = ['foo', 'bar'];
	$anotherArrayAppendedInForeach = ['foo', 'bar'];
	$i = 0;

	$incremented = 0;
	$setFromZeroToOne = 0;
	foreach ($ints as $x) {
		$array['j'] += $x;
		$arrayAppendedInForeach[] = 'baz';
		$anotherArrayAppendedInForeach[$i++] = 'baz';
		$incremented++;
		$setFromZeroToOne = 1;
	}

	$array['l']++;
	$array['m'] += 5;

	if (rand(0, 1) === 1) {
		$array['n'] = 'str';
	}

	assertType("array{i: 3, j: int, k: 2, l: 1, m: 5, key: DateTimeImmutable, n?: 'str'}", $array);
	assertType('non-empty-array&hasOffsetValue(\'key\', mixed~null)', $generalArray);
	assertType('mixed~null', $generalArray['key']);
	assertType('array{0: \'foo\', 1: \'bar\', 2?: \'baz\'}', $arrayAppendedInIf);
	assertType('non-empty-list<\'bar\'|\'baz\'|\'foo\'>', $arrayAppendedInForeach);
	assertType("array{literal-string&lowercase-string&non-falsy-string, literal-string&lowercase-string&non-falsy-string, ...<int<2, max>, 'baz'>}", $anotherArrayAppendedInForeach);
	assertType('\'str\'', $array['n']);
	assertType('int<0, max>', $incremented);
	assertType('0|1', $setFromZeroToOne);
};

/**
 * @param array $generalArray
 * @param non-empty-list<1|2> $xs
 */
function (array $generalArray, array $xs) {
	$array = [
		'i' => 0,
		'j' => 0,
		'k' => 0,
		'l' => 0,
		'm' => 0,
	];

	/** @var \DateTimeImmutable|null $nullableDateTime */
	$nullableDateTime = doFoo();
	$array['key'] = $nullableDateTime;

	$arrayAppendedInIf = ['foo', 'bar'];
	if ($array['key'] === null) {
		$array['key'] = new \DateTimeImmutable();
		$arrayAppendedInIf[] = 'baz';
	}

	if ($generalArray['key'] === null) {
		$generalArray['key'] = new \DateTimeImmutable();
	}

	foreach ($xs as $x) {
		$array['i'] += $x;
		$array['k']++;
	}

	/** @var int[] $ints */
	$ints = doFoo();
	$arrayAppendedInForeach = ['foo', 'bar'];
	$anotherArrayAppendedInForeach = ['foo', 'bar'];
	$i = 0;

	$incremented = 0;
	$setFromZeroToOne = 0;
	foreach ($ints as $x) {
		$array['j'] += $x;
		$arrayAppendedInForeach[] = 'baz';
		$anotherArrayAppendedInForeach[$i++] = 'baz';
		$incremented++;
		$setFromZeroToOne = 1;
	}

	$array['l']++;
	$array['m'] += 5;

	if (rand(0, 1) === 1) {
		$array['n'] = 'str';
	}

	assertType('array{i: (float|int), j: int, k: int<0, max>, l: 1, m: 5, key: DateTimeImmutable, n?: \'str\'}', $array);
	assertType('non-empty-array&hasOffsetValue(\'key\', mixed~null)', $generalArray);
	assertType('mixed~null', $generalArray['key']);
	assertType('array{0: \'foo\', 1: \'bar\', 2?: \'baz\'}', $arrayAppendedInIf);
	assertType('non-empty-list<\'bar\'|\'baz\'|\'foo\'>', $arrayAppendedInForeach);
	assertType("array{literal-string&lowercase-string&non-falsy-string, literal-string&lowercase-string&non-falsy-string, ...<int<2, max>, 'baz'>}", $anotherArrayAppendedInForeach);
	assertType('\'str\'', $array['n']);
	assertType('int<0, max>', $incremented);
	assertType('0|1', $setFromZeroToOne);
};

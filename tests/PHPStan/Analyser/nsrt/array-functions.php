<?php // lint >= 8.0

use function PHPStan\Testing\assertType;

$integers = [1, 2, 3];
$mixedValues = ['abc', 123];

$mappedStrings = array_map(function (): string {

}, $integers);

$filteredIntegers = array_filter($integers, function (): bool {

});

$filteredMixed = array_filter($mixedValues, function ($mixedValue): bool {
	return is_int($mixedValue);
});

$uniquedIntegers = array_unique($integers);

$reducedIntegersToString = array_reduce($integers, function (): string {

});
$reducedIntegersToStringWithNull = array_reduce($uniquedIntegers, function (): string {

});
$reducedIntegersToStringAnother = array_reduce($integers, function (): string {

}, 'initial');
$reducedToNull = array_reduce([], function (): string {

});
$reducedToInt = array_reduce([], function (): string {

}, 1);
$reducedIntegersToStringWithInt = array_reduce($uniquedIntegers, function (): string {

}, 1);

$filledIntegers = array_fill(0, 5, 1);
$emptyFilled = array_fill(3, 0, 'banana');
$filledIntegersWithKeys = array_fill_keys([0], 1);
/** @var negative-int $negInt */
$filledAlwaysFalse = array_fill(0, $negInt, 1);
/** @var positive-int $posInt */
$filledNonEmptyArray = array_fill(0, $posInt, 'foo');
$filledNegativeConstAlwaysFalse = array_fill(0, -5, 1);
/** @var int<-3, 5> $maybeNegRange */
$filledByMaybeNegativeRange = array_fill(0, $maybeNegRange, 1);
$filledByPositiveRange = array_fill(0, rand(3, 5), 1);

$integerKeys = [
	1 => 'foo',
	2 => new \stdClass(),
];

$stringKeys = [
	'foo' => 'foo',
	'bar' => new \stdClass(),
];

/** @var \stdClass[] $stdClassesWithIsset */
$stdClassesWithIsset = doFoo();
if (rand(0, 1) === 0) {
	$stdClassesWithIsset[] = new \stdClass();
}
if (!isset($stdClassesWithIsset['baz'])) {
	return;
}

$stringOrIntegerKeys = [
	'foo' => new \stdClass(),
	1 => new \stdClass(),
];

$constantArrayWithFalseyValues = [null, '', 1];

$constantTruthyValues = array_filter($constantArrayWithFalseyValues);

/** @var array<int, false|null> $falsey */
$falsey = doFoo();

/** @var array<int, bool|null> $withFalsey */
$withFalsey = doFoo();

$union = ['a' => 1];
if (rand(0, 1) === 1) {
	$union['b'] = false;
}

/** @var bool $bool */
$bool = doFoo();
/** @var int $integer */
$integer = doFoo();

$withPossiblyFalsey = [$bool, $integer, '', 'a' => 0];

/** @var array<string, int> $generalStringKeys */
$generalStringKeys = doFoo();

/** @var array<int, int> $generalIntegerKeys */
$generalIntegerKeys = doFoo();

/** @var array<int, \DateTimeImmutable> $generalDateTimeValues */
$generalDateTimeValues = doFoo();

/** @var int $integer */
$integer = doFoo();

/** @var string $string */
$string = doFoo();

/** @var int[] $generalIntegers */
$generalIntegers = doFoo();

/** @var int[][] $generalIntegersInAnotherArray */
$generalIntegersInAnotherArray = doFoo();

$mappedStringKeys = array_map(function (): \stdClass {

}, $generalStringKeys);

/** @var callable $callable */
$callable = doFoo();

$mappedStringKeysWithUnknownClosureType = array_map($callable, $generalStringKeys);
$mappedWrongArray = array_map(function (): string {

}, 1);
$unknownArray = array_map($callable, 1);

$conditionalArray = ['foo', 'bar'];
$conditionalKeysArray = [
	'foo' => 1,
	'bar' => 1,
];
if (doFoo()) {
	$conditionalArray[] = 'baz';
	$conditionalArray[] = 'lorem';
	$conditionalKeysArray['baz'] = 1;
	$conditionalKeysArray['lorem'] = 1;
}

/** @var int|string $generalIntegerOrString */
$generalIntegerOrString = doFoo();

/** @var array<int, int|string> $generalArrayOfIntegersOrStrings */
$generalArrayOfIntegersOrStrings = doFoo();

/** @var array<int|string, int> $generalIntegerOrStringKeys */
$generalIntegerOrStringKeys = doFoo();

/** @var array<int|string, mixed> $generalIntegerOrStringKeysMixedValues */
$generalIntegerOrStringKeysMixedValues = doFoo();

$clonedConditionalArray = $conditionalArray;
$clonedConditionalArray[(int)$generalIntegerOrString] = $generalIntegerOrString;

if (random_int(0, 1)) {
	$unionArrays = [1=>1, 2=> '', 'a' => 0];
} else {
	$unionArrays = ['foo' => 'bar', 'baz' => 'qux'];
}

/** @var mixed $mixed */
$mixed = doFoo();

/** @var array $array */
$array = doFoo();

/** @var array $array2 */
$array2 = doFoo();

/** @var string[] $stringArray */
$stringArray = doFoo();

$slicedOffset = array_slice(['4' => 'foo', 1 => 'bar', 'baz' => 'qux', 0 => 'quux', 'quuz' => 'corge'], 0, null, false);
$slicedOffsetWithKeys = array_slice(['4' => 'foo', 1 => 'bar', 'baz' => 'qux', 0 => 'quux', 'quuz' => 'corge'], 0, null, true);

$slicedOffset[] = 'grault';
$slicedOffsetWithKeys[] = 'grault';

$mergedInts = [];
foreach ($array as $val) {
	$mergedInts = array_merge($mergedInts, $generalIntegers);
}

$fooArray = ['foo'];
$poppedFoo = array_pop($fooArray);

assertType('1', $integers[0]);
assertType('array{string, string, string}', $mappedStrings);
assertType('string', $mappedStrings[0]);
assertType('1|2|3', $filteredIntegers[0]);
assertType('*ERROR*', $filteredMixed[0]);
assertType('123', $filteredMixed[1]);
assertType('non-empty-array<0|1|2, 1|2|3>', $uniquedIntegers);
assertType('1|2|3', $uniquedIntegers[1]);
assertType('string', $reducedIntegersToString);
assertType('string|null', $reducedIntegersToStringWithNull);
assertType('string', $reducedIntegersToStringAnother);
assertType('null', $reducedToNull);
assertType('1|string', $reducedIntegersToStringWithInt);
assertType('1', $reducedToInt);
assertType('array{1, 2, 3}', array_change_key_case($integers));
assertType('array', array_combine($array, $array2));
assertType('array{1: 2}', array_combine([1], [2]));
assertType('*NEVER*', array_combine([1, 2], [3]));
assertType('array{a: \'d\', b: \'e\', c: \'f\'}', array_combine(['a', 'b', 'c'], ['d', 'e', 'f']));
assertType('array<1|2|3, mixed>', array_combine([1, 2, 3], $array));
assertType('array<1|2|3>', array_combine($array, [1, 2, 3]));
assertType('array', array_combine($array, $array));
assertType('array<string, string>', array_combine($stringArray, $stringArray));
assertType('array<0|1|2, 1|2|3>', array_diff_assoc($integers, []));
assertType('array<0|1|2, 1|2|3>', array_diff_key($integers, []));
assertType('array<0|1|2, 1|2|3>', array_diff_uassoc($integers, []));
assertType('array<0|1|2, 1|2|3>', array_diff_ukey($integers, []));
assertType('array<0|1|2, 1|2|3>', array_diff($integers, []));
assertType('array<0|1|2, 1|2|3>', array_udiff_assoc($integers, []));
assertType('array<0|1|2, 1|2|3>', array_udiff_uassoc($integers, []));
assertType('array<0|1|2, 1|2|3>', array_udiff($integers, []));
assertType('array<0|1|2, 1|2|3>', array_intersect_assoc($integers, []));
assertType('array{}', array_intersect_key($integers, []));
assertType('array{1, 2, 3}|array{4, 5, 6}', array_intersect_key(...[$integers, [4, 5, 6]]));
assertType('array<int>', array_intersect_key(...$generalIntegersInAnotherArray));
assertType('array<0|1|2, 1|2|3>', array_intersect_uassoc($integers, []));
assertType('array<0|1|2, 1|2|3>', array_intersect_ukey($integers, []));
assertType('array<0|1|2, 1|2|3>', array_intersect($integers, []));
assertType('array<0|1|2, 1|2|3>', array_uintersect_assoc($integers, []));
assertType('array<0|1|2, 1|2|3>', array_uintersect_uassoc($integers, []));
assertType('array<0|1|2, 1|2|3>', array_uintersect($integers, []));
assertType('array{1, 1, 1, 1, 1}', $filledIntegers);
assertType('array{}', $emptyFilled);
assertType('array{1}', $filledIntegersWithKeys);
assertType('non-empty-list<\'foo\'>', $filledNonEmptyArray);
assertType('*NEVER*', $filledAlwaysFalse);
assertType('*NEVER*', $filledNegativeConstAlwaysFalse);
assertType('list<1>', $filledByMaybeNegativeRange);
assertType('non-empty-list<1>', $filledByPositiveRange);
assertType('array{1, 2}', array_keys($integerKeys));
assertType('array{\'foo\', \'bar\'}', array_keys($stringKeys));
assertType('array{\'foo\', 1}', array_keys($stringOrIntegerKeys));
assertType('list<string>', array_keys($generalStringKeys));
assertType('array{\'foo\', stdClass}', array_values($integerKeys));
assertType('list<int>', array_values($generalStringKeys));
assertType('array{foo: stdClass, 0: stdClass}', array_merge($stringOrIntegerKeys));
assertType('array<int|string, DateTimeImmutable|int>', array_merge($generalStringKeys, $generalDateTimeValues));
assertType('array{foo: stdClass, ...<int|string, int|stdClass>}', array_merge($generalStringKeys, $stringOrIntegerKeys));
assertType('array{foo: int|stdClass, ...<int|string, int|stdClass>}', array_merge($stringOrIntegerKeys, $generalStringKeys));
assertType('array{foo: stdClass, bar: stdClass, 0: stdClass}', array_merge($stringKeys, $stringOrIntegerKeys));
assertType('array{foo: \'foo\', 0: stdClass, bar: stdClass}', array_merge($stringOrIntegerKeys, $stringKeys));
assertType('array{foo: 1, bar: 2, 0: 2, 1: 3}', array_merge(['foo' => 4, 'bar' => 5], ...[['foo' => 1, 'bar' => 2], [2, 3]]));
assertType('array{foo: 1, foo2: stdClass}', array_merge(['foo' => new stdClass()], ...[['foo2' => new stdClass()], ['foo' => 1]]));
assertType('array{foo: 1, foo2: stdClass}', array_merge(['foo' => new stdClass()], ...[['foo2' => new stdClass()], ['foo' => 1]]));
assertType('array{color: \'green\', 0: 2, 1: 4, 2: \'a\', 3: \'b\', shape: \'trapezoid\', 4: 4}', array_merge(array("color" => "red", 2, 4), array("a", "b", "color" => "green", "shape" => "trapezoid", 4)));
assertType('array<int|string, DateTimeImmutable|int>', array_merge(...[$generalStringKeys, $generalDateTimeValues]));
assertType('array<int>', $mergedInts);
assertType('array{5: \'banana\', 6: \'banana\', 7: \'banana\', 8: \'banana\', 9: \'banana\', 10: \'banana\'}', array_fill(5, 6, 'banana'));
assertType('non-empty-list<\'apple\'>', array_fill(0, 101, 'apple'));
assertType('array{-2: \'pear\', 0: \'pear\', 1: \'pear\', 2: \'pear\'}', array_fill(-2, 4, 'pear'));
assertType('non-empty-array<int, stdClass>', array_fill($integer, 2, new \stdClass()));
assertType('array<int, stdClass>', array_fill(2, $integer, new \stdClass()));
assertType('array<int, stdClass>', array_fill_keys($generalStringKeys, new \stdClass()));
assertType('array{foo: \'banana\', 5: \'banana\', 10: \'banana\', bar: \'banana\'}', array_fill_keys(['foo', 5, 10, 'bar'], 'banana'));
assertType('array<string, stdClass>', $mappedStringKeys);
assertType('array<string, mixed>', $mappedStringKeysWithUnknownClosureType);
assertType('array<string>', $mappedWrongArray);
assertType('array', $unknownArray);
assertType('array{foo: \'banana\', bar: \'banana\', baz: \'banana\', lorem: \'banana\'}|array{foo: \'banana\', bar: \'banana\'}', array_fill_keys($conditionalArray, 'banana'));
assertType('array{foo: stdClass, bar: stdClass, baz: stdClass, lorem: stdClass}|array{foo: stdClass, bar: stdClass}', array_map(function (): \stdClass {}, $conditionalKeysArray));
$stringKeysCopy1 = $stringKeys;
assertType('\'foo\'|stdClass', array_pop($stringKeysCopy1));
assertType('non-empty-array<stdClass>&hasOffsetValue(\'baz\', stdClass)', $stdClassesWithIsset);
assertType('stdClass', array_pop($stdClassesWithIsset));
$stringKeysCopy2 = $stringKeys;
assertType('\'foo\'|stdClass', array_shift($stringKeysCopy2));
assertType('int|null', array_pop($generalStringKeys));
assertType('int|null', array_shift($generalStringKeys));
assertType('null', array_pop([]));
assertType('null', array_shift([]));
assertType('array{null, \'\', 1}', $constantArrayWithFalseyValues);
assertType('array{2: 1}', $constantTruthyValues);
assertType('array<int, false|null>', $falsey);
assertType('array{}', array_filter($falsey));
assertType('array<int, bool|null>', $withFalsey);
assertType('array<int, true>', array_filter($withFalsey));
assertType('array{a: 1}', array_filter($union));
assertType('array{0?: true, 1?: int<min, -1>|int<1, max>}', array_filter($withPossiblyFalsey));
assertType('array', array_filter($mixed));
assertType('1|\'foo\'|false', array_search(new stdClass, $stringOrIntegerKeys, true));
assertType('\'foo\'', array_search('foo', $stringKeys, true));
assertType('int|false', array_search(new DateTimeImmutable(), $generalDateTimeValues, true));
assertType('string|false', array_search(9, $generalStringKeys, true));
assertType('string|false', array_search(9, $generalStringKeys, false));
assertType('string|false', array_search(9, $generalStringKeys));
assertType('*NEVER*', array_search(999, $integer, true));
assertType('false', array_search(new stdClass, $generalStringKeys, true));
assertType('int|string|false', array_search($mixed, $array, true));
assertType('int|string|false', array_search($mixed, $array, false));
assertType('\'a\'|\'b\'|false', array_search($string, ['a' => 'A', 'b' => 'B'], true));
assertType('false', array_search($integer, ['a' => 'A', 'b' => 'B'], true));
assertType('\'foo\'|false', array_search($generalIntegerOrString, $stringKeys, true));
assertType('int|false', array_search($generalIntegerOrString, $generalArrayOfIntegersOrStrings, true));
assertType('int|false', array_search($generalIntegerOrString, $clonedConditionalArray, true));
assertType('int|string|false', array_search($generalIntegerOrString, $generalIntegerOrStringKeys, false));
assertType('false', array_search('id', $generalIntegerOrStringKeys, true));
assertType('int|string|false', array_search('id', $generalIntegerOrStringKeysMixedValues, true));
assertType('*ERROR*', array_search('id', doFoo() ? $generalIntegerOrStringKeys : false, true));
assertType('*ERROR*', array_search('id', doFoo() ? [] : false, true));
assertType('*NEVER*', array_search('id', false, true));
assertType('*NEVER*', array_search('id', false));
assertType('int|string|false', array_search('id', $thisDoesNotExistAndIsMixed, true));
assertType('int|string|false', array_search('id', doFoo() ? $thisDoesNotExistAndIsMixedInUnion : false, true));
assertType('int|string|false', array_search(1, $generalIntegers, true));
assertType('int|string|false', array_search(1, $generalIntegers, false));
assertType('int|string|false', array_search(1, $generalIntegers));
assertType('array<string, int>', array_slice($generalStringKeys, 0));
assertType('array<string, int>', array_slice($generalStringKeys, 1));
assertType('array<string, int>', array_slice($generalStringKeys, 1, null, true));
assertType('array<string, int>', array_slice($generalStringKeys, 1, 2));
assertType('array<string, int>', array_slice($generalStringKeys, 1, 2, true));
assertType('array<string, int>', array_slice($generalStringKeys, 1, -1));
assertType('array<string, int>', array_slice($generalStringKeys, 1, -1, true));
assertType('array<string, int>', array_slice($generalStringKeys, -2));
assertType('array<string, int>', array_slice($generalStringKeys, -2, 1, true));
assertType('array', array_slice($unknownArray, 0));
assertType('array', array_slice($unknownArray, 1));
assertType('array', array_slice($unknownArray, 1, null, true));
assertType('array', array_slice($unknownArray, 1, 2));
assertType('array', array_slice($unknownArray, 1, 2, true));
assertType('array', array_slice($unknownArray, 1, -1));
assertType('array', array_slice($unknownArray, 1, -1, true));
assertType('array', array_slice($unknownArray, -2));
assertType('array', array_slice($unknownArray, -2, 1, true));
assertType('array{0: bool, 1: int, 2: \'\', a: 0}', array_slice($withPossiblyFalsey, 0));
assertType('array{0: int, 1: \'\', a: 0}', array_slice($withPossiblyFalsey, 1));
assertType('array{1: int, 2: \'\', a: 0}', array_slice($withPossiblyFalsey, 1, null, true));
assertType('array{0: \'\', a: 0}', array_slice($withPossiblyFalsey, 2, 3));
assertType('array{2: \'\', a: 0}', array_slice($withPossiblyFalsey, 2, 3, true));
assertType('array{int, \'\'}', array_slice($withPossiblyFalsey, 1, -1));
assertType('array{1: int, 2: \'\'}', array_slice($withPossiblyFalsey, 1, -1, true));
assertType('array{0: \'\', a: 0}', array_slice($withPossiblyFalsey, -2, null));
assertType('array{2: \'\', a: 0}', array_slice($withPossiblyFalsey, -2, null, true));
assertType('array{0: \'\', a: 0}|array{baz: \'qux\'}', array_slice($unionArrays, 1));
assertType('array{a: 0}|array{baz: \'qux\'}', array_slice($unionArrays, -1, null, true));
assertType('array{0: \'foo\', 1: \'bar\', baz: \'qux\', 2: \'quux\', quuz: \'corge\', 3: \'grault\'}', $slicedOffset);
assertType('array{4: \'foo\', 1: \'bar\', baz: \'qux\', 0: \'quux\', quuz: \'corge\', 5: \'grault\'}', $slicedOffsetWithKeys);
assertType('0|1', key($mixedValues));
assertType('int|null', key($falsey));
assertType('string|null', key($generalStringKeys));
assertType('int|string|null', key($generalIntegerOrStringKeysMixedValues));
assertType('\'foo\'', $poppedFoo);
assertType('int', array_rand([1 => 1, 2 => "2"]));
assertType('string', array_rand(["a" => 1, "b" => "2"]));
assertType('int|string', array_rand(["a" => 1, 2 => "b"]));
assertType('int|string', array_rand([1 => 1, 2 => "b", $mixed => $mixed]));
assertType('int', array_rand([1 => 1, 2 => "b"], 1));
assertType('string', array_rand(["a" => 1, "b" => "b"], 1));
assertType('int|string', array_rand(["a" => 1, 2 => "b"], 1));
assertType('int|string', array_rand([1 => 1, 2 => "b", $mixed => $mixed], 1));
assertType('array<int, int>', array_rand([1 => 1, 2 => "b"], 2));
assertType('array<int, string>', array_rand(["a" => 1, "b" => "b"], 2));
assertType('array<int, int|string>', array_rand(["a" => 1, 2 => "b"], 2));
assertType('array<int, int|string>', array_rand([1 => 1, 2 => "2", $mixed => $mixed], 2));
assertType('array<int, int>|int', array_rand([1 => 1, 2 => "b"], $mixed));
assertType('array<int, string>|string', array_rand(["a" => 1, "b" => "b"], $mixed));
assertType('array<int, int|string>|int|string', array_rand(["a" => 1, 2 => "b"], $mixed));
assertType('array<int, int|string>|int|string', array_rand([1 => 1, 2 => "b", $mixed => $mixed], $mixed));

<?php // lint >= 8.0

namespace ExplodeFunction;

use function PHPStan\Testing\assertType;

function (string $delimiter, $mixed) {

	/** @var string $str */
	$str = doFoo();
	$sureArray = explode(' ', $str);
	$sureFalse = explode('', $str);
	$arrayOrFalse = explode($delimiter, $str);

	$emptyOrComma = '';
	if (doFoo()) {
		$emptyOrComma = ',';
	}

	$anotherArrayOrFalse = explode($emptyOrComma, $str);
	$benevolentArrayOrFalse = explode($mixed, $str);

	assertType('non-empty-list<string>', $sureArray);
	assertType('*NEVER*', $sureFalse);
	assertType('non-empty-list<string>', $arrayOrFalse);
	assertType('non-empty-list<string>', $anotherArrayOrFalse);
	assertType('non-empty-list<string>', $benevolentArrayOrFalse);

};

/**
 * @param ','|';' $delimiterUnion
 * @param 'a,b'|'x;y;z' $stringUnion
 * @param 1|2 $limitUnion
 * @param int<1, 3> $limitRange
 * @param ''|',' $maybeEmptyDelimiter
 */
function constantSplit(string $delimiterUnion, string $stringUnion, int $limitUnion, int $limitRange, string $maybeEmptyDelimiter, string $unknown, int $unknownLimit): void
{
	assertType("array{'a', 'b', 'c'}", explode(',', 'a,b,c'));
	assertType("array{'App/Service::foo'}", explode(':::', 'App/Service::foo'));
	assertType("array{''}", explode(',', ''));
	assertType("array{'a', 'b,c'}", explode(',', 'a,b,c', 2));
	assertType("array{'a,b,c'}", explode(',', 'a,b,c', 0));
	assertType("array{'a,b,c'}", explode(',', 'a,b,c', 1));
	assertType("array{'a', 'b'}", explode(',', 'a,b,c', -1));
	assertType('array{}', explode(',', 'a,b', -5));

	assertType("array{'a', 'b'}|array{'a,b'}|array{'x', 'y', 'z'}|array{'x;y;z'}", explode($delimiterUnion, $stringUnion));
	assertType("array{'a', 'b'}|array{'x;y;z'}", explode(',', $stringUnion));
	assertType("array{'a', 'b,c'}|array{'a,b,c'}", explode(',', 'a,b,c', $limitUnion));
	assertType("array{'a', 'b', 'c'}|array{'a', 'b,c'}|array{'a,b,c'}", explode(',', 'a,b,c', $limitRange));

	// the empty separator throws a ValueError on PHP 8+, so only the ',' split remains
	assertType("array{'a', 'b'}", explode($maybeEmptyDelimiter, 'a,b'));

	assertType('non-empty-list<lowercase-string>', explode($unknown, 'a,b'));
	assertType('non-empty-list<string>', explode(',', $unknown));
	assertType('list<lowercase-string>', explode(',', 'a,b,c', $unknownLimit));
}

/**
 * @param ','|';' $twoDelimiters
 * @param 'a,b;c'|'x,y;z' $twoStrings
 * @param int<1, 4> $limitRangeAtLimit
 * @param int<1, 5> $limitRangeOverLimit
 * @param 'a'|'b'|'c'|'d'|'e' $fiveDelimiters
 * @param 'xay'|'xby'|'xcy'|'xdy' $fourStrings
 */
function constantSplitLimits(string $twoDelimiters, string $twoStrings, int $limitRangeAtLimit, int $limitRangeOverLimit, string $fiveDelimiters, string $fourStrings): void
{
	// 2 delimiters * 2 strings * 4 limits is exactly CONSTANT_COMBINATION_LIMIT combinations
	assertType("array{'a', 'b;c'}|array{'a,b', 'c'}|array{'a,b;c'}|array{'x', 'y;z'}|array{'x,y', 'z'}|array{'x,y;z'}", explode($twoDelimiters, $twoStrings, $limitRangeAtLimit));

	// one more value in the limit range makes it 20 combinations, so the exact result is not computed
	assertType('non-empty-list<lowercase-string>', explode($twoDelimiters, $twoStrings, $limitRangeOverLimit));

	// 5 delimiters * 4 strings is over the limit too
	assertType('non-empty-list<lowercase-string>', explode($fiveDelimiters, $fourStrings));

	// 257 elements is more than ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT
	assertType('non-empty-list<lowercase-string&uppercase-string>', explode(',', ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,'));
}
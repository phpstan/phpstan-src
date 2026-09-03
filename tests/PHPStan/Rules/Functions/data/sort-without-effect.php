<?php declare(strict_types = 1);

namespace SortWithoutEffect;

/** @param list<string> $list */
function ksortList(array $list): void
{
	ksort($list);
}

/** @param list<string> $list */
function ksortListRegularFlags(array $list): void
{
	ksort($list, SORT_REGULAR);
}

/** @param list<string> $list */
function ksortListNumericFlags(array $list): void
{
	ksort($list, SORT_NUMERIC);
}

/** @param list<string> $list */
function ksortListStringFlags(array $list): void
{
	ksort($list, SORT_STRING);
}

/** @param list<string> $list */
function ksortListNaturalFlags(array $list): void
{
	ksort($list, SORT_NATURAL);
}

/** @param list<string> $list */
function ksortListUnknownFlags(array $list, int $flags): void
{
	ksort($list, $flags);
}

/** @param list<string> $list */
function krsortList(array $list): void
{
	krsort($list);
}

/** @param list<string> $list */
function sortList(array $list): void
{
	sort($list);
}

/** @param list<string> $list */
function usortList(array $list): void
{
	usort($list, fn ($a, $b) => 0);
}

/** @param list<string> $list */
function asortList(array $list): void
{
	asort($list);
}

function ksortAppendedList(): void
{
	$tips = [];
	$tips[] = 'a';
	$tips[] = 'b';
	ksort($tips);
}

/** @param array<string, int> $map */
function ksortMap(array $map): void
{
	ksort($map);
}

/** @param non-empty-array<string, int> $map */
function ksortNonEmptyMap(array $map): void
{
	ksort($map);
}

function ksortEmpty(): void
{
	$a = [];
	ksort($a);
}

function krsortEmpty(): void
{
	$a = [];
	krsort($a);
}

function asortEmpty(): void
{
	$a = [];
	asort($a);
}

function arsortEmpty(): void
{
	$a = [];
	arsort($a);
}

function sortEmpty(): void
{
	$a = [];
	sort($a);
}

function rsortEmpty(): void
{
	$a = [];
	rsort($a);
}

function usortEmpty(): void
{
	$a = [];
	usort($a, fn ($x, $y) => 0);
}

function uasortEmpty(): void
{
	$a = [];
	uasort($a, fn ($x, $y) => 0);
}

function uksortEmpty(): void
{
	$a = [];
	uksort($a, fn ($x, $y) => 0);
}

function shuffleEmpty(): void
{
	$a = [];
	shuffle($a);
}

function natsortEmpty(): void
{
	$a = [];
	natsort($a);
}

function natcasesortEmpty(): void
{
	$a = [];
	natcasesort($a);
}

/** @param array{foo: int} $single */
function ksortSingle(array $single): void
{
	ksort($single);
}

/** @param array{foo: int} $single */
function krsortSingle(array $single): void
{
	krsort($single);
}

/** @param array{foo: int} $single */
function asortSingle(array $single): void
{
	asort($single);
}

/** @param array{foo: int} $single */
function arsortSingle(array $single): void
{
	arsort($single);
}

/** @param array{foo: int} $single */
function uasortSingle(array $single): void
{
	uasort($single, fn ($x, $y) => 0);
}

/** @param array{foo: int} $single */
function uksortSingle(array $single): void
{
	uksort($single, fn ($x, $y) => 0);
}

/** @param array{foo: int} $single */
function natsortSingle(array $single): void
{
	natsort($single);
}

/** @param array{foo: int} $single */
function natcasesortSingle(array $single): void
{
	natcasesort($single);
}

/** @param array{foo: int} $single */
function sortSingle(array $single): void
{
	sort($single);
}

/** @param array{foo: int} $single */
function rsortSingle(array $single): void
{
	rsort($single);
}

/** @param array{foo: int} $single */
function usortSingle(array $single): void
{
	usort($single, fn ($x, $y) => 0);
}

/** @param array{foo: int} $single */
function shuffleSingle(array $single): void
{
	shuffle($single);
}

/** @param array{int} $singleList */
function sortSingleList(array $singleList): void
{
	sort($singleList);
}

/** @param array{int} $singleList */
function rsortSingleList(array $singleList): void
{
	rsort($singleList);
}

/** @param array{int} $singleList */
function usortSingleList(array $singleList): void
{
	usort($singleList, fn ($x, $y) => 0);
}

/** @param array{int} $singleList */
function shuffleSingleList(array $singleList): void
{
	shuffle($singleList);
}

/** @param array{foo: int}|array{bar: int, baz: int} $union */
function ksortUnion(array $union): void
{
	ksort($union);
}

/** @param array{foo: int}|array{bar: int} $unionOfSingles */
function ksortUnionOfSingles(array $unionOfSingles): void
{
	ksort($unionOfSingles);
}

function ksortMixed($mixed): void
{
	ksort($mixed);
}

/** @param array{foo?: int} $optional */
function ksortOptionalKey(array $optional): void
{
	ksort($optional);
}

/** @param array{0?: int} $optionalList */
function sortOptionalKeyList(array $optionalList): void
{
	sort($optionalList);
}

function sortSingleElementMapLiteral(): void
{
	$array = ['key' => 42];
	sort($array);
}

/** @param array{foo?: int} $optional */
function sortOptionalKeyMap(array $optional): void
{
	sort($optional);
}

/** @param array{foo?: int} $optional */
function shuffleOptionalKeyMap(array $optional): void
{
	shuffle($optional);
}

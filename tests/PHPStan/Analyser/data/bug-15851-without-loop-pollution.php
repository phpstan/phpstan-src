<?php declare(strict_types = 1);

namespace Bug15851WithoutLoopPollution;

use function PHPStan\Testing\assertType;

/** @return array{a: int}|false */
function fetchRow()
{
	return rand(0, 1) === 1 ? ['a' => 1] : false;
}

function countRows(): void
{
	for ($count = 0; $row = fetchRow(); ++$count) {
		echo $row['a'];
	}
	assertType('int<0, max>', $count); // 0 when fetchRow() returns false right away
	assertType('false', $row);
	if ($count > 0) {
		echo 'ran at least once';
	}
}

function lessThanUnknown(int $n): void
{
	for ($i = 0, $j = 5; $i < $n; $i++) {
	}
	assertType('int<0, max>', $i);
	assertType('5', $j);
}

function assignmentInConditionAlwaysTrueAtFirst(): void
{
	for ($i = 0; $row = ($i === 0 ? ['a' => 1] : fetchRow()); $i++) {
		echo $row['a'];
	}
	assertType('false', $row);
}

function sideEffectInCondition(): void
{
	for ($i = 0; $i++ < 10;) {
	}
	assertType('11', $i);
}

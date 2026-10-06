<?php declare(strict_types = 1);

namespace Bug15411;

function nextCookieValue(int $count): string
{
	return (string) ++$count;
}

echo nextCookieValue(1);

function previousCookieValue(int $count): string
{
	return (string) --$count;
}

echo previousCookieValue(1);

function nextAssignedValue(int $count): int
{
	$value = ++$count;
	return $value;
}

function previousAssignedValue(int $count): int
{
	$value = --$count;
	return $value;
}

/** @param array{count: int} $counts */
function nextOffsetValue(array $counts): int
{
	return ++$counts['count'];
}

/** @param array{count: int} $counts */
function previousOffsetValue(array $counts): int
{
	return --$counts['count'];
}

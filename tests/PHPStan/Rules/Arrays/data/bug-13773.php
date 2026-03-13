<?php declare(strict_types = 1);

namespace Bug13773;

/** @return array<int, string> */
function getArray(): array {
	return [100 => "hey"];
}

function testNonListArray(): void
{
	$array = getArray();
	for ($i = 0; $i < count($array); $i++) {
		$a = $array[$i];
	}
}

/** @param list<string> $list */
function testList(array $list): void
{
	for ($i = 0; $i < count($list); $i++) {
		$a = $list[$i];
	}
}

/** @param list<string> $list */
function testListReversed(array $list): void
{
	for ($i = 0; count($list) > $i; ++$i) {
		$a = $list[$i];
	}
}

/** @param array<int, string> $array */
function testNonListReversed(array $array): void
{
	for ($i = 0; count($array) > $i; ++$i) {
		$a = $array[$i];
	}
}

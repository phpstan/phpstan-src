<?php

namespace Bug13538;

use LogicException;

/** @param list<string> $arr */
function doFoo(array $arr, string $s): void
{
	$logs = [];
	$logs[$s] = '';
	foreach ($arr as $value) {
		echo $logs[$s];
	}
}

/** @param list<string> $arr */
function doFooBar(array $arr): void
{
	if (!defined('LOG_DIR')) {
		throw new LogicException();
	}

	$logs = [];
	$logs[LOG_DIR] = '';
	foreach ($arr as $value) {
		echo $logs[LOG_DIR];
	}
}

function doBar(array $arr, int $i, string $s): void
{
	$logs = [];
	$logs[$i][$s] = '';
	foreach ($arr as $value) {
		echo $logs[$i][$s];
	}
}

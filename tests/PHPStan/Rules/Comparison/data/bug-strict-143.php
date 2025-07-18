<?php declare(strict_types = 1);

namespace BugStrict143;

function foo(string $key, array $arr): void {
	$found = array_key_exists($key . 'foo', $arr) && array_key_exists($key . 'bar', $arr);
}

<?php

namespace GlobalsModification;

/**
 * @param array<string, mixed> $values
 */
function doFoo(array $values): void
{
	$GLOBALS = $values;
	$GLOBALS += $values;
	$GLOBALS ??= [];
	[$GLOBALS] = [$values];
	['a' => [, $GLOBALS]] = ['a' => [1, $values]];
	foreach ($values as $GLOBALS) {
	}
	foreach ($values as $GLOBALS => $value) {
	}
	$GLOBALS++;
	--$GLOBALS;
	unset($values, $GLOBALS);
	$GLOBALS = &$values;
	$reference = &$GLOBALS;
}

/**
 * @param array<string, mixed> $values
 */
function allowed(array $values): void
{
	$GLOBALS['foo'] = $values;
	$GLOBALS['foo']['bar'] = 1;
	$GLOBALS['foo'] ??= 1;
	$GLOBALS['foo']++;
	[$GLOBALS['foo']] = [1];
	unset($GLOBALS['foo']);
	foreach ($GLOBALS as $name => &$value) {
	}
	$copy = $GLOBALS;
	$reference = &$GLOBALS['foo'];
	var_dump($copy, $reference);
}

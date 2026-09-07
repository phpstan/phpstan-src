<?php

namespace ArrayObjectObjectInput;

use ArrayObject;
use __PHP_Incomplete_Class;
use function PHPStan\Testing\assertType;

function incompleteObject(__PHP_Incomplete_Class $value): void
{
	$array = new ArrayObject($value);
	assertType('mixed', $array['__PHP_Incomplete_Class_Name']);
}

function objectInput(object $value): void
{
	$array = new ArrayObject($value);
	assertType('mixed', $array['value']);
}

function arrayInput(): void
{
	$array = new ArrayObject(['value' => 'foo']);
	assertType("'foo'|null", $array['value']);
}

/** @extends ArrayObject<array-key, mixed> */
class Child extends ArrayObject
{
}

function inheritedConstructor(__PHP_Incomplete_Class $value): void
{
	$array = new Child($value);
	assertType('ArrayObjectObjectInput\Child', $array);
	assertType('mixed', $array['__PHP_Incomplete_Class_Name']);
}

function className(__PHP_Incomplete_Class $value): string
{
	$array = new ArrayObject($value);
	return $array['__PHP_Incomplete_Class_Name'];
}

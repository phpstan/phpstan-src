<?php

namespace Bug7981;

use function PHPStan\Testing\assertType;

class Obj
{
	public int $intValue;

	public string $stringValue;
}

function testIntCast(?Obj $obj): void
{
	if ((int) $obj?->intValue !== 0) {
		assertType('Bug7981\Obj', $obj);
	} else {
		assertType('Bug7981\Obj|null', $obj);
	}
}

function testIntCastEqual(?Obj $obj): void
{
	if ((int) $obj?->intValue === 0) {
		assertType('Bug7981\Obj|null', $obj);
	} else {
		assertType('Bug7981\Obj', $obj);
	}
}

function testStringCast(?Obj $obj): void
{
	if ((string) $obj?->stringValue !== '') {
		assertType('Bug7981\Obj', $obj);
	} else {
		assertType('Bug7981\Obj|null', $obj);
	}
}

function testBoolCast(?Obj $obj): void
{
	if ((bool) $obj?->intValue) {
		assertType('Bug7981\Obj', $obj);
	} else {
		assertType('Bug7981\Obj|null', $obj);
	}
}

function testFloatCast(?Obj $obj): void
{
	if ((float) $obj?->intValue !== 0.0) {
		assertType('Bug7981\Obj', $obj);
	} else {
		assertType('Bug7981\Obj|null', $obj);
	}
}

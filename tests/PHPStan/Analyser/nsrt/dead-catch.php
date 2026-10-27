<?php

namespace DeadCatchTypes;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class FooException extends \Exception
{

}

class BarException extends \Exception
{

}

interface MarkerException
{

}

/** @throws \RuntimeException */
function throwsRuntime(): void
{
}

function noThrowPoints(): void
{
	try {
		$a = 1;
	} catch (\InvalidArgumentException $e) {
		assertType('InvalidArgumentException', $e);
		assertNativeType('InvalidArgumentException', $e);
	}
}

function union(): void
{
	try {
		$a = 1;
	} catch (FooException | BarException $e) {
		assertType('DeadCatchTypes\BarException|DeadCatchTypes\FooException', $e);
		assertNativeType('DeadCatchTypes\BarException|DeadCatchTypes\FooException', $e);
	}
}

function interfaceCatch(): void
{
	try {
		$a = 1;
	} catch (MarkerException $e) {
		assertType('DeadCatchTypes\MarkerException&Throwable', $e);
		assertNativeType('DeadCatchTypes\MarkerException&Throwable', $e);
	}
}

function afterEarlierCatch(): void
{
	try {
		$a = 1;
	} catch (FooException $e) {
		assertType('DeadCatchTypes\FooException', $e);
	} catch (FooException | BarException $e) {
		assertType('DeadCatchTypes\BarException', $e);
		assertNativeType('DeadCatchTypes\BarException', $e);
	}
}

function afterThrowableCatch(): void
{
	try {
		throwsRuntime();
	} catch (\Throwable $e) {
		assertType('Throwable', $e);
	} catch (\InvalidArgumentException $e) {
		assertType('InvalidArgumentException', $e);
		assertNativeType('InvalidArgumentException', $e);
	}
}

function scopeOfThrowPoints(): void
{
	try {
		$a = 1;
		throwsRuntime();
		$a = 2;
		throwsRuntime();
		$a = 3;
	} catch (\InvalidArgumentException $e) {
		assertType('1|2', $a);
		assertNativeType('1|2', $a);
		assertType('InvalidArgumentException', $e);
	}
}

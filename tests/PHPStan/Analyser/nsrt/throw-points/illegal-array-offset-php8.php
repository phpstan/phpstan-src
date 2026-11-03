<?php // lint >= 8.0

namespace ThrowPoints\IllegalArrayOffset;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\doesntThrow;

// doesntThrow() never throws by itself, but on PHP 8.0+ its mixed return value used
// as an array/string offset may be an array or an object, which throws TypeError.
// A write throws only after the assigned value was evaluated and an undefined
// container was turned into an array, so $foo is always assigned. A read throws
// before the statements after it, so $foo may be left unassigned there.

function () {
	try {
		$foo[doesntThrow()] = 0;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		[$foo[doesntThrow()]] = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		$foo[doesntThrow()] .= 0;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		[][doesntThrow()];
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		[doesntThrow() => 1, $foo = 1];
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

/**
 * @param mixed[] $array
 * @param mixed $key
 */
function assignedValueIsEvaluatedFirst(array $array, $key): void
{
	try {
		$array[$key] = $x = 1;
	} catch (\TypeError $e) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $x);
}

/**
 * @param mixed[] $array
 * @param mixed $key
 */
function assignOpValueIsEvaluatedFirst(array $array, $key): void
{
	try {
		$array[$key] .= $x = 'a';
	} catch (\TypeError $e) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $x);
}

/**
 * @param mixed $key
 */
function undefinedContainerIsCreated($key): void
{
	try {
		$foo[$key] = 1;
	} catch (\TypeError $e) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
		assertType('array{}', $foo);
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $foo);
}

/**
 * @param mixed $key
 */
function maybeUndefinedContainerIsCreated($key, bool $b): void
{
	if ($b) {
		$foo = [1];
	}
	try {
		$foo[$key] = 1;
	} catch (\TypeError $e) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $foo);
}

/**
 * @param mixed $key
 */
function undefinedNestedContainerIsCreated($key): void
{
	try {
		$foo[1][$key] = 1;
	} catch (\TypeError $e) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $foo);
}

/**
 * @param mixed[] $array
 * @param mixed $key
 */
function coalesceAssignReadsFirst(array $array, $key): void
{
	try {
		$array[$key] ??= $x = 1;
	} catch (\TypeError $e) {
		assertVariableCertainty(TrinaryLogic::createNo(), $x);
	}
	assertVariableCertainty(TrinaryLogic::createMaybe(), $x);
}

/**
 * @param mixed $key
 */
function coalesceAssignUndefinedContainer($key): void
{
	try {
		$foo[$key] ??= 1;
	} catch (\TypeError $e) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $foo);
}

/**
 * @param mixed[] $array
 * @param mixed $key
 */
function readThrowsBeforeAssignment(array $array, $key): void
{
	try {
		$y = $array[$key];
	} catch (\TypeError $e) {
		assertVariableCertainty(TrinaryLogic::createNo(), $y);
	}
	assertVariableCertainty(TrinaryLogic::createMaybe(), $y);
}

/**
 * @param mixed $key
 */
function arrayLiteralValueIsEvaluatedFirst($key): void
{
	try {
		$y = [$key => $x = 1];
	} catch (\TypeError $e) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
		assertVariableCertainty(TrinaryLogic::createNo(), $y);
	}
	assertVariableCertainty(TrinaryLogic::createMaybe(), $y);
}

function arrayLiteralObjectKey(\stdClass $key): void
{
	try {
		$y = [$key => 1];
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $y);
	}
}

function arrayLiteralIntKey(int $key): void
{
	try {
		$y = [$key => 1];
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $y);
	}
}

/**
 * @param mixed $key
 */
function issetOfStringOffset(string $s, $key): void
{
	try {
		$y = isset($s[$key]);
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $y);
	}
}

/**
 * @param mixed $key
 */
function emptyOfStringOffset(string $s, $key): void
{
	try {
		$y = empty($s[$key]);
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $y);
	}
}

/**
 * @param mixed[]|string $container
 * @param mixed $key
 */
function issetOfArrayOrStringOffset($container, $key): void
{
	try {
		$y = isset($container[$key]);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $y);
	}
}

/**
 * @param mixed $key
 */
function issetOfNestedStringOffset(string $s, $key): void
{
	try {
		// the inner link is read before isset() asks about the outer one
		$y = isset($s[$key][0]);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $y);
	}
}

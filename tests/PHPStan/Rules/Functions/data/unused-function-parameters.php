<?php

namespace UnusedFunctionParameters;

function completelyUnused(int $used, int $unused): int
{
	return $used;
}

function immediatelyReassigned(int $x, int $used): int
{
	$x = 1;

	return $x + $used;
}

function readThenReassigned(int $x): int
{
	$x = $x + 1;

	return $x;
}

function reassignedInOneBranch(int $x): int
{
	if (rand(0, 1) === 0) {
		$x = 1;
	}

	return $x;
}

function byRefWritten(int &$x): void
{
	$x = 1;
}

function byRefUnused(int &$x): void
{
	echo 'no use of the reference';
}

function observedByFuncGetArgs(int $x): array
{
	$x = 1;

	return [func_get_args(), $x];
}

function variadicUnused(int $used, int ...$rest): int
{
	return $used;
}

function usedOnlyInClosureUse(int $x): callable
{
	return function () use ($x): int {
		return $x;
	};
}

function usedOnlyInArrowFunction(int $x): callable
{
	return fn (): int => $x;
}

function closureParametersAreNotReported(): callable
{
	return function (int $x): int {
		return 0;
	};
}

function usedViaCompact(string $key): array
{
	return compact('key');
}

function usedViaResolvedVariableVariable(int $secret, int $other): int
{
	$name = 'secret';

	return $$name;
}

/**
 * @param mixed[] $arr
 * @phpstan-assert-if-true string[] $arr
 */
function assertedParameterIsContract(array $arr): bool
{
	return true;
}

/**
 * @template T of array<int, int>
 * @param T $array
 * @return T[($maybeZero is 0 ? 0 : key-of<T>)]
 */
function parameterInConditionalReturnType(array $array, int $maybeZero): int
{
	return $array[0];
}

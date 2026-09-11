<?php declare(strict_types = 1);

namespace UnusedInputValueFlow;

function unusedParameter(int $input): void
{
	while (rand(0, 1)) {
		$input = $input + 1;
	}
}

function coveredParameter(int $input): void
{
	$copy = $input + 1;
}

function usedParameter(int $input): int
{
	$copy = $input + 1;
	return $copy;
}

function unusedCapture(int $input): \Closure
{
	return function () use ($input): void {
		while (rand(0, 1)) {
			$input = $input + 1;
		}
	};
}

function coveredCapture(int $input): \Closure
{
	return function () use ($input): void {
		$copy = $input + 1;
	};
}

function usedCapture(int $input): \Closure
{
	return function () use ($input): int {
		$copy = $input + 1;
		return $copy;
	};
}

function unusedCaptureOverwritten(int $input): \Closure
{
	return function () use ($input): int {
		$input = 1;
		return $input;
	};
}

function overwrittenParameter(int $input): int
{
	$input = 1;
	return $input;
}

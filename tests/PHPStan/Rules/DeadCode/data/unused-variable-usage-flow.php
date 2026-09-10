<?php // lint >= 8.3

namespace UnusedVariableUsageFlow;

function dynamicGlobal(): void
{
	$name = 'shared';
	global $$name;
}

function staticInitializer(): void
{
	$value = 123;
	static $cached = $value;
}

function terminatingOperands(bool $condition): void
{
	$error = new \RuntimeException();
	$condition && throw $error;
	$condition || throw $error;
}

function terminatingBranches(int $value): void
{
	$error = new \RuntimeException();
	switch ($value) {
		case 0:
			throw $error;
		default:
			return;
	}
}

function finallyReads(): void
{
	$value = 123;
	try {
		return;
	} finally {
		echo $value;
	}
}

function nullsafeCall(?\Closure $callback): void
{
	$value = 123;
	$callback?->__invoke($value);
}

function nestedCaptures(): \Closure
{
	$value = 123;
	return function () use ($value): \Closure {
		return fn () => $value;
	};
}

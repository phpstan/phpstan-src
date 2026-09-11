<?php declare(strict_types = 1);

namespace UnusedVariableRedundantOffsets;

function unused(): void
{
	$a = [1, 2];
	$a[0] = 1;
}

function used(): array
{
	$a = [1, 2];
	$a[0] = 1;
	return $a;
}

function nested(): array
{
	$a = ['x' => ['y' => 1]];
	$a['x']['y'] = 1;
	return $a;
}

function coercedKey(): array
{
	$a = [1];
	$a['0'] = 1;
	return $a;
}

function differentValue(): array
{
	$a = [1, 2];
	$a[0] = 3;
	return $a;
}

function newOffset(): array
{
	$a = [1];
	$a[1] = 1;
	return $a;
}

/** @param array{0?: 1} $a */
function optionalOffset(array $a): array
{
	$a[0] = 1;
	return $a;
}

/** @param array{0: 1} $a */
function phpDocOnly(array $a): array
{
	$a[0] = 1;
	return $a;
}

function append(): array
{
	$a = [1];
	$a[] = 1;
	return $a;
}

function dynamicKey(int $i): array
{
	$a = [1, 2];
	$a[$i] = 1;
	return $a;
}

function reference(): array
{
	$x = 1;
	$a = [&$x];
	$a[0] = 1;
	return $a;
}

function stringOffset(): string
{
	$a = '12';
	$a[0] = '1';
	return $a;
}

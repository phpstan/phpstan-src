<?php declare(strict_types = 1);

namespace UnusedVariableScopePollution;

/**
 * @param non-empty-array<int> $a
 */
function foreachOverNonEmptyArray(array $a): void
{
	$foo = 1;
	foreach ($a as $v) {
		$foo = $v;
	}
	echo $foo;
}

function foreachOverNonEmptyLiteral(): void
{
	$foo = 1;
	foreach ([1, 2] as $v) {
		$foo = $v;
	}
	echo $foo;
}

/**
 * @param array{} $a
 */
function foreachOverEmptyArray(array $a): void
{
	$foo = 1;
	foreach ($a as $v) {
		echo $foo;
		echo $v;
	}
}

function foreachOverEmptyLiteral(): void
{
	$foo = 1;
	foreach ([] as $v) {
		echo $foo;
		echo $v;
	}
}

/**
 * @param non-empty-array<int> $a
 */
function whileAlwaysIterating(array $a): void
{
	$foo = 1;
	$i = 0;
	while ($i < 1) {
		$foo = $a;
		$i++;
	}
	echo $foo;
}

function whileNeverIterating(): void
{
	$foo = 1;
	$i = 1;
	while ($i < 1) {
		echo $foo;
		$i++;
	}
}

function whileLiteralFalse(): void
{
	$foo = 1;
	while (false) {
		echo $foo;
	}
}

/**
 * @param non-empty-array<int> $a
 */
function forAlwaysIterating(array $a): void
{
	$foo = 1;
	for ($i = 0; $i < 1; $i++) {
		$foo = $a;
	}
	echo $foo;
}

function forNeverIterating(): void
{
	$foo = 1;
	for ($i = 1; $i < 1; $i++) {
		echo $foo;
	}
}

function forLiteralFalse(): void
{
	$foo = 1;
	for (; false;) {
		echo $foo;
	}
}

/**
 * @param non-empty-array<int> $a
 */
function blockOverwrite(array $a): void
{
	$foo = 1;
	{
		$foo = $a;
	}
	echo $foo;
}

function blockRead(): void
{
	$foo = 1;
	{
		echo $foo;
	}
}

<?php

namespace Bug12057;

use function PHPStan\Testing\assertType;

/** @param array{foo?: mixed} $options */
function earlyReturn(array $options): void
{
	if (($options['foo'] ?? null) === false) {
		return;
	}

	// The offset may still be absent, but if present it is not false.
	assertType('array{foo?: mixed~false}', $options);
}

/** @param array{foo?: mixed} $options */
function identical(array $options): void
{
	if (($options['foo'] ?? null) === false) {
		assertType('array{foo: false}', $options);
	} else {
		assertType('array{foo?: mixed~false}', $options);
	}
}

/** @param array{foo?: mixed} $options */
function notIdentical(array $options): void
{
	if (($options['foo'] ?? null) !== false) {
		assertType('array{foo?: mixed~false}', $options);
	} else {
		assertType('array{foo: false}', $options);
	}
}

/** @param array{foo?: int|false} $options */
function narrowUnionValue(array $options): void
{
	if (($options['foo'] ?? null) !== false) {
		assertType('array{foo?: int}', $options);
	}
}

/**
 * The same narrowing applies to any compared constant, not just false.
 *
 * @param array{foo?: 1|2|3} $options
 */
function narrowConstantUnionValue(array $options): void
{
	if (($options['foo'] ?? null) !== 2) {
		assertType('array{foo?: 1|3}', $options);
	}
}

class Config
{

	/** @var array{foo?: mixed} */
	public array $options = [];

}

function propertyContainer(Config $c): void
{
	if (($c->options['foo'] ?? null) === false) {
		return;
	}

	assertType('array{foo?: mixed~false}', $c->options);
}

/**
 * A nested offset must NOT be memorized as existing: when the outer key is
 * missing, the whole coalesce expression is the default and the condition
 * can still be satisfied.
 *
 * @param array<string, array{foo?: mixed}> $rows
 */
function nestedOffsetKeepsExistenceUncertain(array $rows, string $key): void
{
	if (($rows[$key]['foo'] ?? null) === false) {
		return;
	}

	assertType('array<string, array{foo?: mixed}>', $rows);
}

/**
 * When the default value would itself be removed, the left side is guaranteed
 * to be set and is narrowed to a required, non-null offset.
 *
 * @param array{foo?: int|null} $options
 */
function defaultRemovedForcesExistence(array $options): void
{
	if (($options['foo'] ?? null) !== null) {
		assertType('array{foo: int}', $options);
	}
}

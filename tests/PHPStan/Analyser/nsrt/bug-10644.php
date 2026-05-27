<?php declare(strict_types = 1);

namespace Bug10644;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, mixed> $data
 */
function testIssetCoalesce(array $data): void
{
	if (isset($data['subtitle']) && !is_string($data['subtitle'])) {
		throw new \InvalidArgumentException('Subtitle must be a string');
	}

	if (isset($data['subtitle'])) {
		assertType("string", $data['subtitle']);
	}
	assertType("string", $data['subtitle'] ?? '');
}

/**
 * @param mixed $y
 */
function testSimpleBool(bool $a, $y): void
{
	if ($a && !is_string($y)) {
		throw new \Exception();
	}

	if ($a) {
		assertType("string", $y);
	}
	assertType("mixed", $y);
}

/**
 * @param mixed $y
 */
function testSimpleInt(bool $a, $y): void
{
	if ($a && !is_int($y)) {
		throw new \Exception();
	}

	if ($a) {
		assertType("int", $y);
	}
}

/**
 * @param mixed $y
 */
function testSimpleArray(bool $a, $y): void
{
	if ($a && !is_array($y)) {
		throw new \Exception();
	}

	if ($a) {
		assertType("array<mixed, mixed>", $y);
	}
}

/**
 * @param mixed $y
 */
function testNotNull(?int $x, $y): void
{
	if ($x !== null && !is_string($y)) {
		throw new \Exception();
	}

	if ($x !== null) {
		assertType("string", $y);
	}
}

/**
 * @param mixed $x
 * @param mixed $y
 */
function testInstanceof($x, $y): void
{
	if ($x instanceof \stdClass && !is_int($y)) {
		throw new \Exception();
	}

	if ($x instanceof \stdClass) {
		assertType("int", $y);
	}
}

/**
 * @param array<string, mixed> $data
 */
function testIssetMultipleKeys(array $data): void
{
	if (isset($data['a']) && !is_string($data['a'])) {
		throw new \Exception();
	}
	if (isset($data['b']) && !is_int($data['b'])) {
		throw new \Exception();
	}

	if (isset($data['a'])) {
		assertType("string", $data['a']);
	}
	if (isset($data['b'])) {
		assertType("int", $data['b']);
	}
	assertType("string", $data['a'] ?? '');
	assertType("int", $data['b'] ?? 0);
}

/**
 * @param array<string, mixed> $data
 */
function testArrayKeyExists(array $data): void
{
	if (array_key_exists('subtitle', $data) && !is_string($data['subtitle'])) {
		throw new \Exception();
	}
	if (array_key_exists('subtitle', $data)) {
		assertType("string", $data['subtitle']);
	}
}

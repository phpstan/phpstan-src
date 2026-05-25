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

function testSimpleBool(bool $a, mixed $y): void
{
	if ($a && !is_string($y)) {
		throw new \Exception();
	}

	if ($a) {
		assertType("string", $y);
	}
	assertType("mixed", $y);
}

function testSimpleInt(bool $a, mixed $y): void
{
	if ($a && !is_int($y)) {
		throw new \Exception();
	}

	if ($a) {
		assertType("int", $y);
	}
}

function testSimpleArray(bool $a, mixed $y): void
{
	if ($a && !is_array($y)) {
		throw new \Exception();
	}

	if ($a) {
		assertType("array<mixed, mixed>", $y);
	}
}

function testNotNull(?int $x, mixed $y): void
{
	if ($x !== null && !is_string($y)) {
		throw new \Exception();
	}

	if ($x !== null) {
		assertType("string", $y);
	}
}

function testInstanceof(mixed $x, mixed $y): void
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

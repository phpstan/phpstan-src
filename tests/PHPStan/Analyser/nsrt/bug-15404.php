<?php declare(strict_types = 1);

namespace Bug15404;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/** @param array{projects?: list<string>} $s */
function a(array $s): void
{
	$has = isset($s['projects']);
	if (!$has) {
		return;
	}
	foreach ($s['projects'] as $p) {
		assertType('string', $p);
		assertNativeType('mixed', $p);
		if (!is_string($p)) {
			throw new \InvalidArgumentException();
		}
	}
}

/** @param array{projects?: list<string>} $s */
function inlineIsset(array $s): void
{
	if (!isset($s['projects'])) {
		return;
	}
	foreach ($s['projects'] as $p) {
		assertType('string', $p);
		assertNativeType('mixed', $p);
	}
}

/** @param array{projects?: list<string>} $s */
function arrayKeyExists(array $s): void
{
	$has = array_key_exists('projects', $s);
	if (!$has) {
		return;
	}
	foreach ($s['projects'] as $p) {
		assertType('string', $p);
		assertNativeType('mixed', $p);
	}
}

/** @param array{projects?: list<string>} $s */
function notEmptyArray(array $s): void
{
	$has = isset($s['projects']);
	if (!$has) {
		return;
	}
	if ($s['projects'] !== []) {
		assertType('non-empty-list<string>', $s['projects']);
		assertNativeType('mixed~array{}', $s['projects']);
	}
}

/** @param array{projects?: list<string>} $s */
function isArrayCheck(array $s): void
{
	$has = isset($s['projects']);
	if (!$has) {
		return;
	}
	if (is_array($s['projects'])) {
		assertType('list<string>', $s['projects']);
		assertNativeType('array<mixed, mixed>', $s['projects']);
	}
}

/** @param array{projects?: list<string>} $s */
function notIsArrayCheck(array $s): void
{
	$has = isset($s['projects']);
	if (!$has) {
		return;
	}
	if (!is_int($s['projects'])) {
		assertType('list<string>', $s['projects']);
		assertNativeType('mixed~int', $s['projects']);
	}
}

class Foo
{

	/** @var list<string>|null */
	public $projects;

	public function doFoo(): void
	{
		$has = $this->projects !== null;
		if (!$has) {
			return;
		}
		foreach ($this->projects as $p) {
			assertType('string', $p);
			assertNativeType('mixed', $p);
		}
	}

}

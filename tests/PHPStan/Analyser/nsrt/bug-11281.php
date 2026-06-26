<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11281;

use function PHPStan\Testing\assertType;

function hello2(string $values): void
{
	$values = json_decode($values);
	$hasError = false;
	try {
		$values = array_map(static function ($item) {
			return Hello::fromObject($item);
		}, $values);
		assertType('array<Bug11281\Hello>', $values);
	} catch (\Throwable) {
		$hasError = true;
	}
	if (!$hasError) {
		// The successful try-branch proves $values is array<Hello>; the
		// pre-assignment mixed must not make the merged type collapse to mixed.
		assertType('array<Bug11281\Hello>', $values);
	}
}

/**
 * The merged subtype-absorbed variable must survive as a conditional target
 * regardless of which control-flow form reads the guard afterwards.
 */
function positiveGuard(string $values): void
{
	$ok = false;
	try {
		$values = array_map(static fn ($item) => Hello::fromObject($item), json_decode($values));
		$ok = true;
	} catch (\Throwable) {
	}
	if ($ok) {
		assertType('array<Bug11281\Hello>', $values);
	}
}

function nestedGuard(string $values, bool $other): void
{
	$ok = false;
	try {
		$values = array_map(static fn ($item) => Hello::fromObject($item), json_decode($values));
		$ok = true;
	} catch (\Throwable) {
	}
	if ($other && $ok) {
		assertType('array<Bug11281\Hello>', $values);
	}
}

function ternaryGuard(string $values): void
{
	$ok = false;
	try {
		$values = array_map(static fn ($item) => Hello::fromObject($item), json_decode($values));
		$ok = true;
	} catch (\Throwable) {
	}
	$result = $ok ? $values : [];
	assertType('array<Bug11281\Hello>', $result);
}

final class Hello
{

	public function __construct(public int $a)
	{
	}

	public static function fromObject(\stdClass $object): self
	{
		return new self(...(array) $object);
	}

}

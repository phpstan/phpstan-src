<?php declare(strict_types = 1);

namespace IssetPropertyDefaultValue;

use function PHPStan\Testing\assertType;

class Holder
{

	public ?int $value = null;

	public ?int $noDefault;

}

function coalesceWithDefault(Holder $a, Holder $b): void
{
	if ($a->value === null && $b->value === null) {
		throw new \LogicException();
	}

	assertType('int', $a->value ?? $b->value);
}

function coalesceWithoutDefault(Holder $a, Holder $b): void
{
	if ($a->noDefault === null && $b->noDefault === null) {
		throw new \LogicException();
	}

	assertType('int', $a->noDefault ?? $b->noDefault);
}

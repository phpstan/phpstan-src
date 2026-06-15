<?php declare(strict_types = 1);

namespace Bug14828;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	public function brokenWithGuard(?string $pendingValue, ?string $submitted): void
	{
		if ($pendingValue !== null && $submitted === $pendingValue) {
			return;
		}

		// After `!(A && B)` the only sound consequent of "A is true" is `!B`,
		// i.e. `$submitted !== $pendingValue`, which does not narrow $submitted
		// because $pendingValue is a non-constant string.
		if ($pendingValue !== null) {
			assertType('string|null', $submitted);
		}
	}

	public function intCase(?int $a, ?int $b): void
	{
		if ($a !== null && $b === $a) {
			return;
		}

		if ($a !== null) {
			assertType('int|null', $b);
		}
	}

}

class WithProperties
{

	public ?string $p = null;

	public ?string $q = null;

	public function propertyCase(self $c): void
	{
		if ($c->p !== null && $c->q === $c->p) {
			return;
		}

		if ($c->p !== null) {
			assertType('string|null', $c->q);
		}
	}

}

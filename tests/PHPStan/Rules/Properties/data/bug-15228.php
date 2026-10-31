<?php declare(strict_types = 1);

namespace Bug15228;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class CoalesceAssignDemo
{
	public ?int $a = null;

	/** @var list<int>|null */
	public $b;

	/** @return list<string> */
	private function strings(): array
	{
		return ['x'];
	}

	// === Symptom 1: false negative below level 7 ===
	// Both methods write a string into ?int - a guaranteed TypeError.

	public function coalesceInvalid(): void
	{
		$this->a ??= 'boom';           // (NOT reported at levels 0-6.)
	}

	public function ifInvalid(): void
	{
		if (null === $this->a) {
			$this->a = 'boom';         // (Reported from level 0. Correct.)
		}
	}

	// === Symptom 2: false positive at level 7+ ===
	// Both methods write list<int>, which the property accepts.

	public function coalesceValid(): void
	{
		$this->b = $this->strings();   // (Genuine error, both methods.)
		$this->b ??= [1, 2];           // VALID write - reported at 7+ anyway.
	}

	public function ifValid(): void
	{
		$this->b = $this->strings();   // (Genuine error, both methods.)
		if (null === $this->b) {
			$this->b = [1, 2];         // VALID write - correctly not reported.
		}
	}
}

class Adjacent
{
	public static ?int $s = null;

	public ?int $a = null;

	public int $uninitialized;

	public function staticProperty(): void
	{
		self::$s ??= 'boom';
	}

	public function neverNull(): void
	{
		$this->a = 1;
		$this->a ??= 'never written';
	}

	public function maybeUninitialized(): void
	{
		$this->uninitialized ??= 'boom';
	}

	public function valid(): void
	{
		$this->a ??= 5;
		self::$s ??= 5;
	}
}

class RightSideNarrowing
{
	public ?string $q = null;

	public function rightSideSeesNullLeftSide(): void
	{
		$this->q ??= $this->q === null ? 'x' : 1;
	}
}

class Offsets
{
	/** @var array<string, int> */
	public array $m = [];

	public function invalid(): void
	{
		$this->m['k'] ??= 'boom';
	}

	public function valid(): void
	{
		$this->m['k'] ??= 5;
	}
}

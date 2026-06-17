<?php // lint >= 8.1

declare(strict_types = 0);

namespace RememberNonNullablePropertyWhenStrictTypesDisabled;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class KeepsPropertyNonNullable {
	private readonly int $i;

	public function __construct()
	{
		$this->i = getIntOrNull();
	}

	public function doFoo(): void {
		assertType('int', $this->i);
		assertNativeType('int', $this->i);
	}
}

class DontCoercePhpdocType {
	/** @var int */
	private $i;

	public function __construct()
	{
		$this->i = getIntOrNull();
	}

	public function doFoo(): void {
		assertType('int', $this->i);
		assertNativeType('mixed', $this->i);
	}
}

function getIntOrNull(): ?int {
	if (rand(0, 1) === 0) {
		return null;
	}
	return 1;
}


class KeepsPropertyNonNullable2 {
	private int|float $i;

	public function __construct()
	{
		$this->i = getIntOrFloatOrNull();
	}

	public function doFoo(): void {
		assertType('float|int', $this->i);
		assertNativeType('float|int', $this->i);
	}
}

function getIntOrFloatOrNull(): null|int|float {
	if (rand(0, 1) === 0) {
		return null;
	}

	if (rand(0, 10) === 0) {
		return 1.0;
	}
	return 1;
}

// Since PHP 8.5, "clone with" can reinitialize any readonly property, so the value
// assigned in the constructor is no longer remembered in other methods.
if (PHP_VERSION_ID >= 80500) {
	class NarrowsNativeUnionCloneWith {
		private readonly int|float $i;

		public function __construct()
		{
			$this->i = getInt();
		}

		public function doFoo(): void {
			assertType('float|int', $this->i);
			assertNativeType('float|int', $this->i);
		}
	}
} else {
	class NarrowsNativeUnion {
		private readonly int|float $i;

		public function __construct()
		{
			$this->i = getInt();
		}

		public function doFoo(): void {
			assertType('int', $this->i);
			assertNativeType('int', $this->i);
		}
	}
}

function getInt(): int {
	return 1;
}

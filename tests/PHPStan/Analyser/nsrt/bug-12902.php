<?php declare(strict_types = 1);

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class NarrowsNativeUnion {
	private readonly int|float $i;

	public function __construct()
	{
		$this->i = getInt();
		assertType('int', $this->i);
		assertNativeType('int', $this->i);
	}

	public function doFoo(): void {
		assertType('int', $this->i);
		assertNativeType('int', $this->i);
	}
}

class NarrowsStaticNativeUnion {
	private static int|float $i;

	public function __construct()
	{
		self::$i = getInt();
		assertType('int', self::$i);
		assertNativeType('int', self::$i);
	}

	public function doFoo(): void {
		assertType('float|int', self::$i);
		assertNativeType('float|int', self::$i);
	}
}

function getInt(): int {
	return 1;
}

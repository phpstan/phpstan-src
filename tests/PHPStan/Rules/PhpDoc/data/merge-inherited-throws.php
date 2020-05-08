<?php declare(strict_types = 1);

namespace InvalidThrowsPhpDocMergeInherited;

class A {}
class B {}
class C {}
class D {}

class One
{
	/** @throws A */
	public function method(): void {}
}

interface InterfaceOne
{
	/** @throws B */
	public function method(): void;
}

class Two extends One implements InterfaceOne
{
	/**
	 * @throws C
	 * @throws D
	 */
	public function method(): void {}
}

class Three extends Two
{
	/** Some comment */
	public function method(): void {}
}

class Four extends Three
{
	public function method(): void {}
}

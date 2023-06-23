<?php // lint >= 7.4

namespace PHPStan\Generics\TypeProjections;

use function PHPStan\dumpType;

class A {}
class B extends A {}
class C extends B {}

/**
 * @template T
 */
interface Box
{
	/** @param T $item */
	public function pack(mixed $item): void;

	/** @return T */
	public function unpack(): mixed;
}

/**
 * @template T of A
 */
interface BoundedBox
{
	/** @return T */
	public function unpack(): mixed;
}

/**
 * @param Box<covariant B> $box
 */
function testCovariant(Box $box, B $b): void
{
	$box->pack($b);
	dumpType($box->unpack());
}

/**
 * @param Box<contravariant B> $box
 */
function testContravariant(Box $box, A $a, B $b): void
{
	$box->pack($a);
	$box->pack($b);
	dumpType($box->unpack());
}

/**
 * @param BoundedBox<contravariant B> $box
 */
function testContravariantWithBound(BoundedBox $box): void
{
	dumpType($box->unpack());
}

/**
 * @param Box<*> $box
 */
function testStar(Box $box, A $a): void
{
	$box->pack($a);
	dumpType($box->unpack());
}


/**
 * @template T
 */
interface Mapped
{
	/**
	 * @param callable(T): void $mapper
	 */
	public function mapIn(callable $mapper): void;

	/**
	 * @param callable(): T $mapper
	 */
	public function mapOut(callable $mapper): void;
}

/**
 * @param Mapped<covariant B> $mapped
 */
function testCovariantMapped(Mapped $mapped): void
{
	$mapped->mapIn(function ($foo) {
		dumpType($foo);
	});

	$mapped->mapOut(fn() => new A);
	$mapped->mapOut(fn() => new B);
	$mapped->mapOut(fn() => new C);
}

/**
 * @param Mapped<contravariant B> $mapped
 */
function testContravariantMapped(Mapped $mapped): void
{
	$mapped->mapIn(function ($foo) {
		dumpType($foo);
	});

	$mapped->mapOut(fn() => new A);
	$mapped->mapOut(fn() => new B);
	$mapped->mapOut(fn() => new C);
}

/**
 * @param Mapped<*> $mapped
 */
function testStarMapped(Mapped $mapped): void
{
	$mapped->mapIn(function ($foo) {
		dumpType($foo);
	});

	$mapped->mapOut(fn() => new A);
	$mapped->mapOut(fn() => new B);
	$mapped->mapOut(fn() => new C);
}

<?php declare(strict_types = 1);

namespace Bug6574;

interface FooInterface
{
}

interface BarInterface
{
	public function __construct();
}

abstract class AbstractBaz
{
}

abstract class AbstractQux
{
	public function __construct()
	{
	}
}

class NonFinalClass
{
}

interface ThrowsVoidInterface
{
	/** @throws void */
	public function __construct();
}

abstract class AbstractThrowsVoid
{
	/** @throws void */
	public function __construct()
	{
	}
}

final class FinalClass
{
}

/** @param class-string<FooInterface> $class */
function interfaceWithoutConstructor(string $class): void
{
	try {
		new $class();
	} catch (\Exception $e) {
	}
}

/** @param class-string<BarInterface> $class */
function interfaceWithConstructor(string $class): void
{
	try {
		new $class();
	} catch (\Exception $e) {
	}
}

/** @param class-string<AbstractBaz> $class */
function abstractClassWithoutConstructor(string $class): void
{
	try {
		new $class();
	} catch (\Exception $e) {
	}
}

/** @param class-string<AbstractQux> $class */
function abstractClassWithConstructor(string $class): void
{
	try {
		new $class();
	} catch (\Exception $e) {
	}
}

/** @param class-string<NonFinalClass> $class */
function nonFinalClassWithoutConstructor(string $class): void
{
	try {
		new $class();
	} catch (\Exception $e) {
	}
}

/** @param class-string<ThrowsVoidInterface> $class */
function interfaceWithThrowsVoidConstructor(string $class): void
{
	try {
		new $class();
	} catch (\Exception $e) { // dead catch - constructor is @throws void
	}
}

/** @param class-string<AbstractThrowsVoid> $class */
function abstractClassWithThrowsVoidConstructor(string $class): void
{
	try {
		new $class();
	} catch (\Exception $e) { // dead catch - constructor is @throws void
	}
}

/** @param class-string<FinalClass> $class */
function finalClassWithoutConstructor(string $class): void
{
	try {
		new $class();
	} catch (\Exception $e) { // dead catch - final class with no constructor
	}
}

<?php

namespace MethodAssert;

class Foo
{
	/**
	 * @phpstan-assert int $i
	 */
	public function fill(int $i): void
	{
	}

	/**
	 * @template T
	 * @param T $p
	 * @phpstan-assert int $i
	 */
	public function fill2(int $i): array
	{
	}

	/**
	 * @template T of int
	 * @param T $p
	 * @phpstan-assert int $i
	 */
	public function fill3(int $i): array
	{
	}

	/**
	 * @template T of int
	 * @param T $p
	 * @phpstan-assert positive-int $i
	 */
	public function fill4(int $i): array
	{
	}

	/**
	 * @phpstan-assert int|string $i
	 */
	public function fill5(int $i): void
	{
	}

	/**
	 * @phpstan-assert string $i
	 */
	public function fill6(int $i): void
	{
	}

	/**
	 * @phpstan-assert positive-int $j
	 */
	public function doFoo(int $i)
	{
	}

	/**
	 * @phpstan-assert !int $i
	 */
	public function negate(int $i): void
	{
	}

	/**
	 * @phpstan-assert !string $i
	 */
	public function negate1(int $i): void
	{
	}

	/**
	 * @phpstan-assert !positive-int $i
	 */
	public function negate2(int $i): void
	{
	}
}

class AbsentTypeChecks
{

	/** @var string|null */
	public $fooProp;

	/**
	 * @phpstan-assert-if-true string&int $a
	 * @phpstan-assert string&int $this->fooProp
	 */
	public function doFoo($a): bool
	{

	}

	/**
	 * @phpstan-assert Nonexistent $a
	 * @phpstan-assert FooTrait $b
	 * @phpstan-assert fOO $c
	 * @phpstan-assert Foo $this->barProp
	 */
	public function doBar($a, $b, $c): bool
	{

	}

	/**
	 * @phpstan-assert !null $this->fooProp
	 */
	public static function doBaz(): void
	{

	}

}

trait FooTrait
{

}

class InvalidGenerics
{

	/**
	 * @phpstan-assert \Exception<int, float> $m
	 */
	function invalidPhpstanAssertGeneric($m) {

	}

	/**
	 * @phpstan-assert FooBar<mixed> $m
	 */
	function invalidPhpstanAssertWrongGenericParams($m) {

	}

	/**
	 * @phpstan-assert FooBar<int> $m
	 */
	function invalidPhpstanAssertNotAllGenericParams($m) {

	}

	/**
	 * @phpstan-assert FooBar<int, string, float> $m
	 */
	function invalidPhpstanAssertMoreGenericParams($m) {

	}

}


/**
 * @template T of int
 * @template TT of string
 */
class FooBar {
	/**
	 * @param-out T $s
	 */
	function genericClassFoo(mixed &$s): void
	{
	}

	/**
	 * @template S of self
	 * @param-out S $s
	 */
	function genericSelf(mixed &$s): void
	{
	}

	/**
	 * @template S of static
	 * @param-out S $s
	 */
	function genericStatic(mixed &$s): void
	{
	}
}

class MissingTypes
{

	/**
	 * @phpstan-assert array $m
	 */
	public function doFoo($m): void
	{

	}

	/**
	 * @phpstan-assert FooBar $m
	 */
	public function doBar($m): void
	{

	}

	/**
	 * @phpstan-assert callable $m
	 */
	public function doBaz($m): void
	{

	}

}

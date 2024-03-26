<?php

namespace
{
	/**
	 * @param int $a
	 * @param $b
	 */
	function globalFunction($a, $b, $c): bool
	{
		$closure = function($a, $b, $c) {

		};

		return false;
	}
}

namespace MissingFunctionParameterTypehint
{
	/**
	 * @param $d
	 */
	function namespacedFunction($d, bool $e): int {
		return 9;
	};

	/**
	 * @param array|int[] $a
	 */
	function intIterableTypehint($a)
	{

	}

	function missingArrayTypehint(array $a)
	{

	}

	/**
	 * @param array $a
	 */
	function missingPhpDocIterableTypehint(array $a)
	{

	}

	/**
	 * @param mixed[] $a
	 */
	function explicitMixedArrayTypehint(array $a)
	{

	}

	/**
	 * @param \stdClass|array|int|null $a
	 */
	function unionTypeWithUnknownArrayValueTypehint($a)
	{

	}

	/**
	 * @param iterable<int>&\Traversable $a
	 */
	function iterableIntersectionTypehint($a)
	{

	}

	/**
	 * @param iterable<mixed>&\Traversable $a
	 */
	function iterableIntersectionTypehint2($a)
	{

	}

	/**
	 * @param \PDOStatement<int> $a
	 */
	function iterableIntersectionTypehint3($a)
	{

	}

	/**
	 * @param \PDOStatement<mixed> $a
	 */
	function iterableIntersectionTypehint4($a)
	{

	}

	/**
	 * @template T
	 * @template U
	 */
	interface GenericInterface
	{

	}

	class NonGenericClass
	{

	}

	function acceptsGenericInterface(GenericInterface $i)
	{

	}

	function acceptsNonGenericClass(NonGenericClass $c)
	{

	}

	/**
	 * @template A
	 * @template B
	 */
	class GenericClass
	{

	}

	function acceptsGenericClass(GenericClass $c)
	{

	}

	function missingIterableTypehint(iterable $iterable)
	{

	}

	/**
	 * @param iterable $iterable
	 */
	function missingIterableTypehintPhpDoc($iterable)
	{

	}

	function missingTraversableTypehint(\Traversable $traversable)
	{

	}

	/**
	 * @param \Traversable $traversable
	 */
	function missingTraversableTypehintPhpDoc($traversable)
	{

	}

	function missingCallableSignature(callable $cb)
	{

	}

}

namespace MissingParamOutType {
	/**
	 * @param array<int> $a
	 * @param-out array $a
	 */
	function oneArray(&$a): void {

	}

	/**
	 * @param mixed $a
	 * @param-out \ReflectionClass $a
	 */
	function generics(&$a): void {

	}
}

namespace MissingParamClosureThisType {
	/**
	 * @param-closure-this \ReflectionClass $cb
	 * @param callable(): void $cb
	 */
	function generics(callable $cb): void
	{

	}
}

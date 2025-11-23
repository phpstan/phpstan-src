<?php

namespace
{
	function globalFunction1($a, $b, $c)
	{
		return false;
	}

	function globalFunction2($a, $b, $c): bool
	{
		$closure = function($a, $b, $c) {

		};

		return false;
	}

	/**
	 * @return bool
	 */
	function globalFunction3($a, $b, $c)
	{
		return false;
	}
}

namespace MissingFunctionReturnTypehint
{
	function namespacedFunction1($d, $e)
	{
		return 9;
	};

	function namespacedFunction2($d, $e): int
	{
		return 9;
	};

	/**
	 * @return int
	 */
	function namespacedFunction3($d, $e)
	{
		return 9;
	};

	/**
	 * @return \stdClass|array|int|null
	 */
	function unionTypeWithUnknownArrayValueTypehint()
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

	function returnsGenericInterface(): GenericInterface
	{

	}

	function returnsNonGenericClass(): NonGenericClass
	{

	}

	/**
	 * @template A
	 * @template B
	 */
	class GenericClass
	{

	}

	function returnsGenericClass(): GenericClass
	{

	}

	/**
	 * @return GenericClass<GenericClass<int, int>, GenericClass<int, int>>
	 */
	function genericGenericValidArgs(): GenericClass
	{

	}

	/**
	 * @return GenericClass<GenericClass, int>
	 */
	function genericGenericMissingTemplateArgs(): GenericClass
	{

	}

	/**
	 * @return \Closure
	 */
	function closureWithNoPrototype() : \Closure{

	}

	/**
	 * @return \Closure(int) : void
	 */
	function closureWithPrototype() : \Closure{

	}

	/**
	 * @return callable
	 */
	function callableWithNoPrototype() : callable{

	}

	/**
	 * @return callable(int) : void
	 */
	function callableWithPrototype() : callable{

	}

	/**
	 * @return callable(callable) : void
	 */
	function callableNestedNoPrototype() : callable{

	}

	/**
	 * @return callable(callable(int) : void) : void
	 */
	function callableNestedWithPrototype() : callable{

	}

	function returnsGeneratorOfIntegersNoPrototype(): \Generator
	{
		yield 1;
		yield 2;
		yield 3;
	}

	/**
	 * @return \Generator<int, int>
	 */
	function returnsGeneratorOfIntegersWithPrototype(): \Generator
	{
		yield 1;
		yield 2;
		yield 3;
	}

	function returnsGeneratorOfIntegersByStringNoPrototype(): \Generator
	{
		yield '1' => 1;
		yield '2' => 2;
		yield '3' => 3;
	}

	/**
	 * @return \Generator<string, int>
	 */
	function returnsGeneratorOfIntegersByStringWithPrototype(): \Generator
	{
		yield '1' => 1;
		yield '2' => 2;
		yield '3' => 3;
	}

	function returnsIteratorNoPrototype(): \Iterator
	{
		yield 'test';
	}

	/**
	 * @return \Iterator<array-key,string>
	 */
	function returnsIteratorWithPrototype(): \Iterator
	{
		yield 'test';
	}

	function returnsIteratorAggregateNoPrototype(): \IteratorAggregate
	{
		return new \ArrayObject([]);
	}

	/**
	 * @return \IteratorAggregate<array-key,string>
	 */
	function returnsIteratorAggregateWithPrototype(): \IteratorAggregate
	{
		return new \ArrayObject([]);
	}

	function returnsTraversableNoPrototype(): \Traversable
	{
		yield 'test';
	}

	/**
	 * @return \Traversable<array-key,string>
	 */
	function returnsTraversableWithPrototype(): \Traversable
	{
		yield 'test';
	}
}

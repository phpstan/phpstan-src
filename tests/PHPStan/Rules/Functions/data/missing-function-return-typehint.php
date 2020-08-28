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
}

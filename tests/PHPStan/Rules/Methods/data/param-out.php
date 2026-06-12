<?php // lint >= 8.0

namespace ParamOutTemplate;


/**
 * @template S
 */
class FooBar
{
	/**
	 * @param-out S $s
	 */
	function usingClassTemplate(mixed &$s): void
	{
	}

	/**
	 * @template T
	 * @param-out T $s
	 */
	function uselessLocalTemplate(mixed &$s): void
	{
	}
}

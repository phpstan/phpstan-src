<?php

namespace Bug4729;

/** @template T of int */
interface I
{
	/**
	 * @return static
	 */
	function get(): I;
}

/**
 * @template T of int
 * @implements I<T>
 */
final class B implements I
{
	function get(): I
	{
		return $this;
	}
}

/**
 * @template T of int
 * @implements I<T>
 */
class C implements I
{
	function get(): I
	{
		return $this;
	}
}

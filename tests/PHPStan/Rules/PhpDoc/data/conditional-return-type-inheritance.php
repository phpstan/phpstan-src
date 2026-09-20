<?php // lint >= 8.0

declare(strict_types = 1);

namespace ConditionalReturnTypeInheritance;

/**
 * @template T of int|string
 */
interface Iface
{

	/**
	 * @param T $val
	 * @return (T is int ? string : int)
	 */
	public function fromTemplate($val);

	/**
	 * @param T $val
	 * @return ($val is int ? string : int)
	 */
	public function fromParameter($val);

	/**
	 * @param T $val
	 * @return (T is not int ? string : int)
	 */
	public function negated($val);

	/**
	 * @param T $val
	 * @return list<(T is int ? string : int)>
	 */
	public function nested($val);

	/**
	 * @param T $val
	 * @param-out (T is int ? string : int) $ref
	 */
	public function paramOut($val, &$ref): void;

	/**
	 * @param T $val
	 * @param (T is int ? string : int) $other
	 */
	public function inParam($val, $other): void;

}

/**
 * @implements Iface<string>
 */
final class Impl implements Iface
{

	public function fromTemplate($val)
	{
		return 1;
	}

	public function fromParameter($val)
	{
		return 1;
	}

	public function negated($val)
	{
		return 'foo';
	}

	public function nested($val)
	{
		return [];
	}

	public function paramOut($val, &$ref): void
	{
	}

	public function inParam($val, $other): void
	{
	}

}

/**
 * @template U of int|string
 * @extends Iface<U>
 */
interface StillGeneric extends Iface
{

}

/**
 * @template T of int|string
 */
trait Tr
{

	/**
	 * @return (T is int ? string : int)
	 */
	abstract public function fromTrait();

}

/**
 * @template T of int|string
 */
abstract class Base
{

	/** @use Tr<T> */
	use Tr;

	/**
	 * @return (T is int ? string : int)
	 */
	abstract public function fromBase();

}

/**
 * @extends Base<string>
 */
abstract class Mid extends Base
{

}

final class Leaf extends Mid
{

	public function fromTrait()
	{
		return 1;
	}

	public function fromBase()
	{
		return 1;
	}

}

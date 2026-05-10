<?php declare(strict_types = 1);

namespace Bug11894Methods;

class Converter
{
	/**
	 * @template T
	 * @param T $a
	 * @return (T is string ? string : T)
	 */
	public function conditionalReturn(mixed $a): mixed
	{
		if (!is_string($a)) {
			return $a;
		}
		return trim($a);
	}

	/**
	 * @template T
	 * @param T $a
	 * @return (T is string ? string : T)
	 */
	public static function conditionalReturnStatic(mixed $a): mixed
	{
		if (!is_string($a)) {
			return $a;
		}
		return trim($a);
	}
}

class Consumer
{
	/**
	 * @template T of string|null
	 * @param T $a
	 */
	public function testMethod(mixed $a): mixed
	{
		if (!is_string($a)) {
			return $a;
		}

		$c = new Converter();
		return $c->conditionalReturn($a);
	}

	/**
	 * @template T of string|null
	 * @param T $a
	 */
	public function testStaticMethod(mixed $a): mixed
	{
		if (!is_string($a)) {
			return $a;
		}

		return Converter::conditionalReturnStatic($a);
	}
}

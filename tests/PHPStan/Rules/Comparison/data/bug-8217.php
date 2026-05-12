<?php declare(strict_types = 1);

namespace Bug8217;

class HelloWorld
{
	/**
	 * @template T
	 *
	 * @param T $object
	 */
	public static function checkTemplate($object): void
	{
		if (is_object($object) && method_exists($object, 'method')) {
			echo 1;
		}
	}

	public static function checkMixed(mixed $value): void
	{
		if (method_exists($value, 'method')) {
			echo 1;
		}
	}

	public static function checkObject(object $object): void
	{
		if (method_exists($object, 'method')) {
			echo 1;
		}
	}

	/**
	 * @template T
	 *
	 * @param T $object
	 */
	public static function checkPropertyExistsTemplate($object): void
	{
		if (is_object($object) && property_exists($object, 'prop')) {
			echo 1;
		}
	}

	public static function checkPropertyExistsMixed(mixed $value): void
	{
		if (property_exists($value, 'prop')) {
			echo 1;
		}
	}

	public static function checkPropertyExistsObject(object $object): void
	{
		if (property_exists($object, 'prop')) {
			echo 1;
		}
	}
}

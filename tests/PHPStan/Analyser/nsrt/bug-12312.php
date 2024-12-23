<?php declare(strict_types = 1);

namespace Bug12312;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param lowercase-string $s
	 */
	public function sayLowercase(string $s): void
	{
		if ($s != '') {
			assertType('lowercase-string&non-empty-string', $s);
		}
		assertType('lowercase-string', $s);
	}

	/**
	 * @param lowercase-string $s
	 */
	public function sayLowercase2(string $s): void
	{
		if ('' != $s) {
			assertType('lowercase-string&non-empty-string', $s);
		}
		assertType('lowercase-string', $s);
	}

	/**
	 * @param lowercase-string&non-empty-string $s
	 */
	public function sayLowercase3(string $s): void
	{
		if ($s != '0') {
			assertType('lowercase-string&non-falsy-string', $s);
		}
		assertType('lowercase-string&non-empty-string', $s);
	}

	/**
	 * @param lowercase-string&non-empty-string $s
	 */
	public function sayLowercase4(string $s): void
	{
		if ('0' != $s) {
			assertType('lowercase-string&non-falsy-string', $s);
		}
		assertType('lowercase-string&non-empty-string', $s);
	}

	/**
	 * @param uppercase-string $s
	 */
	public function sayUppercase(string $s): void
	{
		if ($s != '') {
			assertType('non-empty-string&uppercase-string', $s);
		}
		assertType('uppercase-string', $s);
	}

	/**
	 * @param uppercase-string $s
	 */
	public function sayUppercase2(string $s): void
	{
		if ('' != $s) {
			assertType('non-empty-string&uppercase-string', $s);
		}
		assertType('uppercase-string', $s);
	}

	/**
	 * @param uppercase-string&non-empty-string $s
	 */
	public function sayUppercase3(string $s): void
	{
		if ($s != '0') {
			assertType('non-falsy-string&uppercase-string', $s);
		}
		assertType('non-empty-string&uppercase-string', $s);
	}

	/**
	 * @param uppercase-string&non-empty-string $s
	 */
	public function sayUppercase4(string $s): void
	{
		if ('0' != $s) {
			assertType('non-falsy-string&uppercase-string', $s);
		}
		assertType('non-empty-string&uppercase-string', $s);
	}

	/**
	 * @param lowercase-string&uppercase-string $s
	 */
	public function sayBoth(string $s): void
	{
		if ($s != '') {
			assertType('lowercase-string&non-empty-string&uppercase-string', $s);
		}
		assertType('lowercase-string&uppercase-string', $s);
	}

	/**
	 * @param lowercase-string&uppercase-string $s
	 */
	public function sayBoth2(string $s): void
	{
		if ('' != $s) {
			assertType('lowercase-string&non-empty-string&uppercase-string', $s);
		}
		assertType('lowercase-string&uppercase-string', $s);
	}

	/**
	 * @param lowercase-string&uppercase-string&non-empty-string $s
	 */
	public function sayBoth3(string $s): void
	{
		if ($s != '0') {
			assertType('lowercase-string&non-falsy-string&uppercase-string', $s);
		}
		assertType('lowercase-string&non-empty-string&uppercase-string', $s);
	}

	/**
	 * @param lowercase-string&uppercase-string&non-empty-string $s
	 */
	public function sayBoth4(string $s): void
	{
		if ('0' != $s) {
			assertType('lowercase-string&non-falsy-string&uppercase-string', $s);
		}
		assertType('lowercase-string&non-empty-string&uppercase-string', $s);
	}
}

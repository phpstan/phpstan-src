<?php

namespace NonEmptyString;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(string $s): void
	{
		if ($s === '') {
			return;
		}

		assertType('non-empty-string', $s);
	}

	public function doBar(string $s): void
	{
		if ($s !== '') {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doFoo2(string $s): void
	{
		if (strlen($s) === 0) {
			return;
		}

		assertType('non-empty-string', $s);
	}

	public function doBar2(string $s): void
	{
		if (strlen($s) > 0) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doBar3(string $s): void
	{
		if (strlen($s) >= 1) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doFoo5(string $s): void
	{
		if (0 === strlen($s)) {
			return;
		}

		assertType('non-empty-string', $s);
	}

	public function doBar4(string $s): void
	{
		if (0 < strlen($s)) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doBar5(string $s): void
	{
		if (1 <= strlen($s)) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doFoo3(string $s): void
	{
		if ($s) {
			assertType('non-empty-string', $s);
		} else {
			assertType('\'\'|\'0\'', $s);
		}
	}

	/**
	 * @param non-empty-string $s
	 */
	public function doFoo4(string $s): void
	{
		assertType('array<int, string>&nonEmpty', explode($s, 'foo'));
	}

	/**
	 * @param non-empty-string $s
	 */
	public function doWithNumeric(string $s): void
	{
		if (!is_numeric($s)) {
			return;
		}

		assertType('non-empty-string', $s);
	}

	public function doEmpty(string $s): void
	{
		if (empty($s)) {
			return;
		}

		assertType('non-empty-string', $s);
	}

	public function doEmpty2(string $s): void
	{
		if (!empty($s)) {
			assertType('non-empty-string', $s);
		}
	}

}

class ImplodingStrings
{

	/**
	 * @param array<string> $commonStrings
	 */
	public function doFoo(string $s, array $commonStrings): void
	{
		assertType('string', implode($s, $commonStrings));
		assertType('string', implode(' ', $commonStrings));
		assertType('string', implode('', $commonStrings));
		assertType('string', implode($commonStrings));
	}

	/**
	 * @param non-empty-array<string> $nonEmptyArrayWithStrings
	 */
	public function doFoo2(string $s, array $nonEmptyArrayWithStrings): void
	{
		assertType('string', implode($s, $nonEmptyArrayWithStrings));
		assertType('string', implode('', $nonEmptyArrayWithStrings));
		assertType('non-empty-string', implode(' ', $nonEmptyArrayWithStrings));
		assertType('string', implode($nonEmptyArrayWithStrings));
	}

	/**
	 * @param array<non-empty-string> $arrayWithNonEmptyStrings
	 */
	public function doFoo3(string $s, array $arrayWithNonEmptyStrings): void
	{
		assertType('string', implode($s, $arrayWithNonEmptyStrings));
		assertType('string', implode('', $arrayWithNonEmptyStrings));
		assertType('string', implode(' ', $arrayWithNonEmptyStrings));
		assertType('string', implode($arrayWithNonEmptyStrings));
	}

	/**
	 * @param non-empty-array<non-empty-string> $nonEmptyArrayWithNonEmptyStrings
	 */
	public function doFoo4(string $s, array $nonEmptyArrayWithNonEmptyStrings): void
	{
		assertType('non-empty-string', implode($s, $nonEmptyArrayWithNonEmptyStrings));
		assertType('non-empty-string', implode('', $nonEmptyArrayWithNonEmptyStrings));
		assertType('non-empty-string', implode(' ', $nonEmptyArrayWithNonEmptyStrings));
		assertType('non-empty-string', implode($nonEmptyArrayWithNonEmptyStrings));
	}

	public function sayHello(): void
	{
		// coming from issue #5291
		$s = array(1,2);

		assertType('non-empty-string', implode("a", $s));
	}

	/**
	 * @param non-empty-string $glue
	 */
	public function nonE($glue, array $a) {
		// coming from issue #5291
		if (empty($a)) {
			return "xyz";
		}

		assertType('non-empty-string', implode($glue, $a));
	}

	public function sayHello2(): void
	{
		// coming from issue #5291
		$s = array(1,2);

		assertType('non-empty-string', join("a", $s));
	}

	/**
	 * @param non-empty-string $glue
	 */
	public function nonE2($glue, array $a) {
		// coming from issue #5291
		if (empty($a)) {
			return "xyz";
		}

		assertType('non-empty-string', join($glue, $a));
	}

}

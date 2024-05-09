<?php

namespace NonEmptyStringStrContains;

use function PHPStan\Testing\assertType;

class Foo {
	/**
	 * @param non-empty-string $nonES
	 * @param non-falsy-string $nonFalsy
	 * @param numeric-string $numS
	 * @param literal-string $literalS
	 * @param non-empty-string&numeric-string $nonEAndNumericS
	 */
	public function strContains(string $s, string $s2, $nonES, $nonFalsy, $numS, $literalS, $nonEAndNumericS, int $i): void
	{
		if (str_contains($s, ':')) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (str_contains($s, '0')) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (str_contains($s, $s2)) {
			assertType('string', $s);
		}

		if (str_contains($s, $nonES)) {
			assertType('non-empty-string', $s);
		}
		if (str_contains($s, $numS)) {
			assertType('non-empty-string', $s);
		}
		if (str_contains($s, $literalS)) {
			assertType('string', $s);
		}

		if (str_contains($s, $nonEAndNumericS)) {
			assertType('non-empty-string', $s);
		}
		if (str_contains($numS, $nonEAndNumericS)) {
			assertType('non-empty-string&numeric-string', $numS);
		}

		if (str_contains($nonES, $s)) {
			assertType('non-empty-string', $nonES);
		}
		if (str_contains($nonEAndNumericS, $s)) {
			assertType('non-empty-string&numeric-string', $nonEAndNumericS);
		}

		if (str_contains($i, $s2)) {
			assertType('int', $i);
		}

		if (str_contains($s, $nonFalsy)) {
			assertType('non-falsy-string', $s);
		}
		if (str_contains($numS, $nonFalsy)) {
			assertType('non-falsy-string&numeric-string', $numS);
		}
	}

	public function variants(string $s) {
		if (fnmatch("*gr[ae]y", $s)) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (str_starts_with($s, ':')) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (str_ends_with($s, ':')) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (strpos($s, ':') !== false) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if (strpos($s, ':') === false) {
			assertType('string', $s);
		}
		assertType('string', $s);

		if (strpos($s, ':') === 5) {
			assertType('string', $s); // could be non-empty-string
		}
		assertType('string', $s);
		if (strpos($s, ':') !== 5) {
			assertType('string', $s);
		}
		assertType('string', $s);

		if (strrpos($s, ':') !== false) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (stripos($s, ':') !== false) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (strripos($s, ':') !== false) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (strstr($s, ':') === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if (strstr($s, ':', true) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if (strstr($s, ':', true) !== false) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if (strstr($s, ':', true) === false) {
			assertType('string', $s);
		} else {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (mb_strpos($s, ':') !== false) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if (mb_strpos($s, ':') === false) {
			assertType('string', $s);
		}
		assertType('string', $s);

		if (mb_strpos($s, ':') === 5) {
			assertType('string', $s); // could be non-empty-string
		}
		assertType('string', $s);
		if (mb_strpos($s, ':') !== 5) {
			assertType('string', $s);
		}
		assertType('string', $s);

		if (mb_strrpos($s, ':') !== false) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (mb_stripos($s, ':') !== false) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (mb_strripos($s, ':') !== false) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (mb_strstr($s, ':') === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if (mb_strstr($s, ':', true) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if (mb_strstr($s, ':', true) !== false) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if (mb_strstr($s, ':', true) === false) {
			assertType('string', $s);
		} else {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
	}

}

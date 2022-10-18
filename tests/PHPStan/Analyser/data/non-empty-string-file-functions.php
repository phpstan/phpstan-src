<?php

namespace NonEmptyStringFileFamily;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param non-empty-string $nonES
	 * @param non-falsy-string $nonFalsyString
	 * @param numeric-string $numericString
	 */
	public function strTypes($nonES, $nonFalsyString, $numericString) {
		if (is_dir($nonES)) {
			assertType('non-empty-string', $nonES);
		}
		assertType('non-empty-string', $nonES);

		if (is_dir($nonFalsyString)) {
			assertType('non-falsy-string', $nonFalsyString);
		}
		assertType('non-falsy-string', $nonFalsyString);

		if (is_dir($numericString)) {
			assertType('non-empty-string&numeric-string', $numericString);
		}
		assertType('numeric-string', $numericString);
	}

	public function fileNonEmptyStrings(string $s): void
	{
		if (dir($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (chdir($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (chroot($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (opendir($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (scandir($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (file_exists($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (is_file($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (is_dir($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (is_writable($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (is_readable($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (is_executable($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (file($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (file_get_contents($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (file_put_contents($s, '')) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (fileatime($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (filectime($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (filegroup($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (fileinode($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (filemtime($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (fileowner($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (fileperms($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (filesize($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (filetype($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (fopen($s, "r")) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (linkinfo($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (lstat($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (mkdir($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (readfile($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (readlink($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (realpath($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (rmdir($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (stat($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (touch($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (unlink($s)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

	}

}

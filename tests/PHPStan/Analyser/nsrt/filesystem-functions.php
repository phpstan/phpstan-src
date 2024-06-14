<?php

namespace FilesystemFunctions;

use function PHPStan\Testing\assertType;

class Bug5461
{

	public function run(): void
	{
		$fh = fopen('foo.txt', 'r');
		if ($fh === false) {
			throw new \InvalidArgumentException('Could not open file');
		}

		while (!feof($fh)) {
			$data = (string)fread($fh, 1024);
			assertType('bool', feof($fh));
		}

		fclose($fh);
	}

}

class MoreTests
{

	public function test1(): void
	{
		if (fopen('foo.txt', 'r') === false) {
			assertType('resource|false', fopen('foo.txt', 'r'));
		}
	}

	/**
	 * @param resource $fh
	 */
	public function test2($fh): void
	{
		if (fread($fh, 4) === 'data') {
			assertType('string|false', fread($fh, 4));
		}
	}

	/**
	 * @param resource $fh
	 */
	public function test3($fh): void
	{
		if (ftell($fh) === 0) {
			assertType('0', ftell($fh));
			fseek($fh, 10);
			assertType('int|false', ftell($fh));
		}
	}

	public function test4(string $path): void
	{
		if (file_get_contents($path) === 'data') {
			assertType('string|false', file_get_contents($path));
			file_put_contents($path, 'other');
			assertType('string|false', file_get_contents($path));
		}
	}

}

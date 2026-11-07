<?php declare(strict_types = 1);

namespace Bug8435;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello1(string $path): void
	{
		$iterator = new RecursiveDirectoryIterator($path);
		foreach ($iterator as $fileinfo) {
			assertType('SplFileInfo|string', $fileinfo);
		}
	}

	public function sayHello2(string $path): void
	{
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
		foreach ($iterator as $fileinfo) {
			assertType('SplFileInfo|string', $fileinfo);
		}
	}

	/**
	 * @param RecursiveIteratorIterator<RecursiveDirectoryIterator> $iterator
	 */
	public function test(RecursiveIteratorIterator $iterator): void
	{
		foreach ($iterator as $fileinfo) {
			assertType('SplFileInfo|string', $fileinfo);
		}
	}
}

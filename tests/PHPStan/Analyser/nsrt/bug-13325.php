<?php

namespace Bug13325Nsrt;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param \Iterator<int, string> $iterator
	 */
	public function doFoo(\Iterator $iterator): void
	{
		$regexIterator = new \RegexIterator($iterator, '/pattern/');
		assertType('RegexIterator<int, string, Iterator<int, string>>', $regexIterator);
	}

	public function doBar(): void
	{
		$regexIterator = new \RegexIterator(new \FilesystemIterator('foo'), '/pattern/');
		assertType('RegexIterator<mixed, mixed, FilesystemIterator>', $regexIterator);
	}

}

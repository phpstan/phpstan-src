<?php declare(strict_types = 1);

namespace RecursiveIteratorsStub;

use ParentIterator;
use RecursiveArrayIterator;
use RecursiveCachingIterator;
use RecursiveRegexIterator;
use SplFileInfo;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param ParentIterator<string, SplFileInfo, \RecursiveDirectoryIterator> $parent
	 * @param RecursiveCachingIterator<string, SplFileInfo, \RecursiveDirectoryIterator> $caching
	 * @param RecursiveRegexIterator<string, SplFileInfo, \RecursiveDirectoryIterator> $regex
	 */
	public function doFoo($parent, $caching, $regex): void
	{
		foreach ($parent as $key => $value) {
			assertType('string', $key);
			assertType('SplFileInfo', $value);
		}
		foreach ($caching as $key => $value) {
			assertType('string', $key);
			assertType('SplFileInfo', $value);
		}
		foreach ($regex as $key => $value) {
			assertType('string', $key);
			assertType('SplFileInfo', $value);
		}

		assertType('ParentIterator<string, SplFileInfo, RecursiveDirectoryIterator>', $parent->getChildren());
		assertType('RecursiveCachingIterator<string, SplFileInfo, RecursiveDirectoryIterator>', $caching->getChildren());
		assertType('RecursiveRegexIterator<string, SplFileInfo, RecursiveDirectoryIterator>', $regex->getChildren());

		assertType('Iterator<string, SplFileInfo>', $parent->getInnerIterator());
	}

	/**
	 * @param RecursiveArrayIterator<int, string> $it
	 */
	public function doBar($it): void
	{
		$caching = new RecursiveCachingIterator($it);
		assertType('RecursiveCachingIterator<int, string, RecursiveArrayIterator<int, string>>', $caching);
		assertType('string', $caching->current());
		assertType('int', $caching->key());

		$parent = new ParentIterator($it);
		assertType('ParentIterator<int, string, RecursiveArrayIterator<int, string>>', $parent);

		$regex = new RecursiveRegexIterator($it, '~foo~');
		assertType('RecursiveRegexIterator<int, string, RecursiveArrayIterator<int, string>>', $regex);
		assertType('string', $regex->current());
		assertType('int', $regex->key());
	}

}

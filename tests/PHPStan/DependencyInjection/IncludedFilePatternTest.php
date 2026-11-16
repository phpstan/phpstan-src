<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPUnit\Framework\TestCase;

class IncludedFilePatternTest extends TestCase
{

	public function testPatternMatchesFilesInAlphabeticalOrder(): void
	{
		// subdir.neon is a directory and c.txt does not match, so neither is included
		$dir = __DIR__ . '/globIncludes/baselines';
		$this->assertSame([$dir . '/a.neon', $dir . '/b.neon'], IncludedFilePattern::expand($dir . '/*.neon'));
	}

	public function testPatternMatchingNothingIsEmpty(): void
	{
		$this->assertSame([], IncludedFilePattern::expand(__DIR__ . '/globIncludes/nonexistent/*.neon'));
	}

	public function testPlainPathIsNotAPattern(): void
	{
		$this->assertNull(IncludedFilePattern::expand(__DIR__ . '/globIncludes/main.neon'));
		$this->assertNull(IncludedFilePattern::expand(__DIR__ . '/globIncludes/missing.neon'));
	}

	public function testExistingFileWithMetacharactersIsNotAPattern(): void
	{
		$this->assertNull(IncludedFilePattern::expand(__DIR__ . '/globIncludes/literal/[literal].neon'));
	}

}

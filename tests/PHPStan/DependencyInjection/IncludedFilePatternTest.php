<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\FileNotFoundException;
use PHPUnit\Framework\TestCase;
use function sprintf;

class IncludedFilePatternTest extends TestCase
{

	private const DIR = __DIR__ . '/globIncludes';

	public function testPatternMatchesFilesInAlphabeticalOrder(): void
	{
		// subdir.neon is a directory and c.txt does not match, so neither is included
		$mainFile = self::DIR . '/main.neon';
		$this->assertSame(
			[self::DIR . '/baselines/a.neon', self::DIR . '/baselines/b.neon'],
			IncludedFilePattern::expand('baselines/*.neon', $mainFile, [$mainFile]),
		);
	}

	public function testAbsolutePattern(): void
	{
		$mainFile = self::DIR . '/main.neon';
		$this->assertSame(
			[self::DIR . '/baselines/a.neon', self::DIR . '/baselines/b.neon'],
			IncludedFilePattern::expand(self::DIR . '/baselines/*.neon', $mainFile, [$mainFile]),
		);
	}

	public function testPatternDoesNotMatchFilesBeingLoaded(): void
	{
		$mainFile = self::DIR . '/selfIncluding/_loader.neon';
		$this->assertSame(
			[self::DIR . '/selfIncluding/sibling.neon'],
			IncludedFilePattern::expand('*.neon', $mainFile, [self::DIR . '/main.neon', self::DIR . '/selfIncluding/../selfIncluding/_loader.neon']),
		);
	}

	public function testPatternMatchingNothingIsEmpty(): void
	{
		$mainFile = self::DIR . '/main.neon';
		$this->assertSame([], IncludedFilePattern::expand('baselines/*.php', $mainFile, [$mainFile]));
	}

	public function testPatternInMissingDirectoryFails(): void
	{
		$mainFile = self::DIR . '/main.neon';

		$this->expectException(FileNotFoundException::class);
		$this->expectExceptionMessage(sprintf("Directory '%s/nonexistent/' of the included pattern 'nonexistent/*.neon' is missing.", self::DIR));
		IncludedFilePattern::expand('nonexistent/*.neon', $mainFile, [$mainFile]);
	}

	public function testDirectoryOfPatternIsTheOneBeforeTheFirstMetacharacter(): void
	{
		// baselines/ exists, so this matches nothing rather than failing on the missing baselines/*/
		$mainFile = self::DIR . '/main.neon';
		$this->assertSame([], IncludedFilePattern::expand('baselines/*/missing/*.neon', $mainFile, [$mainFile]));

		$this->expectException(FileNotFoundException::class);
		IncludedFilePattern::expand('nonexistent/sub/*/x.neon', $mainFile, [$mainFile]);
	}

	public function testPatternIsResolvedLiterallyAgainstDirectoryWithMetacharacters(): void
	{
		$mainFile = self::DIR . '/bracketed [dir]/loader.neon';
		$this->assertSame(
			[self::DIR . '/bracketed [dir]/baselines/bracketed.neon'],
			IncludedFilePattern::expand('baselines/*.neon', $mainFile, [$mainFile]),
		);
	}

	public function testPlainPathIsNotAPattern(): void
	{
		$mainFile = self::DIR . '/main.neon';
		$this->assertNull(IncludedFilePattern::expand('empty.neon', $mainFile, [$mainFile]));
		$this->assertNull(IncludedFilePattern::expand('missing.neon', $mainFile, [$mainFile]));
	}

	public function testPlainPathInDirectoryWithMetacharactersIsNotAPattern(): void
	{
		// so that a missing include is still reported as missing
		$mainFile = self::DIR . '/bracketed [dir]/loader.neon';
		$this->assertNull(IncludedFilePattern::expand('missing.neon', $mainFile, [$mainFile]));
	}

	public function testExistingFileWithMetacharactersIsNotAPattern(): void
	{
		$mainFile = self::DIR . '/main.neon';
		$this->assertNull(IncludedFilePattern::expand('literal/[literal].neon', $mainFile, [$mainFile]));
	}

}

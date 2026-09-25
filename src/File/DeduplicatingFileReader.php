<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\LruCache;

/**
 * Reads a file and, when its contents did not change since the last read,
 * hands out the string of that read instead.
 *
 * Reflections keep the contents of their file in a LocatedSource, and a file is
 * read once per symbol located in it (and once per anonymous class declared in
 * it). Sharing one string per unchanged file keeps a large file in memory once
 * instead of once per symbol.
 */
#[AutowiredService]
final class DeduplicatingFileReader
{

	/**
	 * Only the entry count is bounded: the reflections created from a file keep
	 * its contents alive anyway, so evicting a large file would free nothing and
	 * only bring the duplicates back.
	 */
	private const CONTENTS_COUNT_LIMIT = 256;

	/** @var LruCache<string> path => contents */
	private LruCache $contentsByFile;

	public function __construct()
	{
		$this->contentsByFile = new LruCache(self::CONTENTS_COUNT_LIMIT);
	}

	/**
	 * @throws CouldNotReadFileException
	 */
	public function read(string $fileName): string
	{
		$contents = FileReader::read($fileName);
		$previousContents = $this->contentsByFile->get($fileName);
		if ($previousContents === $contents) {
			return $previousContents;
		}

		$this->contentsByFile->set($fileName, $contents, 0);

		return $contents;
	}

}

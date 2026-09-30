<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileContentHasher;

/**
 * The value behind DependencyEmitter::fileDependency(): the hash of the file's contents, or that
 * the file does not exist. Any change of the file - also its creation or deletion - re-analyses the
 * files depending on it, whatever happened to what it declares.
 *
 * The path is stored relative to the same directory as the other paths in the result cache.
 */
#[AutowiredService]
final class FileResultCacheValueExtension implements ResultCacheValueExtension
{

	private const MISSING_FILE = 'missing';

	private ResultCachePathTransformer $pathTransformer;

	public function __construct(
		private FileContentHasher $fileContentHasher,
		#[AutowiredParameter(ref: '%rootDir%')]
		string $anchorDirectory,
	)
	{
		$this->pathTransformer = new ResultCachePathTransformer($anchorDirectory);
	}

	public function getValue(string $key): string
	{
		$hash = $this->fileContentHasher->hash($key);
		if ($hash === false) {
			return self::MISSING_FILE;
		}

		return $hash;
	}

	public function keyToResultCache(string $key): string
	{
		return $this->pathTransformer->relativizePath($key);
	}

	public function keyFromResultCache(string $storedKey): string
	{
		return $this->pathTransformer->absolutizePath($storedKey);
	}

}

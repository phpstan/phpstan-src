<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\DependencyInjection\AutowiredService;
use function hash_file;
use function stat;

/**
 * Memoizes SHA-256 content hashes per process for the lifetime of a run.
 *
 * PHPStan hashes the same file repeatedly — once per located identifier in
 * OptimizedDirectorySourceLocator, once per PHPDoc cache check in
 * FileTypeMapper, once per directory fingerprint in the source-locator
 * factory. Project content is already treated as immutable for the duration
 * of a run (the result cache and the odsl fingerprints rely on this), so a
 * repeated hash of an unchanged file is pure waste. An mtime+size
 * revalidation guards the memo against files regenerated mid-run at the
 * cost of one stat() per repeated ask.
 */
#[AutowiredService]
final class FileContentHasher
{

	/** @var array<string, array{int, int, string}> path => [mtime, size, sha256] */
	private array $hashes = [];

	public function hash(string $file): string|false
	{
		$stat = @stat($file);
		if ($stat === false) {
			return false;
		}

		$memo = $this->hashes[$file] ?? null;
		if ($memo !== null && $memo[0] === $stat['mtime'] && $memo[1] === $stat['size']) {
			return $memo[2];
		}

		$hash = hash_file('sha256', $file);
		if ($hash === false) {
			return false;
		}

		$this->hashes[$file] = [$stat['mtime'], $stat['size'], $hash];

		return $hash;
	}

}

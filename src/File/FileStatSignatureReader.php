<?php declare(strict_types = 1);

namespace PHPStan\File;

use function sprintf;
use function stat;
use function str_contains;

/**
 * Signatures of files and directories read after FileStatSignatures::begin(). A signature is null
 * when the stat cannot vouch for the contents: the caller then neither reuses what it derived from
 * them before nor keeps the signature for next time.
 *
 * That includes a file or directory modified in the second the reading began or later. Timestamps
 * have a one-second granularity, so it could be written again within that second, after it was
 * read, and keep its signature over different contents. Everything modified before that second
 * was read afterwards, so any later change moves its timestamps.
 */
final class FileStatSignatureReader
{

	public function __construct(
		private int $startedAt,
		private bool $trusted,
	)
	{
	}

	public function get(string $path): ?string
	{
		if (!$this->trusted || str_contains($path, '://')) {
			return null;
		}

		$stat = @stat($path);
		if ($stat === false) {
			return null;
		}

		return $this->fromStat($stat);
	}

	/**
	 * @param array<int|string, int> $stat what stat() returned for the path
	 */
	public function fromStat(array $stat): ?string
	{
		if (!$this->trusted || $stat['mtime'] >= $this->startedAt || $stat['ctime'] >= $this->startedAt) {
			return null;
		}

		return sprintf('%d:%d:%d:%d:%d', $stat['size'], $stat['mtime'], $stat['ctime'], $stat['ino'], $stat['dev']);
	}

}

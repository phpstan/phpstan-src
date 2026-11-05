<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

/**
 * Where the data of each collector is in the result cache file, file by file, and the hash of
 * each file's values.
 *
 * The signature identifies the cache file the positions are valid for, so that a reader does not
 * read them from a file that another run has renamed over it since.
 */
final class CollectorLocations
{

	/**
	 * @param array<string, array<string, array{int, int}>> $positions collector type => analysed file => [offset, length], in the order of the offsets
	 * @param array<string, array<string, string>> $hashes collector type => analysed file => hash of its values
	 */
	public function __construct(
		private string $signature,
		private array $positions,
		private array $hashes,
	)
	{
	}

	public function getSignature(): string
	{
		return $this->signature;
	}

	/**
	 * @return array<string, array{int, int}>
	 */
	public function getPositions(string $collectorType): array
	{
		return $this->positions[$collectorType] ?? [];
	}

	/**
	 * md5 of serialize() of each file's values, with absolute paths in them.
	 *
	 * @return array<string, string>
	 */
	public function getHashes(string $collectorType): array
	{
		return $this->hashes[$collectorType] ?? [];
	}

}

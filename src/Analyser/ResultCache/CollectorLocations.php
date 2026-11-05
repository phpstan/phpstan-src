<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

/**
 * Where the data of each collector is in the result cache file, file by file.
 *
 * The signature identifies the cache file the positions are valid for, so that a reader does not
 * read them from a file that another run has renamed over it since.
 */
final class CollectorLocations
{

	/**
	 * @param array<string, array<string, array{int, int}>> $positions collector type => analysed file => [offset, length], in the order of the offsets
	 */
	public function __construct(
		private string $signature,
		private array $positions,
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

}

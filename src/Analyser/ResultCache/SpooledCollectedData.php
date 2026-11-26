<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Generator;
use PhpParser\Node;
use PHPStan\Collectors\Collector;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function fclose;

/**
 * Where the values of each collector and analysed file are in a CollectedDataSpool, with the hash of
 * the values. The hash is the same as the one the result cache stores for the same values.
 */
final class SpooledCollectedData
{

	/**
	 * @param array<class-string<Collector<Node, mixed>>, array<string, array{int<0, max>, int<1, max>, string}>> $entries collector type => file => [offset, length, hash]
	 * @param array<string, true> $files every file in $entries, in the order they came in
	 */
	public function __construct(
		private CollectedDataSpool $spool,
		private array $entries,
		private array $files,
	)
	{
	}

	/**
	 * @return array<string, true>
	 */
	public function getFiles(): array
	{
		return $this->files;
	}

	public function isEmpty(): bool
	{
		return $this->entries === [];
	}

	/**
	 * @param array<string, true> $files
	 */
	public function only(array $files): self
	{
		$entries = [];
		foreach ($this->entries as $collectorType => $entriesPerFile) {
			$kept = array_intersect_key($entriesPerFile, $files);
			if ($kept === []) {
				continue;
			}

			$entries[$collectorType] = $kept;
		}

		return new self($this->spool, $entries, array_intersect_key($this->files, $files));
	}

	public function withRenamedFile(string $from, string $to): self
	{
		$entries = [];
		foreach ($this->entries as $collectorType => $entriesPerFile) {
			foreach ($entriesPerFile as $file => $entry) {
				$entries[$collectorType][$file === $from ? $to : $file] = $entry;
			}
		}

		$files = [];
		foreach (array_keys($this->files) as $file) {
			$files[$file === $from ? $to : $file] = true;
		}

		return new self($this->spool, $entries, $files);
	}

	/**
	 * @return Generator<string, list<mixed>>
	 */
	public function iterate(string $collectorType): Generator
	{
		$entries = $this->entries[$collectorType] ?? [];
		if ($entries === []) {
			return;
		}

		$handle = $this->spool->openForReading();
		try {
			foreach ($entries as $file => [$offset, $length]) {
				yield $file => $this->spool->read($handle, $file, $offset, $length);
			}
		} finally {
			// also when the caller stops iterating early
			fclose($handle);
		}
	}

	/**
	 * @return array<string, string> file => hash
	 */
	public function getHashes(string $collectorType): array
	{
		$hashes = [];
		foreach ($this->entries[$collectorType] ?? [] as $file => [, , $hash]) {
			$hashes[$file] = $hash;
		}

		return $hashes;
	}

	/**
	 * @return resource
	 */
	public function openForReading()
	{
		return $this->spool->openForReading();
	}

	/**
	 * The serialized value lists of one file with their hashes, without unserializing them.
	 *
	 * @param resource $handle from openForReading()
	 * @return array<class-string<Collector<Node, mixed>>, array{string, string}> collector type => [serialized values, hash]
	 */
	public function readBlobs(string $file, $handle): array
	{
		$blobs = [];
		foreach ($this->entries as $collectorType => $entriesPerFile) {
			if (!array_key_exists($file, $entriesPerFile)) {
				continue;
			}

			[$offset, $length, $hash] = $entriesPerFile[$file];
			$blobs[$collectorType] = [$this->spool->readBlob($handle, $file, $offset, $length), $hash];
		}

		return $blobs;
	}

	/**
	 * @return list<mixed>
	 */
	public function unserializeValues(string $blob, string $file): array
	{
		return $this->spool->unserializeValues($blob, $file);
	}

	/**
	 * @param resource|null $handle from openForReading(), to read many files without opening the spool for each
	 * @return array<class-string<Collector<Node, mixed>>, list<mixed>> collector type => values
	 */
	public function read(string $file, $handle = null): array
	{
		$data = [];
		$ownHandle = null;
		try {
			foreach ($this->entries as $collectorType => $entriesPerFile) {
				if (!array_key_exists($file, $entriesPerFile)) {
					continue;
				}

				if ($handle === null) {
					$handle = $ownHandle = $this->spool->openForReading();
				}
				[$offset, $length] = $entriesPerFile[$file];
				$data[$collectorType] = $this->spool->read($handle, $file, $offset, $length);
			}
		} finally {
			if ($ownHandle !== null) {
				fclose($ownHandle);
			}
		}

		return $data;
	}

}

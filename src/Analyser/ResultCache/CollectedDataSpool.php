<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PhpParser\Node;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Collector;
use PHPStan\File\CouldNotWriteFileException;
use RuntimeException;
use function array_is_list;
use function array_keys;
use function count;
use function error_get_last;
use function fclose;
use function fopen;
use function fread;
use function fseek;
use function ftruncate;
use function fwrite;
use function getmypid;
use function is_array;
use function md5;
use function serialize;
use function sprintf;
use function strlen;
use function uksort;
use function unlink;
use function unserialize;

/**
 * A file in the tmpDir that holds the collected data the workers send to the main process. The main
 * process writes each value list when it arrives and keeps only where it is, so the fresh data does
 * not stay in memory until the rules on CollectedDataNode run.
 *
 * The file is created on the first append() and deleted when the object is destroyed.
 *
 * @phpstan-import-type CollectorData from CollectedData
 */
final class CollectedDataSpool
{

	private bool $created = false;

	/** @var int<0, max> */
	private int $size = 0;

	private int|false $creatorPid;

	/** @var array<class-string<Collector<Node, mixed>>, array<string, list<array{int<0, max>, int<1, max>, string}>>> collector type => file => [offset, length, hash] of each value list */
	private array $segments = [];

	/** @var array<string, int> file => when its first value list came, to keep the order of the files the same as in memory */
	private array $fileOrder = [];

	/** @var array<string, int> collector type => order of the last file it got */
	private array $lastFileOrder = [];

	/** @var array<string, true> collector types that got a file out of order, because the file came in an earlier job too */
	private array $unordered = [];

	public function __construct(private string $path)
	{
		$this->creatorPid = getmypid();
	}

	/**
	 * @param CollectorData $collectedData
	 */
	public function append(array $collectedData): void
	{
		if ($collectedData === []) {
			return;
		}

		$handle = $this->open($this->created ? 'a' : 'w');
		$this->created = true;
		$size = $this->size;
		$segments = [];
		try {
			foreach ($collectedData as $file => $dataPerCollector) {
				foreach ($dataPerCollector as $collectorType => $values) {
					$blob = serialize($values);
					$segments[] = [$collectorType, $file, $this->write($handle, $blob), md5($blob)];
				}
			}
		} catch (CouldNotWriteFileException $e) {
			// what was written of this batch is not indexed, so the caller can keep the whole batch elsewhere
			@ftruncate($handle, $size);
			$this->size = $size;

			throw $e;
		} finally {
			fclose($handle);
		}

		foreach ($segments as [$collectorType, $file, [$offset, $length], $hash]) {
			$order = $this->fileOrder[$file] ??= count($this->fileOrder);
			if (!isset($this->segments[$collectorType][$file])) {
				if (($this->lastFileOrder[$collectorType] ?? -1) > $order) {
					$this->unordered[$collectorType] = true;
				} else {
					$this->lastFileOrder[$collectorType] = $order;
				}
			}
			$this->segments[$collectorType][$file][] = [$offset, $length, $hash];
		}
	}

	/**
	 * Everything appended so far, in memory. For when the spool cannot be written any more.
	 *
	 * @return CollectorData
	 */
	public function toArray(): array
	{
		$data = [];
		if ($this->segments === []) {
			return $data;
		}

		$handle = $this->openForReading();
		try {
			foreach ($this->segments as $collectorType => $segmentsPerFile) {
				foreach ($segmentsPerFile as $file => $segments) {
					foreach ($segments as [$offset, $length]) {
						foreach ($this->read($handle, $file, $offset, $length) as $value) {
							$data[$file][$collectorType][] = $value;
						}
					}
				}
			}
		} finally {
			fclose($handle);
		}

		return $data;
	}

	/**
	 * Merges the value lists of a file that came in more than one job. The data of a trait is stored
	 * under the trait's file, and each file that uses the trait can be in a different job.
	 */
	public function finish(): ?SpooledCollectedData
	{
		if ($this->segments === []) {
			return null;
		}

		$entries = [];
		$readHandle = null;
		$writeHandle = null;
		$fileOrder = $this->fileOrder;
		try {
			foreach ($this->segments as $collectorType => $segmentsPerFile) {
				if (isset($this->unordered[$collectorType])) {
					uksort($segmentsPerFile, static fn (string $a, string $b): int => $fileOrder[$a] <=> $fileOrder[$b]);
				}
				foreach ($segmentsPerFile as $file => $segments) {
					if (count($segments) === 1) {
						$entries[$collectorType][$file] = $segments[0];
						continue;
					}

					$readHandle ??= $this->openForReading();
					$merged = [];
					foreach ($segments as [$offset, $length]) {
						foreach ($this->read($readHandle, $file, $offset, $length) as $value) {
							$merged[] = $value;
						}
					}

					$writeHandle ??= $this->open('a');
					$blob = serialize($merged);
					[$offset, $length] = $this->write($writeHandle, $blob);
					$entries[$collectorType][$file] = [$offset, $length, md5($blob)];
				}
			}
		} finally {
			if ($readHandle !== null) {
				fclose($readHandle);
			}
			if ($writeHandle !== null) {
				fclose($writeHandle);
			}
		}

		$files = [];
		foreach (array_keys($fileOrder) as $file) {
			$files[$file] = true;
		}

		$this->segments = [];
		$this->fileOrder = [];
		$this->lastFileOrder = [];
		$this->unordered = [];

		return new SpooledCollectedData($this, $entries, $files);
	}

	/**
	 * @return resource
	 */
	public function openForReading()
	{
		return $this->open('r');
	}

	/**
	 * @param resource $handle from openForReading()
	 * @param int<0, max> $offset
	 * @param int<1, max> $length
	 * @return list<mixed>
	 */
	public function read($handle, string $file, int $offset, int $length): array
	{
		if (fseek($handle, $offset) !== 0) {
			throw new RuntimeException(sprintf('Cannot seek to the collected data of %s in %s.', $file, $this->path));
		}

		$blob = fread($handle, $length);
		if ($blob === false || strlen($blob) !== $length) {
			throw new RuntimeException(sprintf('Expected %d bytes of the collected data of %s in %s.', $length, $file, $this->path));
		}

		$values = @unserialize($blob);
		if (!is_array($values) || !array_is_list($values)) {
			throw new RuntimeException(sprintf('The collected data of %s in %s is not a list.', $file, $this->path));
		}

		return $values;
	}

	public function __destruct()
	{
		// a forked worker holds a copy of this object and must not delete the file of the main process
		if (!$this->created || getmypid() !== $this->creatorPid) {
			return;
		}

		@unlink($this->path);
	}

	/**
	 * @param resource $handle
	 * @return array{int<0, max>, int<1, max>}
	 */
	private function write($handle, string $blob): array
	{
		$length = strlen($blob);
		if ($length === 0 || @fwrite($handle, $blob) !== $length) {
			$error = error_get_last();
			throw new CouldNotWriteFileException($this->path, $error !== null ? $error['message'] : 'unknown cause');
		}

		$offset = $this->size;
		$this->size += $length;

		return [$offset, $length];
	}

	/**
	 * Opened for each call: a handle kept open would be inherited by the workers forked later.
	 *
	 * @return resource
	 */
	private function open(string $mode)
	{
		$handle = @fopen($this->path, $mode);
		if ($handle === false) {
			$error = error_get_last();
			if ($mode === 'r') {
				throw new RuntimeException(sprintf('Cannot open %s: %s', $this->path, $error !== null ? $error['message'] : 'unknown cause'));
			}

			throw new CouldNotWriteFileException($this->path, $error !== null ? $error['message'] : 'unknown cause');
		}

		return $handle;
	}

}

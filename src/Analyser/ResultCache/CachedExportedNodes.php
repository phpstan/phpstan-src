<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Dependency\RootExportedNode;
use function array_key_exists;
use function array_keys;
use function fclose;
use function fread;
use function fseek;
use function is_array;
use function is_resource;
use function sprintf;
use function strlen;
use function unserialize;

/**
 * The exported nodes a result cache file holds, left in the file until they are asked for.
 *
 * They are the bulk of the cache - on Drupal core 135 MB of the 160 MB - and a run needs the decoded
 * nodes of the few files that changed and nothing else: the rest only has to get into the next
 * cache file as it was. Decoding all of them on restore and serializing them again on save was about
 * half a second of every run that re-analysed anything. So restore() hands the untouched ones over
 * as this, and save() copies their bytes over from the old file.
 *
 * The file handle is shared by the instances derived from one another, and is closed once save() is
 * done copying: on Windows, the old file cannot be replaced while it is open.
 */
final class CachedExportedNodes
{

	/**
	 * @param resource|null $handle
	 * @param array<string, array{int, positive-int}> $locations file => [offset, length] of its serialized nodes
	 */
	private function __construct(
		private $handle,
		private array $locations,
	)
	{
	}

	public static function createEmpty(): self
	{
		return new self(null, []);
	}

	/**
	 * @param resource $handle
	 * @param array<string, array{int, positive-int}> $locations
	 */
	public static function createFromFile($handle, array $locations): self
	{
		return new self($handle, $locations);
	}

	public function has(string $file): bool
	{
		return array_key_exists($file, $this->locations);
	}

	/**
	 * The length of the serialized nodes of a file this holds.
	 *
	 * @return positive-int
	 */
	public function getLength(string $file): int
	{
		if (!array_key_exists($file, $this->locations)) {
			throw new CachedExportedNodesUnreadableException(sprintf('The exported nodes of %s are not in the cache file.', $file));
		}

		return $this->locations[$file][1];
	}

	/**
	 * @return list<string>
	 */
	public function getFiles(): array
	{
		return array_keys($this->locations);
	}

	/**
	 * @param array<string, mixed> $files
	 */
	public function only(array $files): self
	{
		$locations = [];
		foreach ($this->locations as $file => $location) {
			if (!array_key_exists($file, $files)) {
				continue;
			}

			$locations[$file] = $location;
		}

		return new self($this->handle, $locations);
	}

	/**
	 * @param array<string, mixed> $files
	 */
	public function without(array $files): self
	{
		$locations = $this->locations;
		foreach (array_keys($files) as $file) {
			unset($locations[$file]);
		}

		return new self($this->handle, $locations);
	}

	/**
	 * @return array<RootExportedNode>
	 */
	public function decode(string $file): array
	{
		$nodes = @unserialize($this->read($file));
		if (!is_array($nodes)) {
			throw new CachedExportedNodesUnreadableException(sprintf('The exported nodes of %s could not be unserialized.', $file));
		}

		/** @var array<RootExportedNode> $nodes */
		return $nodes;
	}

	/**
	 * The serialized nodes, as they are in the file.
	 */
	public function read(string $file): string
	{
		if (!array_key_exists($file, $this->locations) || !is_resource($this->handle)) {
			throw new CachedExportedNodesUnreadableException(sprintf('The exported nodes of %s are not in the cache file.', $file));
		}

		[$offset, $length] = $this->locations[$file];
		if (fseek($this->handle, $offset) !== 0) {
			throw new CachedExportedNodesUnreadableException(sprintf('Cannot seek to the exported nodes of %s.', $file));
		}

		$contents = fread($this->handle, $length);
		if ($contents === false || strlen($contents) !== $length) {
			throw new CachedExportedNodesUnreadableException(sprintf('Cannot read the exported nodes of %s.', $file));
		}

		return $contents;
	}

	public function getOffset(string $file): int
	{
		if (!array_key_exists($file, $this->locations)) {
			throw new CachedExportedNodesUnreadableException(sprintf('The exported nodes of %s are not in the cache file.', $file));
		}

		return $this->locations[$file][0];
	}

	/**
	 * Bytes of the cache file - the serialized nodes of several files stored one after another.
	 *
	 * @param positive-int $length
	 */
	public function readRange(int $offset, int $length): string
	{
		if (!is_resource($this->handle) || fseek($this->handle, $offset) !== 0) {
			throw new CachedExportedNodesUnreadableException(sprintf('Cannot seek to offset %d of the cache file.', $offset));
		}

		$contents = fread($this->handle, $length);
		if ($contents === false || strlen($contents) !== $length) {
			throw new CachedExportedNodesUnreadableException(sprintf('Cannot read %d bytes at offset %d of the cache file.', $length, $offset));
		}

		return $contents;
	}

	public function close(): void
	{
		if (is_resource($this->handle)) {
			fclose($this->handle);
		}

		$this->handle = null;
	}

}

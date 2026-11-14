<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Cache\FileCacheStorage;
use PHPStan\Internal\DirectoryCreatorException;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;

final class FileCacheStorageBench
{

	private ?FileCacheStorage $storage = null;

	/** @var list<array{string, int, list<string>}> */
	private array $data = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function entries(): iterable
	{
		foreach ([100, 10000] as $entries) {
			yield 'entries-' . $entries => [$entries];
		}
	}

	/**
	 * @throws DirectoryCreatorException
	 */
	#[Before]
	public function boot(int $entries): void
	{
		BenchContainer::create($this->scratch);
		$this->storage = new FileCacheStorage($this->scratch->path() . '/file-cache-bench');

		// the counted calls do not autoload
		$this->storage->save('warmup', 'v1', self::data(2));
		$this->storage->load('warmup', 'v1');

		$this->data = self::data($entries);
		$this->storage->save('load-' . $entries, 'v1', $this->data);
	}

	/**
	 * @throws DirectoryCreatorException
	 */
	#[DataProvider(method: 'entries')]
	public function benchSave(int $entries): void
	{
		if ($this->storage === null) {
			throw new LogicException('boot() did not run.');
		}

		$this->storage->save('save-' . $entries, 'v1', $this->data);
	}

	#[DataProvider(method: 'entries')]
	public function benchLoad(int $entries): mixed
	{
		if ($this->storage === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->storage->load('load-' . $entries, 'v1');
	}

	/**
	 * @return list<array{string, int, list<string>}> shaped like the cached PHPDoc and reflection data
	 */
	private static function data(int $entries): array
	{
		$data = [];
		for ($i = 0; $i < $entries; $i++) {
			$data[] = ['App\\Model\\Entity' . $i, $i, ['int', 'string|null', 'list<App\\Model\\Entity' . $i . '>']];
		}

		return $data;
	}

}

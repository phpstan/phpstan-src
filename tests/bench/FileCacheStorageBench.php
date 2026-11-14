<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Cache\FileCacheStorage;
use PHPStan\Internal\DirectoryCreatorException;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
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
	#[ExpectedFor(dataSet: 'entries-100', instructions: '234.2K', cycles: '550.6K', memory: '17.29KiB')]
	#[ExpectedFor(dataSet: 'entries-10000', instructions: '18.92M', cycles: '29.77M', memory: '1.345MiB')]
	public function benchSave(int $entries): void
	{
		if ($this->storage === null) {
			throw new LogicException('boot() did not run.');
		}

		$this->storage->save('save-' . $entries, 'v1', $this->data);
	}

	#[DataProvider(method: 'entries')]
	#[ExpectedFor(dataSet: 'entries-100', instructions: '388.9K', cycles: '763.4K', memory: '130.1KiB')]
	#[ExpectedFor(dataSet: 'entries-10000', instructions: '37.00M', cycles: '70.18M', memory: '11.67MiB')]
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

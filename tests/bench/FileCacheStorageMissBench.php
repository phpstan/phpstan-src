<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Cache\FileCacheStorage;
use PHPStan\Internal\DirectoryCreatorException;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;
use Tally\ScratchDirectory;

final class FileCacheStorageMissBench
{

	private ?FileCacheStorage $storage = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @throws DirectoryCreatorException
	 */
	#[Before]
	public function boot(): void
	{
		$this->storage = new FileCacheStorage($this->scratch->path() . '/cache');
		$this->storage->save('written', 'v1', 'value');
		$this->storage->load('written', 'v1');
		$this->storage->load('warmup', 'v1');
	}

	/**
	 * The reflection cache in front of the PHP-internal locator is asked for every class name the
	 * locators before it do not find, and most were never written.
	 */
	#[Expected(instructions: '7.912K', cycles: '48.44K', memory: '824B')]
	public function benchLoadMissing(): mixed
	{
		if ($this->storage === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->storage->load('missing', 'v1');
	}

}

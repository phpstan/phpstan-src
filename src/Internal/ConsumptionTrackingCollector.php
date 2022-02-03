<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPStan\ShouldNotHappenException;
use RuntimeException;
use function array_map;
use function array_slice;
use function arsort;
use const SORT_NUMERIC;

class ConsumptionTrackingCollector
{

	private int $consumersAdded = 0;

	private int $totalMemoryConsumed = 0;

	/** @var array<string, int>  */
	private array $topMemoryConsumer = [];

	/** @var array<string, float>  */
	private array $topTimeConsumer = [];

	private string $file = '';

	public function __construct(private int $topX = 15, private int $purgeEveryX = 2000)
	{
	}

	public function addConsumption(FileConsumptionTracker $consumption): void
	{
		$this->consumersAdded++;
		if ($this->consumersAdded > $this->purgeEveryX) {
			// save some memory on very large code bases
			$this->purgeOverflow();
		}

		$this->totalMemoryConsumed = $consumption->getTotalMemoryConsumed();

		$this->topMemoryConsumer[$consumption->getFile()] = $consumption->getMemoryConsumed();
		$this->topTimeConsumer[$consumption->getFile()] = $consumption->getTimeConsumed();

		$this->file = $consumption->getFile();
	}

	public function getTotalMemoryConsumed(): int
	{
		return $this->totalMemoryConsumed;
	}

	public function getMemoryConsumedForLatestFile(): int
	{
		if ($this->consumersAdded === 0) {
			throw new RuntimeException('no files were tracked');
		} elseif (!isset($this->topMemoryConsumer[$this->file])) {
			throw new ShouldNotHappenException('no memory consumption found for ' . $this->file . ' (consumers added: ' . $this->consumersAdded . ')');
		}

		return $this->topMemoryConsumer[$this->file];
	}

	public function getTimeConsumedForLatestFile(): float
	{
		if ($this->consumersAdded === 0) {
			throw new RuntimeException('no files were tracked');
		} elseif (!isset($this->topTimeConsumer[$this->file])) {
			throw new ShouldNotHappenException('no time consumption found for ' . $this->file . ' (consumers added: ' . $this->consumersAdded . ')');
		}

		return $this->topTimeConsumer[$this->file];
	}

	/**
	 * @return array<string, int>
	 */
	public function getTopMemoryConsumers(): array
	{
		$this->purgeOverflow();
		return $this->topMemoryConsumer;
	}

	/**
	 * @return array<string, string>
	 */
	public function getHumanisedTopMemoryConsumers(): array
	{
		return array_map(
			static fn (int $usedMemory): string => BytesHelper::bytes($usedMemory),
			$this->getTopMemoryConsumers(),
		);
	}

	/**
	 * @return array<string, float>
	 */
	public function getTopTimeConsumers(): array
	{
		$this->purgeOverflow();
		return $this->topTimeConsumer;
	}

	/**
	 * @return array<string, string>
	 */
	public function getHumanisedTopTimeConsumers(): array
	{
		return array_map(
			static fn (float $time): string => TimeHelper::humaniseFractionalSeconds($time),
			$this->getTopTimeConsumers(),
		);
	}

	/**
	 * Keep memory footprint low - purge data not needed
	 */
	private function purgeOverflow(): void
	{
		arsort($this->topMemoryConsumer, SORT_NUMERIC);
		$this->topMemoryConsumer = array_slice($this->topMemoryConsumer, 0, $this->topX, true);

		arsort($this->topTimeConsumer, SORT_NUMERIC);
		$this->topTimeConsumer = array_slice($this->topTimeConsumer, 0, $this->topX, true);

		$this->consumersAdded = $this->topX;
	}

}

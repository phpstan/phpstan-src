<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPStan\ShouldNotHappenException;
use RuntimeException;
use function array_map;
use function array_slice;
use function arsort;
use function memory_get_peak_usage;
use function microtime;
use const SORT_NUMERIC;

class ConsumptionCollector
{

	private int $consumersAdded = 0;

	private string $file = '';

	private int $totalMemoryConsumed = 0;

	private float $processingStartedAt = 0;

	private int $memoryConsumedAtStart = 0;

	/** @var array<string, int>  */
	private array $topMemoryConsumer = [];

	/** @var array<string, float>  */
	private array $topTimeConsumer = [];


	public function __construct(private int $topX = 15)
	{
	}

	public function registerFile(string $fileProcessed): void
	{
		if ($this->consumersAdded > 2000) {
			$this->purgeOverflow();
		}

		$this->processingStartedAt = microtime(true);
		$this->memoryConsumedAtStart = memory_get_peak_usage(true);

		$this->file = $fileProcessed;
		$this->consumersAdded++;
	}

	public function trackConsumption(): void
	{
		$this->totalMemoryConsumed = memory_get_peak_usage(true);

		$this->topMemoryConsumer[$this->file] = $this->totalMemoryConsumed - $this->memoryConsumedAtStart;
		$this->topTimeConsumer[$this->file] = microtime(true) - $this->processingStartedAt;
	}

	public function getTotalMemoryConsumed(): int
	{
		return $this->totalMemoryConsumed;
	}

	public function getMemoryConsumed(): int
	{
		if ($this->consumersAdded === 0) {
			throw new RuntimeException('no files were registered');
		} elseif (!isset($this->topMemoryConsumer[$this->file])) {
			throw new ShouldNotHappenException('no memory consumption found for ' . $this->file . ' (consumers added: ' . $this->consumersAdded . ')');
		}

		return $this->topMemoryConsumer[$this->file];
	}

	public function getTimeConsumed(): float
	{
		if ($this->consumersAdded === 0) {
			throw new RuntimeException('no files were registered');
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

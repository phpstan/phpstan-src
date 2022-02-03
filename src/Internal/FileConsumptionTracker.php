<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use InvalidArgumentException;
use RuntimeException;
use function is_float;
use function is_int;
use function is_string;
use function memory_get_peak_usage;
use function microtime;

class FileConsumptionTracker
{

	private int $memoryConsumedAtStart = 0;

	private float $processingStartedAt = 0;

	private float $timeConsumed = 0;

	private int $memoryConsumed = 0;

	private int $totalMemoryConsumed = 0;

	private bool $trackingIsRunning = false;

	private bool $trackingWasStarted = false;

	public function __construct(private string $file)
	{
	}

	public function start(): void
	{
		$this->processingStartedAt = microtime(true);
		$this->memoryConsumedAtStart = memory_get_peak_usage(true);
		$this->trackingIsRunning = true;
		$this->trackingWasStarted = true;
	}

	public function stop(): void
	{
		if (!$this->trackingWasStarted) {
			throw new RuntimeException('can not return data when data collection was not started');
		} elseif (!$this->trackingIsRunning) {
			throw new RuntimeException('tracking is not running');
		}

		$this->totalMemoryConsumed = memory_get_peak_usage(true);
		$this->memoryConsumed = $this->totalMemoryConsumed - $this->memoryConsumedAtStart;
		$this->timeConsumed = microtime(true) - $this->processingStartedAt;

		$this->trackingIsRunning = false;
	}

	public function getFile(): string
	{
		return $this->file;
	}

	public function getTimeConsumed(): float
	{
		if (!$this->trackingWasStarted) {
			throw new RuntimeException('can not return data when data collection was not started');
		} elseif ($this->trackingIsRunning) {
			throw new RuntimeException('can not return data when data collection is running');
		}

		return $this->timeConsumed;
	}

	public function getMemoryConsumed(): int
	{
		if (!$this->trackingWasStarted) {
			throw new RuntimeException('can not return data when data collection was not started');
		} elseif ($this->trackingIsRunning) {
			throw new RuntimeException('can not return data when data collection is running');
		}

		return $this->memoryConsumed;
	}

	public function getTotalMemoryConsumed(): int
	{
		if (!$this->trackingWasStarted) {
			throw new RuntimeException('can not return data when data collection was not started');
		} elseif ($this->trackingIsRunning) {
			throw new RuntimeException('can not return data when data collection is running');
		}

		return $this->totalMemoryConsumed;
	}

	/**
	 * Set data from params without actually running
	 */
	private function setConsumptionData(float $timeConsumed, int $memoryConsumed, int $totalMemoryConsumed): void
	{
		$this->timeConsumed = $timeConsumed;
		$this->memoryConsumed = $memoryConsumed;
		$this->totalMemoryConsumed = $totalMemoryConsumed;

		$this->trackingWasStarted = true;
	}

	/**
	 * @return array{"file": string, "timeConsumed": float, "memoryConsumed": int, "totalMemoryConsumed": int}
	 */
	public function toArray(): array
	{
		return [
			'file' => $this->file,
			'timeConsumed' => $this->getTimeConsumed(),
			'memoryConsumed' => $this->getMemoryConsumed(),
			'totalMemoryConsumed' => $this->getTotalMemoryConsumed(),
		];
	}

	/**
	 * @param array<mixed> $consumptionData
	 */
	public static function createFromArray(array $consumptionData): self
	{
		if (
			!isset($consumptionData['file'])
			|| !is_string($consumptionData['file'])
			|| !isset($consumptionData['totalMemoryConsumed'])
			|| !is_int($consumptionData['totalMemoryConsumed'])
			|| !isset($consumptionData['memoryConsumed'])
			|| !is_int($consumptionData['memoryConsumed'])
			|| !isset($consumptionData['timeConsumed'])
			|| !is_float($consumptionData['timeConsumed'])
		) {
			throw new InvalidArgumentException('invalid consumption data');
		}

		$obj = new self($consumptionData['file']);
		$obj->setConsumptionData(
			$consumptionData['timeConsumed'],
			$consumptionData['memoryConsumed'],
			$consumptionData['totalMemoryConsumed'],
		);

		return $obj;
	}

}

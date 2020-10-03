<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\DependencyInjection\Container;
use function memory_get_peak_usage;

class InceptionResult
{

	/** @var callable(): (array{string[], bool}) */
	private $filesCallback;

	private Output $stdOutput;

	private Output $errorOutput;

	private \PHPStan\DependencyInjection\Container $container;

	private bool $isDefaultLevelUsed;

	private string $memoryLimitFile;

	private ?string $projectConfigFile;

	private ?string $generateBaselineFile;

	/**
	 * @param callable(): (array{string[], bool}) $filesCallback
	 * @param Output $stdOutput
	 * @param Output $errorOutput
	 * @param \PHPStan\DependencyInjection\Container $container
	 * @param bool $isDefaultLevelUsed
	 * @param string $memoryLimitFile
	 * @param string|null $projectConfigFile
	 * @param string|null $generateBaselineFile
	 */
	public function __construct(
		callable $filesCallback,
		Output $stdOutput,
		Output $errorOutput,
		Container $container,
		bool $isDefaultLevelUsed,
		string $memoryLimitFile,
		?string $projectConfigFile,
		?string $generateBaselineFile
	)
	{
		$this->filesCallback = $filesCallback;
		$this->stdOutput = $stdOutput;
		$this->errorOutput = $errorOutput;
		$this->container = $container;
		$this->isDefaultLevelUsed = $isDefaultLevelUsed;
		$this->memoryLimitFile = $memoryLimitFile;
		$this->projectConfigFile = $projectConfigFile;
		$this->generateBaselineFile = $generateBaselineFile;
	}

	/**
	 * @return array{string[], bool}
	 */
	public function getFiles(): array
	{
		$callback = $this->filesCallback;

		return $callback();
	}

	public function getStdOutput(): Output
	{
		return $this->stdOutput;
	}

	public function getErrorOutput(): Output
	{
		return $this->errorOutput;
	}

	public function getContainer(): Container
	{
		return $this->container;
	}

	public function isDefaultLevelUsed(): bool
	{
		return $this->isDefaultLevelUsed;
	}

	public function getProjectConfigFile(): ?string
	{
		return $this->projectConfigFile;
	}

	public function getGenerateBaselineFile(): ?string
	{
		return $this->generateBaselineFile;
	}

	public function handleReturn(int $exitCode): int
	{
		if ($this->getErrorOutput()->isVerbose()) {
			$this->getErrorOutput()->writeLineFormatted(sprintf('Used memory: %s', $this->bytes(memory_get_peak_usage(true))));
		}

		@unlink($this->memoryLimitFile);
		return $exitCode;
	}

	private function bytes(int $bytes): string
	{
		$bytes = round($bytes);
		$units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
		foreach ($units as $unit) {
			if (abs($bytes) < 1024 || $unit === end($units)) {
				break;
			}
			$bytes /= 1024;
		}

		if (!isset($unit)) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return round($bytes, 2) . ' ' . $unit;
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\DependencyInjection\Container;
use PHPStan\Internal\BytesHelper;
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

	/** @var mixed[]|null */
	private ?array $projectConfigArray;

	private ?string $generateBaselineFile;

	/**
	 * @param callable(): (array{string[], bool}) $filesCallback
	 * @param mixed[] $projectConfigArray
	 */
	public function __construct(
		callable $filesCallback,
		Output $stdOutput,
		Output $errorOutput,
		Container $container,
		bool $isDefaultLevelUsed,
		string $memoryLimitFile,
		?string $projectConfigFile,
		?array $projectConfigArray,
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
		$this->projectConfigArray = $projectConfigArray;
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

	/**
	 * @return mixed[]|null
	 */
	public function getProjectConfigArray(): ?array
	{
		return $this->projectConfigArray;
	}

	public function getGenerateBaselineFile(): ?string
	{
		return $this->generateBaselineFile;
	}

	public function handleReturn(int $exitCode): int
	{
		if ($this->getErrorOutput()->isVerbose()) {
			$this->getErrorOutput()->writeLineFormatted(sprintf('Used memory: %s', BytesHelper::bytes(memory_get_peak_usage(true))));
		}

		@unlink($this->memoryLimitFile);
		return $exitCode;
	}

}

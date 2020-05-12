<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\DependencyInjection\Container;

class InceptionResult
{

	/** @var string[] */
	private array $files;

	private bool $onlyFiles;

	private Output $stdOutput;

	private Output $errorOutput;

	private \PHPStan\DependencyInjection\Container $container;

	private bool $isDefaultLevelUsed;

	private string $memoryLimitFile;

	private ?string $projectConfigFile;

	private ?string $generateBaselineFile;

	/**
	 * @param string[] $files
	 * @param bool $onlyFiles
	 * @param Output $stdOutput
	 * @param Output $errorOutput
	 * @param \PHPStan\DependencyInjection\Container $container
	 * @param bool $isDefaultLevelUsed
	 * @param string $memoryLimitFile
	 * @param string|null $projectConfigFile
	 * @param string|null $generateBaselineFile
	 */
	public function __construct(
		array $files,
		bool $onlyFiles,
		Output $stdOutput,
		Output $errorOutput,
		Container $container,
		bool $isDefaultLevelUsed,
		string $memoryLimitFile,
		?string $projectConfigFile,
		?string $generateBaselineFile
	)
	{
		$this->files = $files;
		$this->onlyFiles = $onlyFiles;
		$this->stdOutput = $stdOutput;
		$this->errorOutput = $errorOutput;
		$this->container = $container;
		$this->isDefaultLevelUsed = $isDefaultLevelUsed;
		$this->memoryLimitFile = $memoryLimitFile;
		$this->projectConfigFile = $projectConfigFile;
		$this->generateBaselineFile = $generateBaselineFile;
	}

	/**
	 * @return string[]
	 */
	public function getFiles(): array
	{
		return $this->files;
	}

	public function isOnlyFiles(): bool
	{
		return $this->onlyFiles;
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
		@unlink($this->memoryLimitFile);
		return $exitCode;
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\DependencyInjection\Container;
use PHPStan\File\PathNotFoundException;
use PHPStan\Internal\BytesHelper;
use function max;
use function memory_get_peak_usage;
use function sprintf;

class InceptionResult
{

	/** @var callable(): (array{string[], bool}) */
	private $filesCallback;

	/**
	 * @param callable(): (array{string[], bool}) $filesCallback
	 * @param mixed[]|null $projectConfigArray
	 */
	public function __construct(
		callable $filesCallback,
		private Output $stdOutput,
		private Output $errorOutput,
		private Container $container,
		private bool $isDefaultLevelUsed,
		private ?string $projectConfigFile,
		private ?array $projectConfigArray,
		private ?string $generateBaselineFile,
	)
	{
		$this->filesCallback = $filesCallback;
	}

	/**
	 * @throws InceptionNotSuccessfulException
	 * @throws PathNotFoundException
	 * @return array{string[], bool}
	 */
	public function getFiles(): array
	{
		$callback = $this->filesCallback;

		/** @throws InceptionNotSuccessfulException|PathNotFoundException */
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

	public function handleReturn(int $exitCode, ?int $peakMemoryUsageBytes): int
	{
		if ($peakMemoryUsageBytes !== null && $this->getErrorOutput()->isVerbose()) {
			$this->getErrorOutput()->writeLineFormatted(sprintf(
				'Used memory: %s',
				BytesHelper::bytes(max(memory_get_peak_usage(true), $peakMemoryUsageBytes)),
			));
		}

		return $exitCode;
	}

}

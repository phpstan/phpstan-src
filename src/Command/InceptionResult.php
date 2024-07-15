<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\DependencyInjection\Container;
use PHPStan\File\PathNotFoundException;
use PHPStan\Internal\BytesHelper;
use function floor;
use function implode;
use function max;
use function memory_get_peak_usage;
use function microtime;
use function round;
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

	public function handleReturn(int $exitCode, ?int $peakMemoryUsageBytes, float $analysisStartTime): int
	{
		if ($peakMemoryUsageBytes !== null && $this->getErrorOutput()->isVerbose()) {
			$this->getErrorOutput()->writeLineFormatted(sprintf(
				'Used memory: %s',
				BytesHelper::bytes(max(memory_get_peak_usage(true), $peakMemoryUsageBytes)),
			));
		}

		if ($this->getErrorOutput()->isDebug()) {
			$this->getErrorOutput()->writeLineFormatted(sprintf(
				'Analysis time: %s',
				$this->formatDuration((int) round(microtime(true) - $analysisStartTime)),
			));
		}

		return $exitCode;
	}

	private function formatDuration(int $seconds): string
	{
		$minutes = (int) floor($seconds / 60);
		$remainingSeconds = $seconds % 60;

		$result = [];
		if ($minutes > 0) {
			$result[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
		}

		if ($remainingSeconds > 0) {
			$result[] = $remainingSeconds . ' second' . ($remainingSeconds > 1 ? 's' : '');
		}

		return implode(' ', $result);
	}

}

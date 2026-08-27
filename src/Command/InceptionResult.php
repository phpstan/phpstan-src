<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\DependencyInjection\Container;
use PHPStan\File\PathNotFoundException;
use PHPStan\Internal\BytesHelper;
use PHPStan\Parallel\ForkParallelChecker;
use function floor;
use function implode;
use function memory_get_peak_usage;
use function microtime;
use function round;
use function sprintf;

final class InceptionResult
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
		private ?string $editorModeTmpFile,
		private ?string $editorModeInsteadOfFile,
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

	public function getEditorModeTmpFile(): ?string
	{
		return $this->editorModeTmpFile;
	}

	public function getEditorModeInsteadOfFile(): ?string
	{
		return $this->editorModeInsteadOfFile;
	}

	/**
	 * @param int|null $peakMemoryUsageBytes the heaviest parallel worker's peak, 0 when the
	 * analysis ran in this process
	 */
	public function handleReturn(int $exitCode, ?int $peakMemoryUsageBytes, float $analysisStartTime, int $workerCount = 0): int
	{
		if ($this->getErrorOutput()->isVerbose()) {
			$elapsedTime = round(microtime(true) - $analysisStartTime, 2);
			if ($elapsedTime < 60) {
				$elapsedTimeString = sprintf('%.2f seconds', $elapsedTime);
			} else {
				$elapsedTimeString = $this->formatDuration((int) $elapsedTime);
			}
			$this->getErrorOutput()->writeLineFormatted(sprintf(
				'Elapsed time: %s',
				$elapsedTimeString,
			));
		}

		if ($peakMemoryUsageBytes !== null && $this->getErrorOutput()->isVerbose()) {
			// This process's peak is read here, at the very end, so it covers collecting
			// the workers' results and saving the result cache - the part of a parallel
			// run where the main process is at its largest. Both numbers are per process,
			// which is also how memory_limit applies.
			$mainProcessPeak = memory_get_peak_usage(true);
			if ($peakMemoryUsageBytes === 0 || $workerCount === 0) {
				$this->getErrorOutput()->writeLineFormatted(sprintf(
					'Peak memory: %s',
					BytesHelper::bytes($mainProcessPeak),
				));
			} else {
				$mechanism = $this->container->getByType(ForkParallelChecker::class)->isSupported() ? 'forked' : 'spawned';
				$this->getErrorOutput()->writeLineFormatted(sprintf(
					'Peak memory: %s (main process), %s (%s)',
					BytesHelper::bytes($mainProcessPeak),
					BytesHelper::bytes($peakMemoryUsageBytes),
					$workerCount === 1
						? sprintf('the %s worker', $mechanism)
						: sprintf('largest of %d %s workers', $workerCount, $mechanism),
				));
			}
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

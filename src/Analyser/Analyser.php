<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Closure;
use PHPStan\Internal\ConsumptionTrackingCollector;
use PHPStan\Internal\FileConsumptionTracker;
use PHPStan\Rules\Registry;
use Throwable;
use function array_fill_keys;
use function array_merge;
use function count;
use function sprintf;

class Analyser
{

	public function __construct(
		private FileAnalyser $fileAnalyser,
		private Registry $registry,
		private NodeScopeResolver $nodeScopeResolver,
		private int $internalErrorsCountLimit,
	)
	{
	}

	/**
	 * @param string[] $files
	 * @param Closure(string $file): void|null $preFileCallback
	 * @param Closure(int ): void|null $postFileCallback
	 * @param string[]|null $allAnalysedFiles
	 */
	public function analyse(
		array $files,
		?Closure $preFileCallback = null,
		?Closure $postFileCallback = null,
		bool $debug = false,
		?array $allAnalysedFiles = null,
		?ConsumptionTrackingCollector $consumptionTrackingCollector = null,
	): AnalyserResult
	{
		if ($allAnalysedFiles === null) {
			$allAnalysedFiles = $files;
		}

		$this->nodeScopeResolver->setAnalysedFiles($allAnalysedFiles);
		$allAnalysedFiles = array_fill_keys($allAnalysedFiles, true);

		$errors = [];
		$internalErrorsCount = 0;
		$reachedInternalErrorsCountLimit = false;
		$dependencies = [];
		$exportedNodes = [];
		foreach ($files as $file) {
			$consumptionTracker = null;
			if ($preFileCallback !== null) {
				$preFileCallback($file);
			}

			try {
				if ($consumptionTrackingCollector !== null) {
					$consumptionTracker = new FileConsumptionTracker($file);
					$consumptionTracker->start();
				}
				$fileAnalyserResult = $this->fileAnalyser->analyseFile(
					$file,
					$allAnalysedFiles,
					$this->registry,
					null,
				);
				$errors = array_merge($errors, $fileAnalyserResult->getErrors());
				$dependencies[$file] = $fileAnalyserResult->getDependencies();

				$fileExportedNodes = $fileAnalyserResult->getExportedNodes();
				if (count($fileExportedNodes) > 0) {
					$exportedNodes[$file] = $fileExportedNodes;
				}

				if ($consumptionTracker instanceof FileConsumptionTracker) {
					$consumptionTracker->stop();
					$consumptionTrackingCollector->addConsumption($consumptionTracker);
				}

			} catch (Throwable $t) {
				if ($debug) {
					throw $t;
				}
				$internalErrorsCount++;
				$internalErrorMessage = sprintf('Internal error: %s', $t->getMessage());
				$internalErrorMessage .= sprintf(
					'%sRun PHPStan with --debug option and post the stack trace to:%s%s',
					"\n",
					"\n",
					'https://github.com/phpstan/phpstan/issues/new?template=Bug_report.md',
				);
				$errors[] = new Error($internalErrorMessage, $file, null, $t);
				if ($internalErrorsCount >= $this->internalErrorsCountLimit) {
					$reachedInternalErrorsCountLimit = true;
					break;
				}
			}

			if ($postFileCallback === null) {
				continue;
			}

			$postFileCallback(1);
		}

		return new AnalyserResult(
			$errors,
			[],
			$internalErrorsCount === 0 ? $dependencies : null,
			$exportedNodes,
			$reachedInternalErrorsCountLimit,
		);
	}

}

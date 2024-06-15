<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Closure;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\Rules\Registry as RuleRegistry;
use Throwable;
use function array_fill_keys;
use function array_merge;
use function count;
use function memory_get_peak_usage;

class Analyser
{

	public function __construct(
		private FileAnalyser $fileAnalyser,
		private RuleRegistry $ruleRegistry,
		private CollectorRegistry $collectorRegistry,
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
	): AnalyserResult
	{
		if ($allAnalysedFiles === null) {
			$allAnalysedFiles = $files;
		}

		$this->nodeScopeResolver->setAnalysedFiles($allAnalysedFiles);
		$allAnalysedFiles = array_fill_keys($allAnalysedFiles, true);

		/** @var list<Error> $errors */
		$errors = [];
		/** @var list<Error> $filteredPhpErrors */
		$filteredPhpErrors = [];
		/** @var list<Error> $allPhpErrors */
		$allPhpErrors = [];

		/** @var list<Error> $locallyIgnoredErrors */
		$locallyIgnoredErrors = [];

		$linesToIgnore = [];
		$unmatchedLineIgnores = [];

		/** @var list<CollectedData> $collectedData */
		$collectedData = [];

		$internalErrorsCount = 0;
		$reachedInternalErrorsCountLimit = false;
		$dependencies = [];
		$exportedNodes = [];
		foreach ($files as $file) {
			if ($preFileCallback !== null) {
				$preFileCallback($file);
			}

			try {
				$fileAnalyserResult = $this->fileAnalyser->analyseFile(
					$file,
					$allAnalysedFiles,
					$this->ruleRegistry,
					$this->collectorRegistry,
					null,
				);
				$errors = array_merge($errors, $fileAnalyserResult->getErrors());
				$filteredPhpErrors = array_merge($filteredPhpErrors, $fileAnalyserResult->getFilteredPhpErrors());
				$allPhpErrors = array_merge($allPhpErrors, $fileAnalyserResult->getAllPhpErrors());

				$locallyIgnoredErrors = array_merge($locallyIgnoredErrors, $fileAnalyserResult->getLocallyIgnoredErrors());
				$linesToIgnore[$file] = $fileAnalyserResult->getLinesToIgnore();
				$unmatchedLineIgnores[$file] = $fileAnalyserResult->getUnmatchedLineIgnores();
				$collectedData = array_merge($collectedData, $fileAnalyserResult->getCollectedData());
				$dependencies[$file] = $fileAnalyserResult->getDependencies();

				$fileExportedNodes = $fileAnalyserResult->getExportedNodes();
				if (count($fileExportedNodes) > 0) {
					$exportedNodes[$file] = $fileExportedNodes;
				}
			} catch (Throwable $t) {
				if ($debug) {
					throw $t;
				}
				$internalErrorsCount++;
				$errors[] = (new Error($t->getMessage(), $file, null, $t))
					->withIdentifier('phpstan.internal')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($t),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $t->getTraceAsString(),
					]);
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
			$filteredPhpErrors,
			$allPhpErrors,
			$locallyIgnoredErrors,
			$linesToIgnore,
			$unmatchedLineIgnores,
			[],
			$collectedData,
			$internalErrorsCount === 0 ? $dependencies : null,
			$exportedNodes,
			$reachedInternalErrorsCountLimit,
			memory_get_peak_usage(true),
		);
	}

}

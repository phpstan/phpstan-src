<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Closure;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Registry as RuleRegistry;
use Throwable;
use function array_fill_keys;
use function array_key_exists;
use function array_merge;
use function count;
use function json_decode;
use function json_encode;
use function memory_get_peak_usage;
use function sprintf;

class Analyser
{

	public function __construct(
		private FileAnalyser $fileAnalyser,
		private RuleRegistry $ruleRegistry,
		private CollectorRegistry $collectorRegistry,
		private NodeScopeResolver $nodeScopeResolver,
		private int $internalErrorsCountLimit,
		private ReflectionProvider $reflectionProvider,
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
				$internalErrorMessage = sprintf('Internal error: %s', $t->getMessage());
				$internalErrorMessage .= sprintf(
					'%sRun PHPStan with --debug option and post the stack trace to:%s%s',
					"\n",
					"\n",
					'https://github.com/phpstan/phpstan/issues/new?template=Bug_report.yaml',
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

		// verify serializability of collected data
		$alreadyReported = [];
		foreach ($collectedData as $collected) {
			if (array_key_exists($collected->getCollectorType(), $alreadyReported)) {
				continue;
			}

			$json = json_encode($collected->getData());
			if ($json === false) {
				$errors[] = $this->buildCollectedDataError(
					sprintf(
						'Data collected by %s is not JSON serializable.',
						$collected->getCollectorType(),
					),
					$collected,
				);
				$alreadyReported[$collected->getCollectorType()] = true;

				break;
			}

			$restored = json_decode($json);
			if ($restored === $collected->getData()) {
				continue;
			}

			$errors[] = $this->buildCollectedDataError(
				sprintf(
					'Data collected by %s is not JSON decodable and will not work in parallel analysis.',
					$collected->getCollectorType(),
				),
				$collected,
			);
			$alreadyReported[$collected->getCollectorType()] = true;
		}

		return new AnalyserResult(
			$errors,
			[],
			$collectedData,
			$internalErrorsCount === 0 ? $dependencies : null,
			$exportedNodes,
			$reachedInternalErrorsCountLimit,
			memory_get_peak_usage(true),
		);
	}

	private function buildCollectedDataError(string $message, CollectedData $collectedData): Error
	{
		if (!$this->reflectionProvider->hasClass($collectedData->getCollectorType())) {
			return new Error(
				sprintf('Unable to find class for collector of type %s', $collectedData->getCollectorType()),
				'N/A',
			);
		}

		$collectorClass = $this->reflectionProvider->getClass($collectedData->getCollectorType());
		$fileName = $collectorClass->getFileName();
		if ($fileName === null) {
			return new Error(
				sprintf('Missing filename for collector of type %s', $collectedData->getCollectorType()),
				'N/A',
			);
		}

		return new Error(
			$message,
			$fileName,
		);
	}

}

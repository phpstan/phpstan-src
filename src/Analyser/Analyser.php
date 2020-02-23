<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Registry;

class Analyser
{

	/** @var \PHPStan\Analyser\FileAnalyser */
	private $fileAnalyser;

	/** @var \PHPStan\Rules\Registry */
	private $registry;

	/** @var \PHPStan\Analyser\NodeScopeResolver */
	private $nodeScopeResolver;

	/** @var IgnoredErrorHelper */
	private $ignoredErrorHelper;

	/** @var int */
	private $internalErrorsCountLimit;

	/** @var \PHPStan\Analyser\Error[] */
	private $collectedErrors = [];

	public function __construct(
		FileAnalyser $fileAnalyser,
		Registry $registry,
		NodeScopeResolver $nodeScopeResolver,
		IgnoredErrorHelper $ignoredErrorHelper,
		int $internalErrorsCountLimit
	)
	{
		$this->fileAnalyser = $fileAnalyser;
		$this->registry = $registry;
		$this->nodeScopeResolver = $nodeScopeResolver;
		$this->ignoredErrorHelper = $ignoredErrorHelper;
		$this->internalErrorsCountLimit = $internalErrorsCountLimit;
	}

	/**
	 * @param string[] $files
	 * @param bool $onlyFiles
	 * @param \Closure(string $file): void|null $preFileCallback
	 * @param \Closure(int): void|null $postFileCallback
	 * @param bool $debug
	 * @return AnalyserResult
	 */
	public function analyse(
		array $files,
		bool $onlyFiles,
		?\Closure $preFileCallback = null,
		?\Closure $postFileCallback = null,
		bool $debug = false
	): AnalyserResult
	{
		$ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			return new AnalyserResult($ignoredErrorHelperResult->getErrors(), false);
		}

		$this->nodeScopeResolver->setAnalysedFiles($files);

		$this->collectErrors($files);

		$errors = [];
		$internalErrorsCount = 0;
		$reachedInternalErrorsCountLimit = false;
		$inferrablePropertyTypesFromConstructorHelper = new InferrablePropertyTypesFromConstructorHelper();
		foreach ($files as $file) {
			if ($preFileCallback !== null) {
				$preFileCallback($file);
			}

			try {
				$errors = array_merge($errors, $this->fileAnalyser->analyseFile(
					$file,
					$this->registry,
					$inferrablePropertyTypesFromConstructorHelper
				));
			} catch (\Throwable $t) {
				if ($debug) {
					throw $t;
				}
				$internalErrorsCount++;
				$internalErrorMessage = sprintf('Internal error: %s', $t->getMessage());
				$internalErrorMessage .= sprintf(
					'%sRun PHPStan with --debug option and post the stack trace to:%s%s',
					"\n",
					"\n",
					'https://github.com/phpstan/phpstan/issues/new'
				);
				$errors[] = new Error($internalErrorMessage, $file, null, false);
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

		$this->restoreCollectErrorsHandler();

		$errors = array_merge($errors, $this->collectedErrors);
		$errors = $ignoredErrorHelperResult->process($errors, $onlyFiles, $reachedInternalErrorsCountLimit);
		if ($reachedInternalErrorsCountLimit) {
			$errors[] = sprintf('Reached internal errors count limit of %d, exiting...', $this->internalErrorsCountLimit);
		}

		return new AnalyserResult(array_merge($errors, $ignoredErrorHelperResult->getWarnings()), $inferrablePropertyTypesFromConstructorHelper->hasInferrablePropertyTypesFromConstructor());
	}

	/**
	 * @param string[] $analysedFiles
	 */
	private function collectErrors(array $analysedFiles): void
	{
		$this->collectedErrors = [];
		set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($analysedFiles): bool {
			if (error_reporting() === 0) {
				// silence @ operator
				return true;
			}

			if (!in_array($errfile, $analysedFiles, true)) {
				return true;
			}

			$this->collectedErrors[] = new Error($errstr, $errfile, $errline, true);

			return true;
		});
	}

	private function restoreCollectErrorsHandler(): void
	{
		restore_error_handler();
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Nette\Utils\Json;
use PHPStan\File\FileHelper;
use PHPStan\PhpDoc\StubValidator;
use PHPStan\Rules\Registry;

class Analyser
{

	/** @var \PHPStan\Analyser\FileAnalyser */
	private $fileAnalyser;

	/** @var \PHPStan\Rules\Registry */
	private $registry;

	/** @var \PHPStan\Analyser\NodeScopeResolver */
	private $nodeScopeResolver;

	/** @var \PHPStan\File\FileHelper */
	private $fileHelper;

	/** @var \PHPStan\PhpDoc\StubValidator */
	private $stubValidator;

	/** @var (string|mixed[])[] */
	private $ignoreErrors;

	/** @var bool */
	private $reportUnmatchedIgnoredErrors;

	/** @var int */
	private $internalErrorsCountLimit;

	/** @var \PHPStan\Analyser\Error[] */
	private $collectedErrors = [];

	/**
	 * @param \PHPStan\Analyser\FileAnalyser $fileAnalyser
	 * @param \PHPStan\Rules\Registry $registry
	 * @param \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver
	 * @param \PHPStan\File\FileHelper $fileHelper
	 * @param \PHPStan\PhpDoc\StubValidator $stubValidator
	 * @param (string|array<string, string>)[] $ignoreErrors
	 * @param bool $reportUnmatchedIgnoredErrors
	 * @param int $internalErrorsCountLimit
	 */
	public function __construct(
		FileAnalyser $fileAnalyser,
		Registry $registry,
		NodeScopeResolver $nodeScopeResolver,
		FileHelper $fileHelper,
		StubValidator $stubValidator,
		array $ignoreErrors,
		bool $reportUnmatchedIgnoredErrors,
		int $internalErrorsCountLimit
	)
	{
		$this->fileAnalyser = $fileAnalyser;
		$this->registry = $registry;
		$this->nodeScopeResolver = $nodeScopeResolver;
		$this->fileHelper = $fileHelper;
		$this->stubValidator = $stubValidator;
		$this->ignoreErrors = $ignoreErrors;
		$this->reportUnmatchedIgnoredErrors = $reportUnmatchedIgnoredErrors;
		$this->internalErrorsCountLimit = $internalErrorsCountLimit;
	}

	/**
	 * @param string[] $files
	 * @param bool $onlyFiles
	 * @param \Closure(string $file): void|null $preFileCallback
	 * @param \Closure(string $file): void|null $postFileCallback
	 * @param bool $debug
	 * @param \Closure(\PhpParser\Node $node, Scope $scope): void|null $outerNodeCallback
	 * @return string[]|\PHPStan\Analyser\Error[] errors
	 */
	public function analyse(
		array $files,
		bool $onlyFiles,
		?\Closure $preFileCallback = null,
		?\Closure $postFileCallback = null,
		bool $debug = false,
		?\Closure $outerNodeCallback = null
	): array
	{
		$errors = [];

		foreach ($this->ignoreErrors as $ignoreError) {
			try {
				if (is_array($ignoreError)) {
					if (!isset($ignoreError['message'])) {
						$errors[] = sprintf(
							'Ignored error %s is missing a message.',
							Json::encode($ignoreError)
						);
						continue;
					}
					if (!isset($ignoreError['path']) && !isset($ignoreError['paths'])) {
						$errors[] = sprintf(
							'Ignored error %s is missing a path.',
							Json::encode($ignoreError)
						);
					}

					$ignoreMessage = $ignoreError['message'];
				} else {
					$ignoreMessage = $ignoreError;
				}

				\Nette\Utils\Strings::match('', $ignoreMessage);
			} catch (\Nette\Utils\RegexpException $e) {
				$errors[] = $e->getMessage();
			}
		}

		if (count($errors) > 0) {
			return $errors;
		}

		$errors = $this->stubValidator->validate();

		$this->nodeScopeResolver->setAnalysedFiles($files);

		$this->collectErrors($files);

		$internalErrorsCount = 0;
		$reachedInternalErrorsCountLimit = false;
		foreach ($files as $file) {
			if ($preFileCallback !== null) {
				$preFileCallback($file);
			}

			try {
				$errors = array_merge($errors, $this->fileAnalyser->analyseFile(
					$file,
					$this->registry,
					$outerNodeCallback
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
				$errors[] = new Error($internalErrorMessage, $file);
				if ($internalErrorsCount >= $this->internalErrorsCountLimit) {
					$reachedInternalErrorsCountLimit = true;
					break;
				}
			}

			if ($postFileCallback === null) {
				continue;
			}

			$postFileCallback($file);
		}

		$this->restoreCollectErrorsHandler();

		$errors = array_merge($errors, $this->collectedErrors);

		$unmatchedIgnoredErrors = $this->ignoreErrors;
		$addErrors = [];
		$errors = array_values(array_filter($errors, function (Error $error) use (&$unmatchedIgnoredErrors, &$addErrors): bool {
			foreach ($this->ignoreErrors as $i => $ignore) {
				$shouldBeIgnored = false;
				if (is_string($ignore)) {
					$shouldBeIgnored = IgnoredError::shouldIgnore($this->fileHelper, $error, $ignore, null);
					if ($shouldBeIgnored) {
						unset($unmatchedIgnoredErrors[$i]);
					}
				} else {
					if (isset($ignore['path'])) {
						$shouldBeIgnored = IgnoredError::shouldIgnore($this->fileHelper, $error, $ignore['message'], $ignore['path']);
						if ($shouldBeIgnored) {
							if (isset($ignore['count'])) {
								$realCount = $unmatchedIgnoredErrors[$i]['realCount'] ?? 0;
								$realCount++;
								$unmatchedIgnoredErrors[$i]['realCount'] = $realCount;

								if ($realCount > $ignore['count']) {
									$shouldBeIgnored = false;
									if (!isset($unmatchedIgnoredErrors[$i]['file'])) {
										$unmatchedIgnoredErrors[$i]['file'] = $error->getFile();
										$unmatchedIgnoredErrors[$i]['line'] = $error->getLine();
									}
								}
							} else {
								unset($unmatchedIgnoredErrors[$i]);
							}
						}
					} elseif (isset($ignore['paths'])) {
						foreach ($ignore['paths'] as $j => $ignorePath) {
							$shouldBeIgnored = IgnoredError::shouldIgnore($this->fileHelper, $error, $ignore['message'], $ignorePath);
							if ($shouldBeIgnored) {
								if (isset($unmatchedIgnoredErrors[$i])) {
									if (!is_array($unmatchedIgnoredErrors[$i])) {
										throw new \PHPStan\ShouldNotHappenException();
									}
									unset($unmatchedIgnoredErrors[$i]['paths'][$j]);
									if (isset($unmatchedIgnoredErrors[$i]['paths']) && count($unmatchedIgnoredErrors[$i]['paths']) === 0) {
										unset($unmatchedIgnoredErrors[$i]);
									}
								}
								break;
							}
						}
					} else {
						throw new \PHPStan\ShouldNotHappenException();
					}
				}

				if ($shouldBeIgnored) {
					if (!$error->canBeIgnored()) {
						$addErrors[] = sprintf(
							'Error message "%s" cannot be ignored, use excludes_analyse instead.',
							$error->getMessage()
						);
						return true;
					}
					return false;
				}
			}

			return true;
		}));

		foreach ($unmatchedIgnoredErrors as $unmatchedIgnoredError) {
			if (!isset($unmatchedIgnoredError['count']) || !isset($unmatchedIgnoredError['realCount'])) {
				continue;
			}

			if ($unmatchedIgnoredError['realCount'] <= $unmatchedIgnoredError['count']) {
				continue;
			}

			$addErrors[] = new Error(sprintf(
				'Ignored error pattern %s is expected to occur %d %s, but occured %d %s.',
				IgnoredError::stringifyPattern($unmatchedIgnoredError),
				$unmatchedIgnoredError['count'],
				$unmatchedIgnoredError['count'] === 1 ? 'time' : 'times',
				$unmatchedIgnoredError['realCount'],
				$unmatchedIgnoredError['realCount'] === 1 ? 'time' : 'times'
			), $unmatchedIgnoredError['file'], $unmatchedIgnoredError['line']);
		}

		$errors = array_merge($errors, $addErrors);

		if (!$onlyFiles && $this->reportUnmatchedIgnoredErrors && !$reachedInternalErrorsCountLimit) {
			foreach ($unmatchedIgnoredErrors as $unmatchedIgnoredError) {
				if (
					isset($unmatchedIgnoredError['count'])
					&& isset($unmatchedIgnoredError['realCount'])
				) {
					if ($unmatchedIgnoredError['realCount'] < $unmatchedIgnoredError['count']) {
						$errors[] = sprintf(
							'Ignored error pattern %s is expected to occur %d %s, but occured only %d %s.',
							IgnoredError::stringifyPattern($unmatchedIgnoredError),
							$unmatchedIgnoredError['count'],
							$unmatchedIgnoredError['count'] === 1 ? 'time' : 'times',
							$unmatchedIgnoredError['realCount'],
							$unmatchedIgnoredError['realCount'] === 1 ? 'time' : 'times'
						);
					}
				} else {
					$errors[] = sprintf(
						'Ignored error pattern %s was not matched in reported errors.',
						IgnoredError::stringifyPattern($unmatchedIgnoredError)
					);
				}
			}
		}

		if ($reachedInternalErrorsCountLimit) {
			$errors[] = sprintf('Reached internal errors count limit of %d, exiting...', $this->internalErrorsCountLimit);
		}

		return $errors;
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

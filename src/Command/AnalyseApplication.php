<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\InferrablePropertyTypesFromConstructorHelper;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\PhpDoc\StubValidator;

class AnalyseApplication
{

	/** @var \PHPStan\Analyser\Analyser */
	private $analyser;

	/** @var \PHPStan\PhpDoc\StubValidator */
	private $stubValidator;

	/** @var string */
	private $memoryLimitFile;

	public function __construct(
		Analyser $analyser,
		StubValidator $stubValidator,
		string $memoryLimitFile
	)
	{
		$this->analyser = $analyser;
		$this->stubValidator = $stubValidator;
		$this->memoryLimitFile = $memoryLimitFile;
	}

	/**
	 * @param string[] $files
	 * @param bool $onlyFiles
	 * @param \PHPStan\Command\Output $stdOutput
	 * @param \PHPStan\Command\Output $errorOutput
	 * @param \PHPStan\Command\ErrorFormatter\ErrorFormatter $errorFormatter
	 * @param bool $defaultLevelUsed
	 * @param bool $debug
	 * @param string|null $projectConfigFile
	 * @return int Error code.
	 */
	public function analyse(
		array $files,
		bool $onlyFiles,
		Output $stdOutput,
		Output $errorOutput,
		ErrorFormatter $errorFormatter,
		bool $defaultLevelUsed,
		bool $debug,
		?string $projectConfigFile
	): int
	{
		$this->updateMemoryLimitFile();
		$errors = $this->stubValidator->validate();

		register_shutdown_function(function (): void {
			$error = error_get_last();
			if ($error === null) {
				return;
			}
			if ($error['type'] !== E_ERROR) {
				return;
			}

			if (strpos($error['message'], 'Allowed memory size') !== false) {
				return;
			}

			@unlink($this->memoryLimitFile);
		});

		if (!$debug) {
			$progressStarted = false;
			$fileOrder = 0;
			$preFileCallback = null;
			$postFileCallback = function () use ($errorOutput, &$progressStarted, $files, &$fileOrder): void {
				if (!$progressStarted) {
					$errorOutput->getStyle()->progressStart(count($files));
					$progressStarted = true;
				}
				$errorOutput->getStyle()->progressAdvance();
				if ($fileOrder % 100 === 0) {
					$this->updateMemoryLimitFile();
				}
				$fileOrder++;
			};
		} else {
			$preFileCallback = static function (string $file) use ($stdOutput): void {
				$stdOutput->writeLineFormatted($file);
			};
			$postFileCallback = null;
		}

		$inferrablePropertyTypesFromConstructorHelper = new InferrablePropertyTypesFromConstructorHelper();
		$errors = array_merge($errors, $this->analyser->analyse(
			$files,
			$onlyFiles,
			$preFileCallback,
			$postFileCallback,
			$debug,
			$inferrablePropertyTypesFromConstructorHelper
		));

		if (isset($progressStarted) && $progressStarted) {
			$errorOutput->getStyle()->progressFinish();
		}

		$fileSpecificErrors = [];
		$notFileSpecificErrors = [];
		$warnings = [];
		foreach ($errors as $error) {
			if (is_string($error)) {
				$notFileSpecificErrors[] = $error;
			} else {
				if ($error->isWarning()) {
					$warnings[] = $error->getMessage();
					continue;
				}
				$fileSpecificErrors[] = $error;
			}
		}

		return $errorFormatter->formatErrors(
			new AnalysisResult(
				$fileSpecificErrors,
				$notFileSpecificErrors,
				$warnings,
				$defaultLevelUsed,
				$inferrablePropertyTypesFromConstructorHelper->hasInferrablePropertyTypesFromConstructor(),
				$projectConfigFile
			),
			$stdOutput
		);
	}

	private function updateMemoryLimitFile(): void
	{
		$bytes = memory_get_peak_usage(true);
		$megabytes = ceil($bytes / 1024 / 1024);
		file_put_contents($this->memoryLimitFile, sprintf('%d MB', $megabytes));
	}

}

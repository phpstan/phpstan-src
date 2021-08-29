<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\DI\Helpers;
use Nette\Neon\Neon;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use const SORT_STRING;
use function ksort;
use function preg_quote;

class BaselineNeonErrorFormatter implements ErrorFormatter
{

	private \PHPStan\File\RelativePathHelper $relativePathHelper;

	public function __construct(RelativePathHelper $relativePathHelper)
	{
		$this->relativePathHelper = $relativePathHelper;
	}

	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output
	): int
	{
		if (!$analysisResult->hasErrors()) {
			$output->writeRaw(Neon::encode([
				'parameters' => [
					'ignoreErrors' => [],
				],
			], Neon::BLOCK));
			return 0;
		}

		$fileErrors = [];
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			if (!$fileSpecificError->canBeIgnored()) {
				continue;
			}
			$filePath = $fileSpecificError->getFilePath();
			$relativeFilePath = $this->relativePathHelper->getRelativePath($filePath);
			$fileErrors[$relativeFilePath][] = $fileSpecificError->getMessage();
		}
		ksort($fileErrors, SORT_STRING);

		$errorsToOutput = [];
		foreach ($fileErrors as $file => $errorMessages) {
			$fileErrorsCounts = [];
			foreach ($errorMessages as $errorMessage) {
				if (!isset($fileErrorsCounts[$errorMessage])) {
					$fileErrorsCounts[$errorMessage] = 1;
					continue;
				}

				$fileErrorsCounts[$errorMessage]++;
			}
			ksort($fileErrorsCounts, SORT_STRING);

			foreach ($fileErrorsCounts as $message => $count) {
				$errorsToOutput[] = [
					'message' => Helpers::escape('#^' . preg_quote($message, '#') . '$#'),
					'count' => $count,
					'path' => Helpers::escape($file),
				];
			}
		}

		$output->writeRaw(Neon::encode([
			'parameters' => [
				'ignoreErrors' => $errorsToOutput,
			],
		], Neon::BLOCK));

		return 1;
	}

}

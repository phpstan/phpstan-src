<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\DI\Helpers;
use Nette\Neon\Neon;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PHPStan\ShouldNotHappenException;
use function ksort;
use function preg_quote;
use function substr;
use const SORT_STRING;

class BaselineNeonErrorFormatter
{

	public function __construct(private RelativePathHelper $relativePathHelper)
	{
	}

	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output,
	): int
	{
		if (!$analysisResult->hasErrors()) {
			$output->writeRaw($this->getNeon([]));
			return 0;
		}

		$fileErrors = [];
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			if (!$fileSpecificError->canBeIgnored()) {
				continue;
			}
			$fileErrors[$this->relativePathHelper->getRelativePath($fileSpecificError->getFilePath())][] = $fileSpecificError->getMessage();
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

		$output->writeRaw($this->getNeon($errorsToOutput));

		return 1;
	}

	/**
	 * @param array<int, array{message: string, count: int, path: string}> $ignoreErrors
	 */
	private function getNeon(array $ignoreErrors): string
	{
		$neon = Neon::encode([
			'parameters' => [
				'ignoreErrors' => $ignoreErrors,
			],
		], Neon::BLOCK);

		if (substr($neon, -2) !== "\n\n") {
			throw new ShouldNotHappenException();
		}

		return substr($neon, 0, -1);
	}

}

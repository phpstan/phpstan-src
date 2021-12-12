<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\DI\Helpers;
use Nette\Neon\Neon;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use function ksort;
use function preg_quote;
use const SORT_STRING;

class BaselineNeonErrorFormatter implements ErrorFormatter
{

	private RelativePathHelper $relativePathHelper;

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

			$relativePath = $this->relativePathHelper->getRelativePath($fileSpecificError->getFilePath());
			$key = $fileSpecificError->getIdentifier() . $fileSpecificError->getMessage();

			$fileErrors[$relativePath][$key] ??= [
				'message' => $fileSpecificError->getMessage(),
				'count' => 0,
				'identifier' => $fileSpecificError->getIdentifier()
			];
			$fileErrors[$relativePath][$key]['count']++;
		}
		ksort($fileErrors, SORT_STRING);

		$errorsToOutput = [];
		foreach ($fileErrors as $file => $fileSpecificErrors) {
			ksort($fileSpecificErrors, SORT_STRING);

			foreach ($fileSpecificErrors as $data) {
				$error = [
					'message' => Helpers::escape('#^' . preg_quote($data['message'], '#') . '$#'),
					'count' => $data['count'],
					'path' => Helpers::escape($file),
				];
				if ($data['identifier'] !== null && $data['identifier'] !== '') {
					$error['identifier'] = Helpers::escape($data['identifier']);
				}

				$errorsToOutput[] = $error;
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
